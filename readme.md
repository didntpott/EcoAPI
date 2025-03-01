# EcoAPI Plugin

A powerful and optimized economy management plugin for PocketMine 5 servers.

## Features

- **Performance Optimized**: Uses an in-memory caching system to drastically reduce database queries
- **Multiple Currency Types**: Supports balance, tokens, and multipliers
- **Command Interface**: Complete command system for managing player economies
- **Developer API**: Simple and intuitive API for other plugins to integrate with the economy system
- **Database Persistence**: All data saved in SQLite database for reliability
- **Flexible Structure**: Well-organized code structure following best practices

## Commands

- `/economy` - Shows available economy commands
- `/economy give <player> <amount>` - Add money to a player's balance
- `/economy take <player> <amount>` - Remove money from a player's balance
- `/economy set <player> <amount>` - Set a player's balance to a specific amount
- `/economy reset <player>` - Reset a player's economy data
- `/economy info <player>` - View a player's economy information

## Permissions

- `economy.command` - Access to the main /economy command
- `economy.command.give` - Permission to give money to others
- `economy.command.take` - Permission to take money from others
- `economy.command.set` - Permission to set other players' balance
- `economy.command.reset` - Permission to reset player economy data
- `economy.command.info` - Permission to view other players' economy data

## Configuration

Edit the `config.yml` file to customize:

```yaml
# Performance Settings
use-cache: true  # Enable caching for better performance

# Default Economy Values
starting-balance: 1000.0  # Starting balance for new players
starting-tokens: 0.0  # Starting tokens for new players

# Database Settings
database-type: sqlite  # Currently only sqlite is supported

# Format Settings
currency-symbol: "$"  # Currency symbol for formatting
```

## Installation

1. Download the plugin
2. Place in your server's `plugins` folder
3. Restart the server
4. Edit `config.yml` to customize settings

## Directory Structure

```
CustomEconomy/
├── plugin.yml
├── README.md
├── resources/
│   └── config.yml
└── src/
    └── didntpott/
        └── EcoAPI/
            ├── API.php
            ├── EcoAPI.php
            ├── command/
            │   └── EconomyCommand.php
            └── provider/
                └── Economy.php
```

## Developer API

### Quick Start

```php
use didntpott\EcoAPI\API;

// Get the API instance
$economyAPI = API::getInstance();

// Get player balance
$balance = $economyAPI->getMoney($player);

// Add money to player
$economyAPI->addMoney($player, 100);

// Take money from player
if ($economyAPI->reduceMoney($player, 50)) {
    // Success
} else {
    // Player doesn't have enough
}

// Transfer money between players
$economyAPI->transferMoney($sender, $receiver, 75);
```

### Available Methods

#### Balance Operations
```php
// Get player balance
$balance = $economyAPI->getMoney($player);

// Add to player balance
$economyAPI->addMoney($player, $amount);

// Reduce player balance (returns false if not enough)
$success = $economyAPI->reduceMoney($player, $amount);

// Set player balance
$economyAPI->setMoney($player, $amount);

// Transfer money between players
$success = $economyAPI->transferMoney($sender, $receiver, $amount);
```

#### Token Operations
```php
// Get player tokens
$tokens = $economyAPI->getTokens($player);

// Add tokens
$economyAPI->addTokens($player, $amount);

// Reduce tokens (returns false if not enough)
$success = $economyAPI->reduceTokens($player, $amount);

// Set tokens
$economyAPI->setTokens($player, $amount);
```

#### Multiplier Operations
```php
// Get player multiplier
$multiplier = $economyAPI->getMultiplier($player);

// Set player multiplier
$economyAPI->setMultiplier($player, $value);
```

#### Formatting
```php
// Format currency with proper formatting
$formatted = $economyAPI->formatMoney($amount); // Example: "1,234.56"
```