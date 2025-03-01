# EcoAPI

A powerful and optimized economy management plugin for PocketMine 5 servers.

## Features

- **Complete Economy System**: Manage both money and tokens for players
- **Multiplier Support**: Apply earning multipliers to players
- **Performance Optimized**:
    - SQLite database with caching system
    - WAL mode and indexing for fast queries
- **Command System**:
    - Player commands: `/balance`, `/token`, `/pay`
    - Admin commands: `/economy give|take|set|reset|info`
- **Developer API**: Easily integrate with your plugins
- **Permission System**: Fine-grained control over economy commands

## Installation

1. Download the latest release from [GitHub](https://github.com/didntpott/EcoAPI/releases)
2. Place the .phar file in your server's `plugins` folder
3. Restart your server
4. Configure the plugin in `plugins/EcoAPI/config.yml`

## Commands

| Command | Description | Permission | Aliases |
|---------|-------------|------------|---------|
| `/balance` | Check your balance | economy.commands.default | `/bal`, `/money` |
| `/token` | Check your tokens | economy.commands.default | `/tokens` |
| `/pay <player> <amount>` | Send money to another player | economy.commands.default | `/transfer` |
| `/economy give <player> <amount>` | Give money to a player | economy.commands.give | `/eco give` |
| `/economy take <player> <amount>` | Take money from a player | economy.commands.take | `/eco take` |
| `/economy set <player> <amount>` | Set a player's balance | economy.commands.set | `/eco set` |
| `/economy reset <player>` | Reset player's economy data | economy.commands.reset | `/eco reset` |
| `/economy info <player>` | View player's economy info | economy.commands.info | `/eco info` |

## Permissions

| Permission | Description | Default |
|------------|-------------|---------|
| economy.commands.default | Access to basic economy commands | true |
| economy.commands | Access to admin economy commands | op |
| economy.commands.give | Permission to give money | op |
| economy.commands.take | Permission to take money | op |
| economy.commands.set | Permission to set balance | op |
| economy.commands.reset | Permission to reset data | op |
| economy.commands.info | Permission to view economy info | op |

## Configuration (config.yml)

```yaml
# Performance Settings
# Enable caching to improve performance by reducing database queries
use-cache: true

# Default Economy Values
# Starting balance for new players
starting-balance: 1000.0

# Starting tokens for new players
starting-tokens: 0.0

# Database Settings
# Database type (currently only sqlite is supported)
database-type: sqlite

# Format Settings
# Currency symbol to use when formatting amounts
currency-symbol: "$"
```

## For Developers

### API Usage

```php
use didntpott\EcoAPI\API;

// Get the API instance
$api = API::getInstance();

// Money operations
$balance = $api->getMoney($player);
$api->addMoney($player, 100.0);
$api->reduceMoney($player, 50.0);
$api->setMoney($player, 1000.0);

// Token operations
$tokens = $api->getTokens($player);
$api->addTokens($player, 10.0);
$api->reduceTokens($player, 5.0);
$api->setTokens($player, 20.0);

// Other operations
$multiplier = $api->getMultiplier($player);
$api->setMultiplier($player, 1.5);
$api->transferMoney($sender, $receiver, 100.0);
$formattedMoney = $api->formatMoney(1000.0); // Returns "$1,000.00"
```

## Troubleshooting

### Common Issues

1. **Player data not saving**
    - Ensure your server has proper write permissions for the plugins directory
    - Check if the database file `plugins/EcoAPI/economy.db` exists and isn't corrupted
    - Make sure you're using the latest version of the plugin

2. **Commands not working**
    - Verify that permissions are set correctly in your permissions plugin
    - Check console for any error messages

3. **Permission errors**
    - Make sure you've properly configured your permissions plugin
    - Verify that the permission nodes match exactly as specified in this README

## License

This plugin is licensed under the [MIT License](LICENSE).

## Support

For support, please [open an issue](https://github.com/didntpott/EcoAPI/issues) on GitHub.