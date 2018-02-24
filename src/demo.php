<?php
namespace Syndesi\REST;
require_once '../vendor/autoload.php';

$r = new ClientRequest;

$router = new Router($r);
$router->run();

$r->finish();