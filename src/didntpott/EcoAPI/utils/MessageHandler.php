<?php

namespace didntpott\EcoAPI\utils;

use didntpott\EcoAPI\EcoAPI;
use pocketmine\player\Player;

class MessageHandler
{
    private static ?MessageHandler $instance = null;
    private array $messages = [];

    private function __construct()
    {
        $this->loadMessages();
    }

    private function loadMessages(): void
    {
        $config = EcoAPI::getInstance()->getConfig();
        $this->messages = $config->get('messages', []);
    }

    public static function getInstance(): MessageHandler
    {
        if (self::$instance === null) {
            self::$instance = new MessageHandler();
        }
        return self::$instance;
    }

    public function sendMessage(Player $player, string $key, array $params = []): void
    {
        $player->sendMessage($this->getMessage($key, $params));
    }

    public function getMessage(string $key, array $params = []): string
    {
        $message = $this->getNestedKey($this->messages, $key, "Message not found: $key");

        // Replace placeholders
        foreach ($params as $placeholder => $value) {
            $message = str_replace("{{$placeholder}}", $value, $message);
        }

        return $message;
    }

    private function getNestedKey(array $array, string $key, string $default): string
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $nestedKey) {
            if (!isset($current[$nestedKey])) {
                return $default;
            }
            $current = $current[$nestedKey];
        }

        return $current;
    }

    public function reload(): void
    {
        $this->loadMessages();
    }
}