# Simple DeVault JSON-RPC client based on GuzzleHttp

[![Latest Stable Version](https://poser.pugx.org/protean/php-devaultrpc/v/stable)](https://packagist.org/packages/protean/php-devaultrpc)
[![License](https://poser.pugx.org/protean/php-devaultrpc/license)](https://packagist.org/packages/protean/php-devaultrpc)
[![Build Status](https://travis-ci.org/proteanmusic/php-devaultrpc.svg)](https://travis-ci.org/proteanmusic/php-devaultrpc)
[![Code Climate](https://codeclimate.com/github/proteanmusic/php-devaultrpc/badges/gpa.svg)](https://codeclimate.com/github/proteanmusic/php-devaultrpc)
[![Code Coverage](https://codeclimate.com/github/proteanmusic/php-devaultrpc/badges/coverage.svg)](https://codeclimate.com/github/proteanmusic/php-devaultrpc/coverage)
[![Join the chat at https://gitter.im/php-devaultrpc/Lobby](https://badges.gitter.im/php-devaultrpc/Lobby.svg)](https://gitter.im/php-devaultrpc/Lobby?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

## Installation
Run ```php composer.phar require protean/php-devaultrpc``` in your project directory or add following lines to composer.json
```javascript
"require": {
    "protean/php-devaultrpc": "^2.1"
}
```
and run ```php composer.phar install```.

## Requirements
PHP 7.1 or higher  
_For PHP 5.6 and 7.0 use [php-devaultrpc v2.0.x](https://github.com/proteanmusic/php-devaultrpc/tree/2.0.x)._

## Usage
Create new object with url as parameter
```php
/**
 * Don't forget to include composer autoloader by uncommenting line below
 * if you're not already done it anywhere else in your project.
 **/
// require 'vendor/autoload.php';

use Protean\DeVault\Client as DeVaultClient;

$devaultd = new DeVaultClient('http://rpcuser:rpcpassword@localhost:3339/');
```
or use array to define your devaultd settings
```php
/**
 * Don't forget to include composer autoloader by uncommenting line below
 * if you're not already done it anywhere else in your project.
 **/
// require 'vendor/autoload.php';

use Protean\DeVault\Client as DeVaultClient;

$devaultd = new DeVaultClient([
    'scheme'        => 'http',                 // optional, default http
    'host'          => 'localhost',            // optional, default localhost
    'port'          => 3339,                   // optional, default 3339
    'user'          => 'rpcuser',              // required
    'password'      => 'rpcpassword',          // required
    'ca'            => '/etc/ssl/ca-cert.pem'  // optional, for use with https scheme
    'preserve_case' => false,                  // optional, send method names as defined instead of lowercasing them
]);
```
Then call methods defined in [DeVault Core API Documentation](https://devault.org/en/developer-reference#devault-core-apis) with magic:
```php
/**
 * Get block info.
 */
$block = $devaultd->getBlock('000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

$block('hash')->get();     // 000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f
$block['height'];          // 0 (array access)
$block->get('tx.0');       // 4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b
$block->count('tx');       // 1
$block->has('version');    // key must exist and CAN NOT be null
$block->exists('version'); // key must exist and CAN be null
$block->contains(0);       // check if response contains value
$block->values();          // array of values
$block->keys();            // array of keys
$block->random(1, 'tx');   // random block txid
$block('tx')->random(2);   // two random block txid's
$block('tx')->first();     // txid of first transaction
$block('tx')->last();      // txid of last transaction

/**
 * Send transaction.
 */
$result = $devaultd->sendToAddress('mmXgiR6KAhZCyQ8ndr2BCfEq1wNG2UnyG6', 0.1);
$txid = $result->get();

/**
 * Get transaction amount.
 */
$result = $devaultd->listSinceBlock();
$devault = $result->sum('transactions.*.amount');
$satoshi = \Protean\DeVault\to_satoshi($devault);
```
To send asynchronous request, add Async to method name:
```php
$devaultd->getBlockAsync(
    '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f',
    function ($response) {
        // success
    },
    function ($exception) {
        // error
    }
);
```

You can also send requests using request method:
```php
/**
 * Get block info.
 */
$block = $devaultd->request('getBlock', '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f');

$block('hash');            // 000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f
$block['height'];          // 0 (array access)
$block->get('tx.0');       // 4a5e1e4baab89f3a32518a88c31bc87f618f76673e2cc77ab2127b7afdeda33b
$block->count('tx');       // 1
$block->has('version');    // key must exist and CAN NOT be null
$block->exists('version'); // key must exist and CAN be null
$block->contains(0);       // check if response contains value
$block->values();          // get response values
$block->keys();            // get response keys
$block->first('tx');       // get txid of the first transaction
$block->last('tx');        // get txid of the last transaction
$block->random(1, 'tx');   // get random txid

/**
 * Send transaction.
 */
$result = $devaultd->request('sendtoaddress', 'mmXgiR6KAhZCyQ8ndr2BCfEq1wNG2UnyG6', 0.06);
$txid = $result->get();

```
or requestAsync method for asynchronous calls:
```php
$devaultd->requestAsync(
    'getBlock',
    '000000000019d6689c085ae165831e934ff763ae46a2a6c172b3f1b60a8ce26f',
    function ($response) {
        // success
    },
    function ($exception) {
        // error
    }
);
```

## Multi-Wallet RPC
You can use `wallet($name)` function to do a [Multi-Wallet RPC call](https://en.devault.it/wiki/API_reference_(JSON-RPC)#Multi-wallet_RPC_calls):
```php
/**
 * Get wallet2.dat balance.
 */
$balance = $devaultd->wallet('wallet2.dat')->getbalance();

echo $balance->get(); // 0.10000000
```

## Exceptions
* `Protean\DeVault\Exceptions\BadConfigurationException` - thrown on bad client configuration.
* `Protean\DeVault\Exceptions\BadRemoteCallException` - thrown on getting error message from daemon.
* `Protean\DeVault\Exceptions\ConnectionException` - thrown on daemon connection errors (e. g. timeouts)


## Helpers
Package provides following helpers to assist with value handling.
#### `to_devault()`
Converts value in satoshi to devault.
```php
echo Protean\DeVault\to_devault(100000); // 0.00100000
```
#### `to_satoshi()`
Converts value in devault to satoshi.
```php
echo Protean\DeVault\to_satoshi(0.001); // 100000
```
#### `to_ubtc()`
Converts value in devault to ubtc/bits.
```php
echo Protean\DeVault\to_ubtc(0.001); // 1000.0000
```
#### `to_mbtc()`
Converts value in devault to mbtc.
```php
echo Protean\DeVault\to_mbtc(0.001); // 1.0000
```
#### `to_fixed()`
Trims float value to precision without rounding.
```php
echo Protean\DeVault\to_fixed(0.1236, 3); // 0.123
```

## License

This product is distributed under MIT license.

## Donations

If you like this project, please consider donating:<br>
**BTC**: 3L6dqSBNgdpZan78KJtzoXEk9DN3sgEQJu<br>
**Bech32**: bc1qyj8v6l70c4mjgq7hujywlg6le09kx09nq8d350

❤Thanks for your support!❤
