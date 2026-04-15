<?php

namespace App\Models;

class CourseClub
{
    private ?int $rowId;
    private string $nameClub;
    private int $numberHole;
    private string $nameHole;
    private string $gender;
    private int $par;
    private int $stroke;
    private string $updatedBy;
    private ?string $updatedTs;

    public function __construct(
        string $nameClub,
        int $numberHole,
        string $nameHole,
        string $gender,
        int $par,
        int $stroke,
        string $updatedBy,
        ?int $rowId = null,
        ?string $updatedTs = null
    ) {
        $this->rowId = $rowId;
        $this->nameClub = $nameClub;
        $this->numberHole = $numberHole;
        $this->nameHole = $nameHole;
        $this->gender = $gender;
        $this->par = $par;
        $this->stroke = $stroke;
        $this->updatedBy = $updatedBy;
        $this->updatedTs = $updatedTs;
    }

    // Getters
    public function getRowId(): ?int
    {
        return $this->rowId;
    }

    public function getNameClub(): string
    {
        return $this->nameClub;
    }

    public function getNumberHole(): int
    {
        return $this->numberHole;
    }

    public function getNameHole(): string
    {
        return $this->nameHole;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function getPar(): int
    {
        return $this->par;
    }

    public function getStroke(): int
    {
        return $this->stroke;
    }

    public function getUpdatedBy(): string
    {
        return $this->updatedBy;
    }

    public function getUpdatedTs(): ?string
    {
        return $this->updatedTs;
    }

    // Setters
    public function setRowId(int $rowId): void
    {
        $this->rowId = $rowId;
    }

    public function setNameClub(string $nameClub): void
    {
        $this->nameClub = $nameClub;
    }

    public function setNumberHole(int $numberHole): void
    {
        $this->numberHole = $numberHole;
    }

    public function setNameHole(string $nameHole): void
    {
        $this->nameHole = $nameHole;
    }

    public function setGender(string $gender): void
    {
        $this->gender = $gender;
    }

    public function setPar(int $par): void
    {
        $this->par = $par;
    }

    public function setStroke(int $stroke): void
    {
        $this->stroke = $stroke;
    }

    public function setUpdatedBy(string $updatedBy): void
    {
        $this->updatedBy = $updatedBy;
    }

    public function setUpdatedTs(string $updatedTs): void
    {
        $this->updatedTs = $updatedTs;
    }

    // Convert to array for database operations
    public function toArray(): array
    {
        return [
            'row_id' => $this->rowId,
            'name_club' => $this->nameClub,
            'number_hole' => $this->numberHole,
            'name_hole' => $this->nameHole,
            'gender' => $this->gender,
            'par' => $this->par,
            'stroke' => $this->stroke,
            'updated_by' => $this->updatedBy,
            'updated_ts' => $this->updatedTs
        ];
    }

    // Create from database row
    public static function fromArray(array $data): self
    {
        return new self(
            $data['name_club'],
            (int) $data['number_hole'],
            $data['name_hole'],
            $data['gender'],
            (int) $data['par'],
            (int) $data['stroke'],
            $data['updated_by'],
            isset($data['row_id']) ? (int) $data['row_id'] : null,
            $data['updated_ts'] ?? null
        );
    }
}
