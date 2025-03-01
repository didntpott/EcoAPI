<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\API;
use didntpott\EcoAPI\EcoAPI;
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
            $sender->sendMessage("§cThis command can only be used in-game.");
            return true;
        }

        if (count($args) < 2) {
            $sender->sendMessage("§cUsage: /pay <player> <amount>");
            return true;
        }

        $targetName = $args[0];
        $amount = (float)$args[1];

        if ($amount <= 0) {
            $sender->sendMessage("§cAmount must be positive");
            return true;
        }

        $target = EcoAPI::getInstance()->getServer()->getPlayerExact($targetName);
        if ($target === null) {
            $sender->sendMessage("§cPlayer not found: $targetName");
            return true;
        }

        if ($sender->getName() === $target->getName()) {
            $sender->sendMessage("§cYou cannot pay yourself");
            return true;
        }

        $api = API::getInstance();
        if (!$api->transferMoney($sender, $target, $amount)) {
            $sender->sendMessage("§cYou don't have enough money");
            return true;
        }

        $formattedAmount = $api->formatMoney($amount);
        $senderBalance = $api->formatMoney($api->getMoney($sender));
        $targetBalance = $api->formatMoney($api->getMoney($target));

        $sender->sendMessage("§aYou sent §e{$formattedAmount} §ato §e{$target->getName()}§a. Your balance: §e{$senderBalance}");
        $target->sendMessage("§aYou received §e{$formattedAmount} §afrom §e{$sender->getName()}§a. Your balance: §e{$targetBalance}");
        return true;
    }
}