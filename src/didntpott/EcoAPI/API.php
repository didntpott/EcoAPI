<?php

namespace didntpott\EcoAPI;

use didntpott\EcoAPI\provider\Economy;
use pocketmine\player\Player;

class API
{

    private static ?API $instance = null;
    private Economy $economy;

    private function __construct()
    {
        $plugin = EcoAPI::getInstance();
        $this->economy = $plugin->getEconomy();
    }

    public static function getInstance(): API
    {
        if (self::$instance === null) {
            self::$instance = new API();
        }
        return self::$instance;
    }

    public function getMoney(Player $player): float
    {
        return $this->economy->getBalance($player);
    }

    public function addMoney(Player $player, float $amount): void
    {
        $this->economy->addBalance($player, $amount);
    }

    public function reduceMoney(Player $player, float $amount): bool
    {
        return $this->economy->reduceBalance($player, $amount);
    }

    public function setMoney(Player $player, float $amount): void
    {
        $this->economy->updatePlayerField($player, 'balance', $amount);
    }

    public function getTokens(Player $player): float
    {
        return $this->economy->getTokens($player);
    }

    public function addTokens(Player $player, float $amount): void
    {
        $this->economy->addTokens($player, $amount);
    }

    public function reduceTokens(Player $player, float $amount): bool
    {
        return $this->economy->reduceTokens($player, $amount);
    }

    public function setTokens(Player $player, float $amount): void
    {
        $this->economy->updatePlayerField($player, 'tokens', $amount);
    }

    public function getMultiplier(Player $player): float
    {
        return $this->economy->getMultiplier($player);
    }

    public function setMultiplier(Player $player, float $multiplier): void
    {
        $this->economy->setMultiplier($player, $multiplier);
    }

    public function transferMoney(Player $sender, Player $receiver, float $amount): bool
    {
        return $this->economy->transferBalance($sender, $receiver, $amount);
    }

    public function formatMoney(float $amount): string
    {
        return $this->economy->formatCurrency($amount);
    }
}