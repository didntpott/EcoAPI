<?php

namespace didntpott\EcoAPI;

use didntpott\EcoAPI\commands\EconomyCommand;
use didntpott\EcoAPI\commands\BalanceCommand;
use didntpott\EcoAPI\commands\PayCommand;
use didntpott\EcoAPI\commands\TokenCommand;
use didntpott\EcoAPI\provider\Economy;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;
use SQLite3;

class EcoAPI extends PluginBase implements Listener
{

    use SingletonTrait;

    private SQLite3 $db;
    private Economy $economy;
    /** @var array<string, array<string, float>> */
    private array $playerCache = [];
    private bool $useCache = true;

    public function onEnable(): void
    {
        $this->saveDefaultConfig();

        $this->useCache = $this->getConfig()->get("use-cache", true);
        $startingBalance = $this->getConfig()->get("starting-balance", 0.0);
        $startingTokens = $this->getConfig()->get("starting-tokens", 0.0);

        $this->initDatabase();

        self::setInstance($this);

        $this->economy = new Economy($this, $startingBalance, $startingTokens);

        // Register commands
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register("ecoapi", new EconomyCommand());
        $commandMap->register("balance", new BalanceCommand());
        $commandMap->register("token", new TokenCommand());
        $commandMap->register("pay", new PayCommand());

        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    private function initDatabase(): void
    {
        @mkdir($this->getDataFolder());
        $this->db = new SQLite3($this->getDataFolder() . "economy.db");
        $this->db->exec("PRAGMA foreign_keys = ON;");
        $this->db->exec("PRAGMA journal_mode = WAL;");
        $this->db->exec("PRAGMA synchronous = NORMAL;");

        $this->db->exec("CREATE TABLE IF NOT EXISTS player (
            player_name TEXT PRIMARY KEY NOT NULL,
            balance REAL DEFAULT 0,
            tokens REAL DEFAULT 0,
            multiplier REAL DEFAULT 1
        )");

        $this->db->exec("CREATE INDEX IF NOT EXISTS player_name_idx ON player (player_name);");
    }

    public function onDisable(): void
    {
        if ($this->useCache) {
            $this->saveAllPlayerData();
        }

        if (isset($this->db)) {
            $this->db->close();
        }
        $this->getLogger()->info("CustomEconomy has been disabled!");
    }

    private function saveAllPlayerData(): void
    {
        foreach ($this->playerCache as $name => $data) {
            $stmt = $this->db->prepare("UPDATE player SET balance = :balance, tokens = :tokens, multiplier = :multiplier WHERE player_name = :player_name");
            if (!$stmt) {
                $this->getLogger()->error("Failed to prepare statement to save player data for $name");
                continue;
            }

            $stmt->bindValue(":balance", $data['balance'], SQLITE3_FLOAT);
            $stmt->bindValue(":tokens", $data['tokens'], SQLITE3_FLOAT);
            $stmt->bindValue(":multiplier", $data['multiplier'], SQLITE3_FLOAT);
            $stmt->bindValue(":player_name", $name, SQLITE3_TEXT);
            $stmt->execute();

            if ($this->db->changes() === 0) {
                $this->getLogger()->warning("No changes detected when saving data for player $name");
            }
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        if (!$this->useCache) return;

        $player = $event->getPlayer();
        $name = strtolower($player->getName());

        // Load player data into cache
        $this->playerCache[$name] = [
            'balance' => $this->economy->getBalance($player),
            'tokens' => $this->economy->getTokens($player),
            'multiplier' => $this->economy->getMultiplier($player)
        ];
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        if (!$this->useCache) return;

        $player = $event->getPlayer();
        $name = strtolower($player->getName());

        $this->savePlayerData($player);
        unset($this->playerCache[$name]);
    }

    private function savePlayerData(Player $player): void
    {
        $name = strtolower($player->getName());
        if (!isset($this->playerCache[$name])) return;

        $data = $this->playerCache[$name];
        $stmt = $this->db->prepare("UPDATE player SET balance = :balance, tokens = :tokens, multiplier = :multiplier WHERE player_name = :player_name");
        if (!$stmt) {
            $this->getLogger()->error("Failed to prepare statement to save player data for $name");
            return;
        }

        $stmt->bindValue(":balance", $data['balance'], SQLITE3_FLOAT);
        $stmt->bindValue(":tokens", $data['tokens'], SQLITE3_FLOAT);
        $stmt->bindValue(":multiplier", $data['multiplier'], SQLITE3_FLOAT);
        $stmt->bindValue(":player_name", $name, SQLITE3_TEXT);
        $stmt->execute();

        if ($this->db->changes() === 0) {
            $stmtInsert = $this->db->prepare("INSERT INTO player (player_name, balance, tokens, multiplier) VALUES (:player_name, :balance, :tokens, :multiplier)");
            if (!$stmtInsert) {
                $this->getLogger()->error("Failed to prepare insert statement for player $name");
                return;
            }

            $stmtInsert->bindValue(":player_name", $name, SQLITE3_TEXT);
            $stmtInsert->bindValue(":balance", $data['balance'], SQLITE3_FLOAT);
            $stmtInsert->bindValue(":tokens", $data['tokens'], SQLITE3_FLOAT);
            $stmtInsert->bindValue(":multiplier", $data['multiplier'], SQLITE3_FLOAT);
            $stmtInsert->execute();
        }
    }

    public function getDatabase(): SQLite3
    {
        return $this->db;
    }

    public function getEconomy(): Economy
    {
        return $this->economy;
    }

    public function getPlayerCache(): array
    {
        return $this->playerCache;
    }

    public function isCacheEnabled(): bool
    {
        return $this->useCache;
    }

    public function updateCache(string $playerName, string $field, float $value): void
    {
        if (!$this->useCache) return;

        $playerName = strtolower($playerName);
        if (!isset($this->playerCache[$playerName])) {
            $this->playerCache[$playerName] = [
                'balance' => 0.0,
                'tokens' => 0.0,
                'multiplier' => 1.0
            ];
        }

        $this->playerCache[$playerName][$field] = $value;
    }

    public function getFromCache(string $playerName, string $field, float $default = 0.0): float
    {
        if (!$this->useCache) return $default;

        $playerName = strtolower($playerName);
        return $this->playerCache[$playerName][$field] ?? $default;
    }
}