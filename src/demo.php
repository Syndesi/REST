<?php
namespace Syndesi\REST;
require_once '../vendor/autoload.php';


$router = new Router(new ClientRequest());
//$router->request->enableCORS();
$router->setRoute('GET', 'demo', function($request, $args){$request->finish('demo');}, 'demo route');
$router->resolve();