<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\EcoAPI;
use didntpott\EcoAPI\utils\MessageHandler;
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
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.console-only"));
            return true;
        }

        $api = EcoAPI::getInstance();
        $balance = $api->getEconomy()->formatCurrency($api->getEconomy()->getBalance($sender));

        MessageHandler::getInstance()->sendMessage($sender, "balance.show", [
            "balance" => $balance
        ]);

        return true;
    }
}