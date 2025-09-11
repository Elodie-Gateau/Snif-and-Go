<?php

namespace App\Entity;

use App\Repository\WalkRegistrationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: WalkRegistrationRepository::class)]
#[UniqueEntity(fields: ['dog', 'walk', 'status'], message: "Ce chien est déjà inscrit à cette balade.")]

class WalkRegistration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull]
    private ?\DateTime $date_registration = null;

    #[ORM\ManyToOne(inversedBy: 'walk_registration')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Dog $dog = null;

    #[ORM\ManyToOne(inversedBy: 'walk_registration')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private ?Walk $walk = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ['Active', 'Cancelled'])]
    private ?string $status = null;

    public function __construct()
    {
        $this->date_registration = new \DateTime();
        $this->status = "Active";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDateRegistration(): ?\DateTime
    {
        return $this->date_registration;
    }

    public function setDateRegistration(\DateTime $date_registration): static
    {
        $this->date_registration = $date_registration;

        return $this;
    }

    public function getDog(): ?Dog
    {
        return $this->dog;
    }

    public function setDog(?Dog $dog): static
    {
        $this->dog = $dog;

        return $this;
    }

    public function getWalk(): ?Walk
    {
        return $this->walk;
    }

    public function setWalk(?Walk $walk): static
    {
        $this->walk = $walk;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }
}
