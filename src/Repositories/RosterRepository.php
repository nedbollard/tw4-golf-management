<?php

namespace App\Repositories;

use App\Core\Database;
use App\Models\Roster;

class RosterRepository
{
    private const ACTIVE_STATUSES = ['active', 'scored'];

    public function __construct(private Database $db)
    {
    }

    public function findById(int $playerId): ?Roster
    {
        $data = $this->db->fetchOne(
            'SELECT * FROM roster WHERE row_id = ? AND status IN (?, ?)',
            [$playerId, ...self::ACTIVE_STATUSES]
        );
        return $data ? Roster::fromArray($data) : null;
    }

    public function findByIdentifier(string $identifier): ?Roster
    {
        $data = $this->db->fetchOne(
            'SELECT * FROM roster WHERE player_identifier = ? AND status IN (?, ?)',
            [$identifier, ...self::ACTIVE_STATUSES]
        );
        return $data ? Roster::fromArray($data) : null;
    }

    public function findByAlias(string $alias): ?Roster
    {
        $data = $this->db->fetchOne(
            'SELECT * FROM roster WHERE alias = ? AND status IN (?, ?)',
            [$alias, ...self::ACTIVE_STATUSES]
        );
        return $data ? Roster::fromArray($data) : null;
    }

    public function findAll(): array
    {
        $data = $this->db->fetchAll(
            'SELECT * FROM roster WHERE status IN (?, ?) ORDER BY first_name, last_name',
            self::ACTIVE_STATUSES
        );
        return array_map([Roster::class, 'fromArray'], $data);
    }

    public function search(string $query): array
    {
        $searchTerm = "%{$query}%";
        $data = $this->db->fetchAll(
            'SELECT * FROM roster WHERE
             (player_identifier LIKE ? OR alias LIKE ? OR first_name LIKE ? OR last_name LIKE ?)
             AND status IN (?, ?) ORDER BY first_name, last_name',
            [$searchTerm, $searchTerm, $searchTerm, $searchTerm, ...self::ACTIVE_STATUSES]
        );
        return array_map([Roster::class, 'fromArray'], $data);
    }

    public function save(Roster $roster): int
    {
        $data = [
            'player_identifier' => $roster->getPlayerIdentifier(),
            'first_name' => $roster->getFirstName(),
            'last_name' => $roster->getLastName(),
            'alias' => $roster->getAlias(),
            'gender' => $roster->getGender(),
            'handicap' => $roster->getHandicap(),
            'status' => $roster->getStatus(),
        ];

        if ($roster->getPlayerId() === null) {
            $roster->assignPlayerId($this->db->insert('roster', $data));
        } else {
            $this->db->update('roster', $data, ['row_id' => $roster->getPlayerId()]);
        }

        return (int) $roster->getPlayerId();
    }

    public function deactivate(Roster $roster): bool
    {
        if ($roster->getPlayerId() === null) {
            return false;
        }
        if ($this->db->update('roster', ['status' => 'inactive'], ['row_id' => $roster->getPlayerId()]) < 1) {
            return false;
        }
        $roster->deactivate();
        return true;
    }

    public function activate(Roster $roster): bool
    {
        if ($roster->getPlayerId() === null) {
            return false;
        }
        if ($this->db->update('roster', ['status' => 'active'], ['row_id' => $roster->getPlayerId()]) < 1) {
            return false;
        }
        $roster->activate();
        return true;
    }
}