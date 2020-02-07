<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Entity\Game;
use App\Entity\Player;
use App\Entity\Question;
use Doctrine\ORM\Mapping\Entity;
use http\Env\Request as whatisthis;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\Publisher as Somethingelse;
use Symfony\Component\Mercure\PublisherInterface as Stupid;
//use App\Controller\Publisher;
use Symfony\Component\Mercure\Update;
use Symfony\Component\HttpFoundation\Request;

class GameController extends AbstractController
{




    /**
     * @Route("/new-game")
     */
    public function createGame(LoggerInterface $logger, Request $request)
    {
        if ($request->getMethod() == "OPTIONS") {
            $response = new Response('', 204, [
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'DNT, X-User-Token, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type',
                'Access-Control-Max-Age' => 1728000,
                'Content-Type' => 'text/plain charset=UTF-8',
                'Content-Length' => 0
            ]);
            return $response;
        }
        $em = $this->getDoctrine()->getManager();
        $playerJson = $request->getContent();
        $playerJson = json_decode($playerJson);
        $game = new Game();
        $game->setCode(substr(uniqid(), -4));
        $player = new Player();
        $player->setName($playerJson->player);
        $player->setGame($game);
        $game->addPlayer($player);
        $em->persist($game);
        $em->persist($player);
        $em->flush();

        $gameObj = $this->gameInfo($game);

        $response =  new JsonResponse($json);
        $response = new JsonResponse(
            ['gameId' => $game->getCode(), 'player' => array('name' => $player->getName(), 'id' => $player->getId()), 'gameObj' => $gameObj], 200, ['Access-Control-Allow-Origin' => '*']
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;

    }

    /**
     * @Route("/new-player/{gameCode}/{playerName}")
     */
    public function newPlayer(LoggerInterface $logger, Stupid $stupid, $gameCode, $playerName) {
        $em = $this->getDoctrine()->getManager();
        $game = $em->getRepository(Game::class)->findOneBy(array('code' => $gameCode));
        if (!$game) {
            $logger->info('^^^No game found');
            return new JsonResponse(['message' => 'No game was found using that game code, please check and try again.']);
        }
        //check to see if a player with this name has already joined the game
        $player = null;
        $players = $game->getPlayers();
        if ($players) {
            foreach ($players as $gamePlayer) {
                $player = $gamePlayer->getName() == $playerName ? $playerName : null;
            }
            if ($player) {
                $logger->error('Player name already taken');
                return new Response('Player name already taken');
            }
        }
        if ($player === null) {
            $player = new Player();
            $player->setGame($game);
            $player->setName($playerName);
            $em->persist($player);
            $game->addPlayer($player);
            $em->persist($game);
            $em->flush();
            $logger->info('^^^Just created a new player');
        }

        $updatedGameObj = $em->getRepository(Game::class)->findOneBy(array('code' => $gameCode));
        $logger->info('^^^Game info: '.var_export($this->gameInfo($updatedGameObj), true));
        $update = new Update(
            'http://tylerreber.com',
            json_encode($this->gameInfo($game))
        );
        $stupid($update);

        $gameObj = $this->gameInfo($game);

        // todo return player id and game id
        $response = new JsonResponse(
            ['gameId' => $game->getCode(), 'player' => array('name' => $player->getName(), 'id' => $player->getId()), 'gameObj' => $gameObj]
        );
        $response->headers->set('Access-Control-Allow-Origin', '*');

        return $response;
    }


    /**
     * @Route("/next-question/{gameCode}")
     */
    public function nextQuestion(LoggerInterface $logger, Stupid $stupid, $gameCode) {
        $repository = $this->getDoctrine()->getRepository(Question::class);
        /**
         * @var $game Game
         */
        $game = $this->getDoctrine()->getRepository(Game::class)->findOneBy(array('code' => $gameCode));
        $questionHistory = $game->getHistory();
        $count = $repository->createQueryBuilder('q')
            ->select('count(q.id)')
            ->getQuery()
            ->getSingleScalarResult();
        $currentQuestion = null;
        while (!$currentQuestion) {
            $id = rand(1, $count);
            $possibleQuestion = $repository->find($id);
            foreach ($questionHistory as $historicQuestion) {
//                $logger->info('^^^History question '.var_export($historicQuestion, true));
            }
//            if ($possibleQuestion && !in_array($possibleQuestion, $questionHistory)) {
                $currentQuestion = $possibleQuestion;
//            }
        }

        $game->setCurrentQuestion($currentQuestion);
        $game->addHistory($currentQuestion);
        $players = $game->getPlayers();
        foreach ($players as $player) {
            $player->setCurrentAnswer(null);
        }
        $this->getDoctrine()->getManager()->flush();
        $gameUpdate = $this->gameInfo($game);
        $gameUpdate['newQuestion'] = true;
        $logger->info('^^^Game info '.var_export($gameUpdate, true));
        $update = new Update(
            'http://tylerreber.com',
            json_encode($gameUpdate)
        );
        $stupid($update);

        return new JsonResponse($this->gameInfo($game));
    }

    /**
     * @route("/answer")
     */
    public function checkAnswer(Request $request, Stupid $stupid, LoggerInterface $logger) {
        $em = $this->getDoctrine()->getManager();
        $answer = json_decode($request->getContent());

        // find the player
        $player = $em->getRepository(Player::class)->find($answer->player);

        // get the game and current question
        /**
         * @var $game Game
         */
        $game = $em->getRepository(Game::class)->findOneBy(array('code' => $answer->game));

        $player->setCurrentAnswer($answer->userAnswer);
        $em->flush();
        $players = $game->getPlayers();
        $playersWaiting = 0;
        foreach ($players as $i) {
            if (!$i->getCurrentAnswer()) {
                $playersWaiting++;
            }
        }
        if ($playersWaiting > 0) {
            $gameUpdate = $this->gameInfo($game);
            $logger->info('^^^Game info '.var_export($gameUpdate, true));
            $update = new Update(
                'http://tylerreber.com',
                json_encode($gameUpdate)
            );
            $stupid($update);

            return new Response('ok');
        }
        // if there were no players waiting to respond, we can check the scores.
        // Check the solution for each player.
        foreach ($players as $i) {
            if ($game->getCurrentQuestion()->getSolution() == $i->getCurrentAnswer()) {
                $i->setPoints($i->getPoints() + 1 );
            }
        }
        $em->flush();
        $gameUpdate = $this->gameInfo($game);
        $gameUpdate['nextQuestion'] = true;
        $logger->info('^^^Game info '.var_export($gameUpdate, true));
        $update = new Update(
            'http://tylerreber.com',
            json_encode($gameUpdate)
        );
        $stupid($update);

        return new Response('ok');

    }

    private function gameInfo(Game $game) {
        $players = $game->getPlayers();
        $playersArray = [];

        foreach ($players as $player) {
            /**
             * @var $player Player
             */
            $responded = $player->getCurrentAnswer() ? true: false;
            array_push($playersArray, array('playerName' => $player->getName(), 'points' => $player->getPoints(), 'id' => $player->getId(), 'responded' => $responded));
        }
        $currentQuestion = $game->getCurrentQuestion();
        $currentQuestionArray = null;
        if ($currentQuestion) {
            $currentQuestionArray = array('prompt' => $currentQuestion->getPrompt(), 'solution' => $currentQuestion->getSolution(), 'l1' => $currentQuestion->getFalsety1(), 'l2' => $currentQuestion->getFalsety2(), 'l3' => $currentQuestion->getFalse3());
        }
        return array('gameCode' => $game->getCode(), 'players' => $playersArray, 'currentQuestion' => $currentQuestionArray, 'nextQuestion' => false, 'newQuestion' => false);
    }



}
