# LetsEncrypt ACME v2 client

[![Build Status](https://img.shields.io/travis/com/misantron/letsencrypt-client.svg?style=flat-square&maxAge=2592000)](https://travis-ci.com/misantron/letsencrypt-client)
[![Code Coverage](https://img.shields.io/coveralls/github/misantron/letsencrypt-client.svg?style=flat-square)](https://coveralls.io/github/misantron/letsencrypt-client)
[![Code Quality](https://img.shields.io/scrutinizer/g/misantron/letsencrypt-client.svg?style=flat-square)](https://scrutinizer-ci.com/g/misantron/letsencrypt-client/)

[ACME v2 draft](https://github.com/ietf-wg-acme/acme/blob/master/draft-ietf-acme-acme.md)

## Installation

Use Composer to install the package:  
```shell script
composer require misantron/letsencrypt-client
```

## Basic usage

Create client instance with defaults:

```php
use LetsEncrypt\Client;

$client = new Client($accountKeysPath, $certificatesPath);
```
By default client uses api staging environment.  
To switch to production mode you can pass third argument to client constructor with `false` value:
```php
use LetsEncrypt\Client;

$client = new Client($accountKeysPath, $certificatesPath, false);
```

### Account methods

* `$client->account()->create(array $emails)`
* `$client->account()->get()`
* `$client->account()->update(array $emails)`
* `$client->account()->keyRollover()`
* `$client->account()->deactive()`

### Order methods

* `$client->order()->create(Account $account, string $basename, array $subjects, Certificate $certificate)`
* `$client->order()->get(string $basename, array $subjects)`
* `$client->order()->getOrCreate(Account $account, string $basename, array $subjects, Certificate $certificate)`
* `$client->order()->getPendingHttpAuthorizations(Account $account, Order $order)`
* `$client->order()->getPendingDnsAuthorizations(Account $account, Order $order)`
* `$client->order()->verifyPendingHttpAuthorizations(Account $account, Order $order, string $identifier)`
* `$client->order()->verifyPendingDnsAuthorizations(Account $account, Order $order, string $identifier)`
* `$client->order()->getCertificate(Account $account, Order $order, string $basename, string $csr)`
* `$client->order()->revokeCertificate(Account $account, string $basename, RevocationReason $reason)`

## Advanced usage

### Logging

If you need to get some debug information or analyze server interaction

```php
use LetsEncrypt\Client;                       
use LetsEncrypt\Logger\Logger;
use LetsEncrypt\Logger\LogStrategy;

$strategy = LogStrategy::errorsOnly();
// $logger is an instance of PSR-3 logger (\Psr\Log\LoggerInterface)
$clientLogger = new Logger($logger, $strategy);
$client = new Client($accountKeysPath, $certificatesPath, true, $clientLogger);
```
### Logger strategy
* `LogStrategy::requestsOnly()` log only requests data
* `LogStrategy::errorsOnly()` log only failed (400/500) requests (request/response data)
* `LogStrategy::debugMode()` log all requests (request/response data)
