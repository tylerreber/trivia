<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\QuestionRepository")
 */
class Question
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
    private $prompt;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $solution;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $falsety1;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $falsety2;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $false3;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Game", inversedBy="history")
     */
    private $game;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrompt(): ?string
    {
        return $this->prompt;
    }

    public function setPrompt(string $prompt): self
    {
        $this->prompt = $prompt;

        return $this;
    }

    public function getSolution(): ?string
    {
        return $this->solution;
    }

    public function setSolution(string $solution): self
    {
        $this->solution = $solution;

        return $this;
    }

    public function getFalsety1(): ?string
    {
        return $this->falsety1;
    }

    public function setFalsety1(string $falsety1): self
    {
        $this->falsety1 = $falsety1;

        return $this;
    }

    public function getFalsety2(): ?string
    {
        return $this->falsety2;
    }

    public function setFalsety2(string $falsety2): self
    {
        $this->falsety2 = $falsety2;

        return $this;
    }

    public function getFalse3(): ?string
    {
        return $this->false3;
    }

    public function setFalse3(string $false3): self
    {
        $this->false3 = $false3;

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
}
