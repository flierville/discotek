<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\UnicodeString;

/**
 * French city used by the location autocomplete (imported from the legacy database).
 */
#[ORM\Entity(repositoryClass: CityRepository::class, readOnly: true)]
#[ORM\Index(name: 'city_search_name', columns: ['search_name'])]
#[ORM\Index(name: 'city_postal_code', columns: ['postal_code'])]
class City
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Lower case name without accents nor dashes, for searching. */
    #[ORM\Column(length: 180)]
    private string $searchName;

    public function __construct(
        #[ORM\Column(length: 180)]
        private string $name,
        #[ORM\Column(length: 10)]
        private string $postalCode,
        #[ORM\Column(length: 100)]
        private string $department,
        #[ORM\Column(length: 100)]
        private string $region,
        #[ORM\Column]
        private float $latitude,
        #[ORM\Column]
        private float $longitude,
    ) {
        $this->searchName = self::normalize($name);
    }

    public static function normalize(string $value): string
    {
        $ascii = (new UnicodeString($value))->ascii()->lower()->toString();

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $ascii));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSearchName(): string
    {
        return $this->searchName;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function getDepartment(): string
    {
        return $this->department;
    }

    public function getRegion(): string
    {
        return $this->region;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getLabel(): string
    {
        return \sprintf('%s (%s, %s - %s)', $this->name, $this->postalCode, $this->region, $this->department);
    }
}
