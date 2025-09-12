<?php

namespace App\Entity;

use App\Repository\TrailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TrailRepository::class)]
#[UniqueEntity(fields: ['user', 'name'], message: "Vous avez déjà créé un itinéraire avec ce nom.")]
class Trail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 50)]
    #[Assert\Regex(
        pattern: '/^[\p{L}0-9\s\'\-]+$/u',
    )]
    private ?string $name = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La distance doit être positive.")]
    #[Assert\LessThanOrEqual(value: 80, message: "Distance maximale 80 km.")]
    private ?float $distance = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: "La durée doit être positive.")]
    #[Assert\LessThanOrEqual(value: 600, message: "Durée maximale 10 h.")]
    private ?float $duration = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Choice(choices: ['easy', 'medium', 'hard'])]
    private ?string $difficulty = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: 0, max: 5)]
    private ?float $score = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Choice(choices: ['true', 'false'])]
    private ?string $water_point = null;

    #[ORM\ManyToOne(inversedBy: 'trails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, Walk>
     */
    #[ORM\OneToMany(targetEntity: Walk::class, mappedBy: 'trail', cascade: ['remove'], orphanRemoval: true)]
    private Collection $walks;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: "/^[\p{L}0-9\s,'\.\-]+$/u",
        message: "Adresse invalide."
    )]
    private ?string $startAddress = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '/^\d{5}$/',
        message: "Code postal invalide."
    )]
    private ?string $startCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'\-]+$/u",
        message: "Ville invalide."
    )]
    private ?string $startCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: "/^[\p{L}0-9\s,'\.\-]+$/u",
        message: "Adresse invalide."
    )]
    private ?string $endAddress = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '/^\d{5}$/',
        message: "Code postal invalide."
    )]
    private ?string $endCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: "/^[\p{L}\s'\-]+$/u",
        message: "Ville invalide."
    )]
    private ?string $endCity = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameSearch = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $startCitySearch = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $endCitySearch = null;

    /**
     * @var Collection<int, Photo>
     */
    #[ORM\OneToMany(mappedBy: 'trail', targetEntity: Photo::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $photos;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '/\.gpx$/i',
        message: "Seuls les fichiers .gpx sont acceptés."
    )]
    private ?string $gpxFile = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Choice(
        choices: ['gpx', 'manual'],
        message: "Mode d'entrée invalide."
    )]
    private ?string $inputMode = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: -90, max: 90)]
    private ?float $startLat = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: -180, max: 180)]
    private ?float $startLon = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: -90, max: 90)]
    private ?float $endLat = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Range(min: -180, max: 180)]
    private ?float $endLon = null;

    #[ORM\Column(length: 255)]
    #[Assert\Choice(choices: ['Active', 'Inactive'])]
    private ?string $status = null;

    public function __construct()
    {
        $this->walks = new ArrayCollection();
        $this->photos = new ArrayCollection();
        $this->status = "Active";
    }

    private function normalize(string $value): string
    {
        if ($value === null) {
            return '';
        }
        $value = strtolower($value);
        $value = str_replace(['-', ' ', '_', '.'], '', $value);
        return $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        $this->nameSearch = $this->normalize($name);
        return $this;
    }

    public function getDistance(): ?float
    {
        return $this->distance;
    }

    public function setDistance(float $distance): static
    {
        $this->distance = $distance;

        return $this;
    }

    public function getDuration(): ?float
    {
        return $this->duration;
    }

    public function setDuration(float $duration): static
    {
        $this->duration = $duration;

        return $this;
    }

    public function getDifficulty(): ?string
    {
        return $this->difficulty;
    }

    public function setDifficulty(string $difficulty): static
    {
        $this->difficulty = $difficulty;

        return $this;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function setScore(float $score): static
    {
        $this->score = $score;

        return $this;
    }

    public function getWaterPoint(): ?string
    {
        return $this->water_point;
    }

    public function setWaterPoint(string $water_point): static
    {
        $this->water_point = $water_point;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, Walk>
     */
    public function getWalks(): Collection
    {
        return $this->walks;
    }

    public function addWalk(Walk $walk): static
    {
        if (!$this->walks->contains($walk)) {
            $this->walks->add($walk);
            $walk->setTrail($this);
        }

        return $this;
    }

    public function removeWalk(Walk $walk): static
    {
        if ($this->walks->removeElement($walk)) {
            // set the owning side to null (unless already changed)
            if ($walk->getTrail() === $this) {
                $walk->setTrail(null);
            }
        }

        return $this;
    }

    public function getStartAddress(): ?string
    {
        return $this->startAddress;
    }

    public function setStartAddress(string $startAddress): static
    {
        $this->startAddress = $startAddress;

        return $this;
    }

    public function getStartCode(): ?string
    {
        return $this->startCode;
    }

    public function setStartCode(?string $startCode): static
    {
        $this->startCode = $startCode;

        return $this;
    }

    public function getStartCity(): ?string
    {
        return $this->startCity;
    }

    public function setStartCity(?string $startCity): static
    {
        $this->startCity = $startCity;
        $this->startCitySearch = $this->normalize($startCity);
        return $this;
    }

    public function getEndAddress(): ?string
    {
        return $this->endAddress;
    }

    public function setEndAddress(string $endAddress): static
    {
        $this->endAddress = $endAddress;

        return $this;
    }

    public function getEndCode(): ?string
    {
        return $this->endCode;
    }

    public function setEndCode(?string $endCode): static
    {
        $this->endCode = $endCode;

        return $this;
    }

    public function getEndCity(): ?string
    {
        return $this->endCity;
    }

    public function setEndCity(?string $endCity): static
    {
        $this->endCity = $endCity;
        $this->endCitySearch = $this->normalize($endCity);
        return $this;
    }

    public function getNameSearch(): ?string
    {
        return $this->nameSearch;
    }

    public function setNameSearch(string $nameSearch): static
    {
        $this->nameSearch = $nameSearch;

        return $this;
    }

    public function getStartCitySearch(): ?string
    {
        return $this->startCitySearch;
    }

    public function setStartCitySearch(string $startCitySearch): static
    {
        $this->startCitySearch = $startCitySearch;

        return $this;
    }

    public function getEndCitySearch(): ?string
    {
        return $this->endCitySearch;
    }

    public function setEndCitySearch(string $endCitySearch): static
    {
        $this->endCitySearch = $endCitySearch;

        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): static
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setTrail($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): static
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getTrail() === $this) {
                $photo->setTrail(null);
            }
        }

        return $this;
    }

    public function getGpxFile(): ?string
    {
        return $this->gpxFile;
    }

    public function setGpxFile(?string $gpxFile): static
    {
        $this->gpxFile = $gpxFile;

        return $this;
    }

    public function getInputMode(): ?string
    {
        return $this->inputMode;
    }

    public function setInputMode(?string $inputMode): static
    {
        $this->inputMode = $inputMode;

        return $this;
    }

    public function getStartLat(): ?float
    {
        return $this->startLat;
    }

    public function setStartLat(?float $startLat): static
    {
        $this->startLat = $startLat;

        return $this;
    }

    public function getStartLon(): ?float
    {
        return $this->startLon;
    }

    public function setStartLon(?float $startLon): static
    {
        $this->startLon = $startLon;

        return $this;
    }

    public function getEndLat(): ?float
    {
        return $this->endLat;
    }

    public function setEndLat(?float $endLat): static
    {
        $this->endLat = $endLat;

        return $this;
    }

    public function getEndLon(): ?float
    {
        return $this->endLon;
    }

    public function setEndLon(?float $endLon): static
    {
        $this->endLon = $endLon;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $Status): static
    {
        $this->status = $Status;

        return $this;
    }
}
