<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\EcoAPI;
use didntpott\EcoAPI\utils\MessageHandler;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class TopBalanceCommand extends Command
{
    private const DEFAULT_LIMIT = 10;

    public function __construct()
    {
        parent::__construct("topbalance", "View players with highest balance", "/topbalance [limit]", ["topbal", "baltop"]);
        $this->setPermission("economy.commands.default");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        $limit = self::DEFAULT_LIMIT;

        if (isset($args[0]) && is_numeric($args[0]) && $args[0] > 0) {
            $limit = (int)$args[0];
            if ($limit > 100) {
                $limit = 100;
            }
        }

        $db = EcoAPI::getInstance()->getDatabase();
        $stmt = $db->prepare("SELECT player_name, balance FROM player ORDER BY balance DESC LIMIT :limit");

        if (!$stmt) {
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.database"));
            return true;
        }

        $stmt->bindValue(":limit", $limit, SQLITE3_INTEGER);
        $result = $stmt->execute();

        if (!$result) {
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("error.database"));
            return true;
        }

        $economy = EcoAPI::getInstance()->getEconomy();
        $topBalance = [];

        $position = 1;
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $topBalance[] = [
                "position" => $position,
                "name" => $row['player_name'],
                "balance" => $row['balance']
            ];
            $position++;
        }

        $sender->sendMessage(MessageHandler::getInstance()->getMessage("topbalance.header", [
            "count" => count($topBalance)
        ]));

        if (empty($topBalance)) {
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("topbalance.empty"));
            return true;
        }

        foreach ($topBalance as $entry) {
            $sender->sendMessage(MessageHandler::getInstance()->getMessage("topbalance.entry", [
                "position" => $entry["position"],
                "player" => $entry["name"],
                "balance" => $economy->formatCurrency($entry["balance"])
            ]));
        }

        return true;
    }
}