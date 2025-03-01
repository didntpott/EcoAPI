<?php

namespace didntpott\EcoAPI\provider;

use didntpott\EcoAPI\EcoAPI;
use InvalidArgumentException;
use pocketmine\player\Player;
use RuntimeException;
use SQLite3;

class Economy
{

    private const ALLOWED_FIELDS = ['balance', 'tokens', 'multiplier'];

    private SQLite3 $db;
    private EcoAPI $plugin;
    private float $startingBalance;
    private float $startingTokens;

    public function __construct(EcoAPI $plugin, float $startingBalance = 0.0, float $startingTokens = 0.0)
    {
        $this->plugin = $plugin;
        $this->db = $plugin->getDatabase();
        $this->startingBalance = $startingBalance;
        $this->startingTokens = $startingTokens;
    }

    public function getMultiplier(Player $player): float
    {
        return $this->getPlayerField($player, 'multiplier', 1.0);
    }

    public function getPlayerField(Player $player, string $field, float $default = 0.0): float
    {
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new InvalidArgumentException("Invalid field: $field");
        }

        $playerName = strtolower($player->getName());

        if ($this->plugin->isCacheEnabled()) {
            $cachedValue = $this->plugin->getFromCache($playerName, $field);
            if ($cachedValue !== 0.0 || isset($this->plugin->getPlayerCache()[$playerName])) {
                return $cachedValue;
            }
        }

        $stmt = $this->db->prepare("SELECT $field FROM player WHERE player_name = :player_name");
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare statement to retrieve field `$field`.");
        }
        $stmt->bindValue(":player_name", $playerName, SQLITE3_TEXT);

        $result = $stmt->execute();
        if (!$result) {
            throw new RuntimeException("Failed to execute statement while retrieving field `$field`.");
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);
        if ($row === false || !isset($row[$field])) {
            // Update cache with default value
            if ($this->plugin->isCacheEnabled()) {
                $this->plugin->updateCache($playerName, $field, $default);
            }
            return $default;
        }

        $value = (float)$row[$field];

        if ($this->plugin->isCacheEnabled()) {
            $this->plugin->updateCache($playerName, $field, $value);
        }

        return $value;
    }

    public function setMultiplier(Player $player, float $multiplier): void
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException("Multiplier cannot be negative.");
        }
        $this->updatePlayerField($player, 'multiplier', $multiplier);
    }

    public function updatePlayerField(Player $player, string $field, float $value): bool
    {
        if (!in_array($field, self::ALLOWED_FIELDS, true)) {
            throw new InvalidArgumentException("Invalid field: $field");
        }

        $playerName = strtolower($player->getName());

        if ($this->plugin->isCacheEnabled()) {
            $this->plugin->updateCache($playerName, $field, $value);
        }
        $stmt = $this->db->prepare("UPDATE player SET $field = :value WHERE player_name = :player_name");
        if (!$stmt) {
            throw new RuntimeException("Failed to prepare update statement for field `$field`.");
        }
        $stmt->bindValue(":value", $value, SQLITE3_FLOAT);
        $stmt->bindValue(":player_name", $playerName, SQLITE3_TEXT);
        $stmt->execute();
        if ($this->db->changes() === 0) {
            $stmtInsert = $this->db->prepare("INSERT INTO player (player_name, $field) VALUES (:player_name, :value)");
            if (!$stmtInsert) {
                throw new RuntimeException("Failed to prepare insert statement for field `$field`.");
            }
            $stmtInsert->bindValue(":player_name", $playerName, SQLITE3_TEXT);
            $stmtInsert->bindValue(":value", $value, SQLITE3_FLOAT);
            $stmtInsert->execute();
        }

        return true;
    }

    public function transferBalance(Player $sender, Player $receiver, float $amount): bool
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Transfer amount must be positive.");
        }

        if (!$this->reduceBalance($sender, $amount)) {
            return false;
        }

        $this->addBalance($receiver, $amount);
        return true;
    }

    public function reduceBalance(Player $player, float $amount): bool
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount to reduce must be non-negative.");
        }
        $currentBalance = $this->getBalance($player);
        if ($currentBalance < $amount) {
            return false;
        }
        $this->updatePlayerField($player, 'balance', $currentBalance - $amount);
        return true;
    }

    public function getBalance(Player $player): float
    {
        return $this->getPlayerField($player, 'balance', $this->startingBalance);
    }

    public function addBalance(Player $player, float $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount to add must be non-negative.");
        }
        $newBalance = $this->getBalance($player) + $amount;
        $this->updatePlayerField($player, 'balance', $newBalance);
    }

    public function transferTokens(Player $sender, Player $receiver, float $amount): bool
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException("Transfer amount must be positive.");
        }

        if (!$this->reduceTokens($sender, $amount)) {
            return false;
        }

        $this->addTokens($receiver, $amount);
        return true;
    }

    public function reduceTokens(Player $player, float $amount): bool
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount to reduce must be non-negative.");
        }
        $currentTokens = $this->getTokens($player);
        if ($currentTokens < $amount) {
            return false;
        }
        $this->updatePlayerField($player, 'tokens', $currentTokens - $amount);
        return true;
    }

    public function getTokens(Player $player): float
    {
        return $this->getPlayerField($player, 'tokens', $this->startingTokens);
    }

    public function addTokens(Player $player, float $amount): void
    {
        if ($amount < 0) {
            throw new InvalidArgumentException("Amount to add must be non-negative.");
        }
        $newTokens = $this->getTokens($player) + $amount;
        $this->updatePlayerField($player, 'tokens', $newTokens);
    }

    public function resetPlayerData(Player $player): void
    {
        $this->updatePlayerField($player, 'balance', $this->startingBalance);
        $this->updatePlayerField($player, 'tokens', $this->startingTokens);
        $this->updatePlayerField($player, 'multiplier', 1.0);
    }

    public function formatCurrency(float $amount): string
    {
        return number_format($amount, 2, '.', ',');
    }
}