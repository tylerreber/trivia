<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\GameRepository")
 */
class Game
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Player", mappedBy="game", orphanRemoval=true)
     */
    private $players;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Question", cascade={"persist", "remove"})
     */
    private $current_question;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Question", mappedBy="game")
     */
    private $history;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->history = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return Collection|Player[]
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players[] = $player;
            $player->setGame($this);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        if ($this->players->contains($player)) {
            $this->players->removeElement($player);
            // set the owning side to null (unless already changed)
            if ($player->getGame() === $this) {
                $player->setGame(null);
            }
        }

        return $this;
    }

    public function getCurrentQuestion(): ?Question
    {
        return $this->current_question;
    }

    public function setCurrentQuestion(?Question $current_question): self
    {
        $this->current_question = $current_question;

        return $this;
    }

    /**
     * @return Collection|Question[]
     */
    public function getHistory(): Collection
    {
        return $this->history;
    }

    public function addHistory(Question $history): self
    {
        if (!$this->history->contains($history)) {
            $this->history[] = $history;
            $history->setGame($this);
        }

        return $this;
    }

    public function removeHistory(Question $history): self
    {
        if ($this->history->contains($history)) {
            $this->history->removeElement($history);
            // set the owning side to null (unless already changed)
            if ($history->getGame() === $this) {
                $history->setGame(null);
            }
        }

        return $this;
    }
}
