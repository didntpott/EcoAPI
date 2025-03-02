<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\EcoAPI;
use didntpott\EcoAPI\utils\MessageHandler;
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
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.no-permission"));
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
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.player-not-found", [
                "player" => $targetName
            ]));
            return true;
        }

        $economy = EcoAPI::getInstance()->getEconomy();

        switch ($action) {
            case "give":
            case "add":
                if (!$sender->hasPermission("economy.command.give")) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.no-permission"));
                    return true;
                }

                if (count($args) < 3) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("help.usage-give"));
                    return true;
                }

                $amount = (float)$args[2];
                if ($amount <= 0) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.amount-positive"));
                    return true;
                }

                $economy->addBalance($target, $amount);
                $newBalance = $economy->formatCurrency($economy->getBalance($target));

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.give-success-sender", [
                    "amount" => $economy->formatCurrency($amount),
                    "player" => $target->getName(),
                    "balance" => $newBalance
                ]));

                $target->sendMessage(MessageHandler::getInstance()->getMessage("economy.give-success-receiver", [
                    "amount" => $economy->formatCurrency($amount),
                    "balance" => $newBalance
                ]));
                break;

            case "take":
            case "remove":
                if (!$sender->hasPermission("economy.command.take")) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.no-permission"));
                    return true;
                }

                if (count($args) < 3) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("help.usage-take"));
                    return true;
                }

                $amount = (float)$args[2];
                if ($amount <= 0) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.amount-positive"));
                    return true;
                }

                if (!$economy->reduceBalance($target, $amount)) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.insufficient-target-balance", [
                        "player" => $target->getName()
                    ]));
                    return true;
                }

                $newBalance = $economy->formatCurrency($economy->getBalance($target));

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.take-success-sender", [
                    "amount" => $economy->formatCurrency($amount),
                    "player" => $target->getName(),
                    "balance" => $newBalance
                ]));

                $target->sendMessage(MessageHandler::getInstance()->getMessage("economy.take-success-receiver", [
                    "amount" => $economy->formatCurrency($amount),
                    "balance" => $newBalance
                ]));
                break;

            case "set":
                if (!$sender->hasPermission("economy.command.set")) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.no-permission"));
                    return true;
                }

                if (count($args) < 3) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("help.usage-set"));
                    return true;
                }

                $amount = (float)$args[2];
                if ($amount < 0) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.amount-negative"));
                    return true;
                }

                $economy->updatePlayerField($target, 'balance', $amount);
                $formattedAmount = $economy->formatCurrency($amount);

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.set-success-sender", [
                    "player" => $target->getName(),
                    "amount" => $formattedAmount
                ]));

                $target->sendMessage(MessageHandler::getInstance()->getMessage("economy.set-success-receiver", [
                    "amount" => $formattedAmount
                ]));
                break;

            case "reset":
                if (!$sender->hasPermission("economy.command.reset")) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.no-permission"));
                    return true;
                }

                $economy->resetPlayerData($target);

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.reset-success-sender", [
                    "player" => $target->getName()
                ]));

                $target->sendMessage(MessageHandler::getInstance()->getMessage("economy.reset-success-receiver"));
                break;

            case "info":
                if (!$sender->hasPermission("economy.command.info") && $sender->getName() !== $target->getName()) {
                    $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.no-permission"));
                    return true;
                }

                $balance = $economy->formatCurrency($economy->getBalance($target));
                $tokens = $economy->formatCurrency($economy->getTokens($target));
                $multiplier = $economy->getMultiplier($target);

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.info-header", [
                    "player" => $target->getName()
                ]));

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.info-balance", [
                    "balance" => $balance
                ]));

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.info-tokens", [
                    "tokens" => $tokens
                ]));

                $sender->sendMessage(MessageHandler::getInstance()->getMessage("economy.info-multiplier", [
                    "multiplier" => $multiplier
                ]));
                break;

            default:
                $this->showHelp($sender);
                return true;
        }

        return true;
    }

    private function showHelp(CommandSender $sender): void
    {
        $messageHandler = MessageHandler::getInstance();
        $sender->sendMessage($messageHandler->getMessage("help.header"));
        $sender->sendMessage($messageHandler->getMessage("help.available-actions"));

        if ($sender->hasPermission("economy.command.give")) {
            $sender->sendMessage($messageHandler->getMessage("help.give"));
        }

        if ($sender->hasPermission("economy.command.take")) {
            $sender->sendMessage($messageHandler->getMessage("help.take"));
        }

        if ($sender->hasPermission("economy.command.set")) {
            $sender->sendMessage($messageHandler->getMessage("help.set"));
        }

        if ($sender->hasPermission("economy.command.reset")) {
            $sender->sendMessage($messageHandler->getMessage("help.reset"));
        }

        $sender->sendMessage($messageHandler->getMessage("help.info"));
    }

    private function showActionHelp(CommandSender $sender, string $action): void
    {
        $messageHandler = MessageHandler::getInstance();

        switch ($action) {
            case "give":
            case "add":
                $sender->sendMessage($messageHandler->getMessage("help.usage-give"));
                break;

            case "take":
            case "remove":
                $sender->sendMessage($messageHandler->getMessage("help.usage-take"));
                break;

            case "set":
                $sender->sendMessage($messageHandler->getMessage("help.usage-set"));
                break;

            case "reset":
                $sender->sendMessage($messageHandler->getMessage("help.usage-reset"));
                break;

            case "info":
                $sender->sendMessage($messageHandler->getMessage("help.usage-info"));
                break;

            default:
                $this->showHelp($sender);
                break;
        }
    }
}