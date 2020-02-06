<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlayerRepository")
 */
class Player
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
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Game", inversedBy="players")
     * @ORM\JoinColumn(nullable=false)
     */
    private $game;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $points;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $current_answer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function setGame(?Game $game): self
    {
        $this->game = $game;

        return $this;
    }

    public function getPoints(): ?int
    {
        return $this->points;
    }

    public function setPoints(?int $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function getCurrentAnswer(): ?string
    {
        return $this->current_answer;
    }

    public function setCurrentAnswer(?string $current_answer): self
    {
        $this->current_answer = $current_answer;

        return $this;
    }
}
