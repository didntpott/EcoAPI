<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\EcoAPI;
use didntpott\EcoAPI\utils\MessageHandler;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class PayCommand extends Command
{
    public function __construct()
    {
        parent::__construct("pay", "Send money to another player", "/pay <player> <amount>", ["transfer"]);
        $this->setPermission("economy.commands.default");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.console-only"));
            return true;
        }

        if (count($args) < 2) {
            MessageHandler::getInstance()->sendMessage($sender, "help.usage-pay");
            return true;
        }

        $targetName = $args[0];
        $amount = (float)$args[1];

        if ($amount <= 0) {
            MessageHandler::getInstance()->sendMessage($sender, "error.amount-positive");
            return true;
        }

        $target = EcoAPI::getInstance()->getServer()->getPlayerExact($targetName);
        if ($target === null) {
            MessageHandler::getInstance()->sendMessage($sender, "error.player-not-found", [
                "player" => $targetName
            ]);
            return true;
        }

        if ($sender->getName() === $target->getName()) {
            MessageHandler::getInstance()->sendMessage($sender, "error.pay-self");
            return true;
        }

        $economy = EcoAPI::getInstance()->getEconomy();
        if (!$economy->transferBalance($sender, $target, $amount)) {
            MessageHandler::getInstance()->sendMessage($sender, "error.insufficient-balance");
            return true;
        }

        $formattedAmount = $economy->formatCurrency($amount);
        $senderBalance = $economy->formatCurrency($economy->getBalance($sender));
        $targetBalance = $economy->formatCurrency($economy->getBalance($target));

        MessageHandler::getInstance()->sendMessage($sender, "pay.success-sender", [
            "amount" => $formattedAmount,
            "player" => $target->getName(),
            "balance" => $senderBalance
        ]);

        MessageHandler::getInstance()->sendMessage($target, "pay.success-receiver", [
            "amount" => $formattedAmount,
            "sender" => $sender->getName(),
            "balance" => $targetBalance
        ]);

        return true;
    }
}