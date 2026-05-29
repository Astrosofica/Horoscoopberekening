<?php

namespace Astro\Entity;

class Horoscope
{
    private ?int $id = null;
    private ?string $slug = null;
    private int $userId;
    private ?string $firstname = null;
    private ?string $infix = null;
    private string $lastname;
    private string $birthDate;
    private string $birthTime;
    private string $locationName;
    private float $latitude;
    private float $longitude;
    private string $timezoneId;
    private int $utcOffset;
    private ?string $timeCorrection = null;
    private ?string $offsetSource = null;
    private ?string $offsetLabel = null;
    private ?string $formattedAddress = null;
    private string $houseSystem = 'K';
    private ?\DateTime $createdAt = null;
    private ?\DateTime $updatedAt = null;

    public function __construct(
        int $userId,
        string $lastname,
        string $birthDate,
        string $birthTime,
        string $locationName,
        float $latitude,
        float $longitude,
        string $timezoneId,
        int $utcOffset,
        ?string $firstname = null,
        ?string $infix = null
    ) {
        $this->userId = $userId;
        $this->lastname = $lastname;
        $this->birthDate = $birthDate;
        $this->birthTime = $birthTime;
        $this->locationName = $locationName;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
        $this->timezoneId = $timezoneId;
        $this->utcOffset = $utcOffset;
        $this->firstname = $firstname;
        $this->infix = $infix;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): self
    {
        $this->firstname = $firstname;
        return $this;
    }

    public function getInfix(): ?string
    {
        return $this->infix;
    }

    public function setInfix(?string $infix): self
    {
        $this->infix = $infix;
        return $this;
    }

    public function getLastname(): string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;
        return $this;
    }

    public function getName(): string
    {
        return $this->getFullName();
    }

    public function getFullName(): string
    {
        $parts = array_filter([$this->firstname, $this->infix, $this->lastname]);
        return implode(' ', $parts);
    }

    public function getBirthDate(): string
    {
        return $this->birthDate;
    }

    public function setBirthDate(string $birthDate): self
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getBirthTime(): string
    {
        return $this->birthTime;
    }

    public function setBirthTime(string $birthTime): self
    {
        $this->birthTime = $birthTime;
        return $this;
    }

    public function getLocationName(): string
    {
        return $this->locationName;
    }

    public function setLocationName(string $locationName): self
    {
        $this->locationName = $locationName;
        return $this;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function setLatitude(float $latitude): self
    {
        $this->latitude = $latitude;
        return $this;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function setLongitude(float $longitude): self
    {
        $this->longitude = $longitude;
        return $this;
    }

    public function getTimezoneId(): string
    {
        return $this->timezoneId;
    }

    public function setTimezoneId(string $timezoneId): self
    {
        $this->timezoneId = $timezoneId;
        return $this;
    }

    public function getUtcOffset(): int
    {
        return $this->utcOffset;
    }

    public function setUtcOffset(int $utcOffset): self
    {
        $this->utcOffset = $utcOffset;
        return $this;
    }

    public function getTimeCorrection(): ?string
    {
        return $this->timeCorrection;
    }

    public function setTimeCorrection(?string $timeCorrection): self
    {
        $this->timeCorrection = $timeCorrection;
        return $this;
    }

    public function getOffsetSource(): ?string
    {
        return $this->offsetSource;
    }

    public function setOffsetSource(?string $offsetSource): self
    {
        $this->offsetSource = $offsetSource;
        return $this;
    }

    public function getOffsetLabel(): ?string
    {
        return $this->offsetLabel;
    }

    public function setOffsetLabel(?string $offsetLabel): self
    {
        $this->offsetLabel = $offsetLabel;
        return $this;
    }

    public function getFormattedAddress(): ?string
    {
        return $this->formattedAddress;
    }

    public function setFormattedAddress(?string $formattedAddress): self
    {
        $this->formattedAddress = $formattedAddress;
        return $this;
    }

    public function getHouseSystem(): string
    {
        return $this->houseSystem;
    }

    public function setHouseSystem(string $houseSystem): self
    {
        $this->houseSystem = $houseSystem;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getLocalTimestamp(): int
    {
        return strtotime("{$this->birthDate} {$this->birthTime}");
    }

    public function getUtcTimestamp(): int
    {
        return $this->getLocalTimestamp() - $this->utcOffset;
    }

    public static function fromArray(array $data): self
    {
        $horoscope = new self(
            (int) $data['user_id'],
            $data['lastname'],
            $data['birth_date'],
            $data['birth_time'],
            $data['location_name'],
            (float) $data['latitude'],
            (float) $data['longitude'],
            $data['timezone_id'],
            (int) $data['utc_offset'],
            $data['firstname'] ?? null,
            $data['infix'] ?? null
        );

        $horoscope->setId((int) $data['id']);

        if (!empty($data['slug'])) {
            $horoscope->setSlug($data['slug']);
        }

        if (!empty($data['time_correction'])) {
            $horoscope->setTimeCorrection($data['time_correction']);
        }

        if (!empty($data['offset_source'])) {
            $horoscope->setOffsetSource($data['offset_source']);
        }

        if (!empty($data['offset_label'])) {
            $horoscope->setOffsetLabel($data['offset_label']);
        }

        if (!empty($data['formatted_address'])) {
            $horoscope->setFormattedAddress($data['formatted_address']);
        }

        if (!empty($data['house_system'])) {
            $horoscope->setHouseSystem($data['house_system']);
        }

        if (!empty($data['created_at'])) {
            $horoscope->setCreatedAt(new \DateTime($data['created_at']));
        }

        if (!empty($data['updated_at'])) {
            $horoscope->setUpdatedAt(new \DateTime($data['updated_at']));
        }

        return $horoscope;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'user_id' => $this->userId,
            'firstname' => $this->firstname,
            'infix' => $this->infix,
            'lastname' => $this->lastname,
            'name' => $this->getFullName(),
            'birth_date' => $this->birthDate,
            'birth_time' => $this->birthTime,
            'location_name' => $this->locationName,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone_id' => $this->timezoneId,
            'utc_offset' => $this->utcOffset,
            'time_correction' => $this->timeCorrection,
            'offset_source' => $this->offsetSource,
            'offset_label' => $this->offsetLabel,
            'formatted_address' => $this->formattedAddress,
            'house_system' => $this->houseSystem,
            'created_at' => $this->createdAt?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt?->format('Y-m-d H:i:s'),
        ];
    }
}