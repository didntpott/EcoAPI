<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\EcoAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class EconomyCommand extends Command
{
    public function __construct()
    {
        parent::__construct("economy", "Manage player economy", "/economy", ["eco"]);
        $this->setPermission("economy.commands");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender->hasPermission("economy.commands")) {
            $sender->sendMessage("§cYou do not have permission to use this commands.");
            return true;
        }

        if (count($args) === 0) {
            $this->showHelp($sender);
            return true;
        }
        $action = strtolower($args[0]);
        if (count($args) < 2) {
            $this->showActionHelp($sender, $action);
            return true;
        }
        $targetName = $args[1];
        $target = EcoAPI::getInstance()->getServer()->getPlayerExact($targetName);

        if ($target === null) {
            $sender->sendMessage("§cPlayer not found: $targetName");
            return true;
        }
        $economy = EcoAPI::getInstance()->getEconomy();
        switch ($action) {
            case "give":
            case "add":
                if (!$sender->hasPermission("economy.command.give")) {
                    $sender->sendMessage("§cYou don't have permission to give money.");
                    return true;
                }

                if (count($args) < 3) {
                    $sender->sendMessage("§cUsage: /economy give <player> <amount>");
                    return true;
                }

                $amount = (float)$args[2];
                if ($amount <= 0) {
                    $sender->sendMessage("§cAmount must be positive");
                    return true;
                }

                $economy->addBalance($target, $amount);
                $newBalance = $economy->formatCurrency($economy->getBalance($target));
                $sender->sendMessage("§aAdded §e" . $economy->formatCurrency($amount) . " §ato §e" . $target->getName() . "§a's balance. New balance: §e" . $newBalance);
                $target->sendMessage("§aYou received §e" . $economy->formatCurrency($amount) . "§a. New balance: §e" . $newBalance);
                break;

            case "take":
            case "remove":
                if (!$sender->hasPermission("economy.command.take")) {
                    $sender->sendMessage("§cYou don't have permission to take money.");
                    return true;
                }

                if (count($args) < 3) {
                    $sender->sendMessage("§cUsage: /economy take <player> <amount>");
                    return true;
                }

                $amount = (float)$args[2];
                if ($amount <= 0) {
                    $sender->sendMessage("§cAmount must be positive");
                    return true;
                }

                if (!$economy->reduceBalance($target, $amount)) {
                    $sender->sendMessage("§c" . $target->getName() . " doesn't have enough balance");
                    return true;
                }

                $newBalance = $economy->formatCurrency($economy->getBalance($target));
                $sender->sendMessage("§aRemoved §e" . $economy->formatCurrency($amount) . " §afrom §e" . $target->getName() . "§a's balance. New balance: §e" . $newBalance);
                $target->sendMessage("§c" . $economy->formatCurrency($amount) . " §cwas taken from your balance. New balance: §e" . $newBalance);
                break;

            case "set":
                if (!$sender->hasPermission("economy.command.set")) {
                    $sender->sendMessage("§cYou don't have permission to set money.");
                    return true;
                }

                if (count($args) < 3) {
                    $sender->sendMessage("§cUsage: /economy set <player> <amount>");
                    return true;
                }

                $amount = (float)$args[2];
                if ($amount < 0) {
                    $sender->sendMessage("§cAmount cannot be negative");
                    return true;
                }

                $economy->updatePlayerField($target, 'balance', $amount);
                $sender->sendMessage("§aSet §e" . $target->getName() . "§a's balance to §e" . $economy->formatCurrency($amount));
                $target->sendMessage("§aYour balance was set to §e" . $economy->formatCurrency($amount));
                break;

            case "reset":
                if (!$sender->hasPermission("economy.command.reset")) {
                    $sender->sendMessage("§cYou don't have permission to reset player data.");
                    return true;
                }

                $economy->resetPlayerData($target);
                $sender->sendMessage("§aReset §e" . $target->getName() . "§a's economy data");
                $target->sendMessage("§aYour economy data has been reset");
                break;

            case "info":
                if (!$sender->hasPermission("economy.command.info") && $sender->getName() !== $target->getName()) {
                    $sender->sendMessage("§cYou don't have permission to view other players' economy data.");
                    return true;
                }

                $balance = $economy->formatCurrency($economy->getBalance($target));
                $tokens = $economy->formatCurrency($economy->getTokens($target));
                $multiplier = $economy->getMultiplier($target);

                $sender->sendMessage("§e--- Economy Info for " . $target->getName() . " ---");
                $sender->sendMessage("§aBalance: §e" . $balance);
                $sender->sendMessage("§aTokens: §e" . $tokens);
                $sender->sendMessage("§aMultiplier: §e" . $multiplier . "x");
                break;

            default:
                $this->showHelp($sender);
                return true;
        }

        return true;
    }

    private function showHelp(CommandSender $sender): void
    {
        $sender->sendMessage("§e--- Economy Command Help ---");
        $sender->sendMessage("§aAvailable actions:");

        if ($sender->hasPermission("economy.command.give")) {
            $sender->sendMessage("§a/economy give <player> <amount> §7- Add money to a player");
        }

        if ($sender->hasPermission("economy.command.take")) {
            $sender->sendMessage("§a/economy take <player> <amount> §7- Take money from a player");
        }

        if ($sender->hasPermission("economy.command.set")) {
            $sender->sendMessage("§a/economy set <player> <amount> §7- Set a player's balance");
        }

        if ($sender->hasPermission("economy.command.reset")) {
            $sender->sendMessage("§a/economy reset <player> §7- Reset a player's economy data");
        }

        $sender->sendMessage("§a/economy info <player> §7- View a player's economy info");
    }

    private function showActionHelp(CommandSender $sender, string $action): void
    {
        switch ($action) {
            case "give":
            case "add":
                $sender->sendMessage("§cUsage: /economy give <player> <amount>");
                break;

            case "take":
            case "remove":
                $sender->sendMessage("§cUsage: /economy take <player> <amount>");
                break;

            case "set":
                $sender->sendMessage("§cUsage: /economy set <player> <amount>");
                break;

            case "reset":
                $sender->sendMessage("§cUsage: /economy reset <player>");
                break;

            case "info":
                $sender->sendMessage("§cUsage: /economy info <player>");
                break;

            default:
                $this->showHelp($sender);
                break;
        }
    }
}