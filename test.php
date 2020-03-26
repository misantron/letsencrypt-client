<?php

declare(strict_types=1);

/*
 * This file is part of the LetsEncrypt ACME client.
 *
 * @author    Ivanov Aleksandr <misantron@gmail.com>
 * @copyright 2019
 * @license   https://github.com/misantron/letsencrypt-client/blob/master/LICENSE MIT License
 */

use LetsEncrypt\Client;

require_once 'vendor/autoload.php';

$keysDirectory = __DIR__ . '/keys';

$client = new Client($keysDirectory, $keysDirectory);
$client->account()->create(['test.account@gmail.com']);
//$client->account()->get();
//$client->account()->update(['ivanov@propellerads.net']);
//$client->account()->deactivate();
