<?php

namespace didntpott\EcoAPI\commands;

use didntpott\EcoAPI\EcoAPI;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;

class TokenCommand extends Command
{
    public function __construct()
    {
        parent::__construct("token", "Check your tokens", "/token", ["tokens"]);
        $this->setPermission("economy.commands.default");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("§cThis command can only be used in-game.");
            return true;
        }

        $economy = EcoAPI::getInstance()->getEconomy();
        $tokens = $economy->formatCurrency($economy->getTokens($sender));

        $sender->sendMessage("§aYour tokens: §e" . $tokens);
        return true;
    }
}