<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\EcoAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class BalanceCommand extends Command
{
    public function __construct()
    {
        parent::__construct("balance", "Check your balance", "/balance", ["bal", "money"]);
        $this->setPermission("economy.commands.default");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can only be used in-game.");
            return true;
        }

        $api = EcoAPI::getInstance();
        $balance = $api->getEconomy()->formatCurrency($api->getEconomy()->getBalance($sender));

        $sender->sendMessage("§aYour balance: §e" . $balance);
        return true;
    }
}