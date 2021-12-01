<?php
require '../vendor/autoload.php';

use Onion\Container\Application;

$app = new Application(realpath(dirname(__DIR__)));

$response = $app->kernel->handle($app->request);

$response->send();

