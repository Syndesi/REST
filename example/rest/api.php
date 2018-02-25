<?php
require_once '../../vendor/autoload.php';
use Syndesi\REST\Router;
use Syndesi\REST\ClientRequest;

// router
$router = new Router(new ClientRequest());

// routes
$router->setRoute('GET',    'get/',             function($r, $a){$r->finish('static get');}, 'static get example');
$router->setRoute('POST',   'post/',            function($r, $a){$r->finish('static post');}, 'static post example');
$router->setRoute('POST',   'post/{number:i}/', function($r, $a){$r->finish(['number' => $a['number']]);}, 'dynamic post example with numbers');
$router->setRoute('POST',   'post/{string:a}/', function($r, $a){$r->finish(['string' => $a['string']]);}, 'dynamic post example with strings');
$router->setRoute('PUT',    'put/',             function($r, $a){$r->finish('static put');}, 'static put example');
$router->setRoute('DELETE', 'delete/',          function($r, $a){$r->finish('static delete');}, 'static delete example');

// resolve
$router->resolve();

/*
The url can be written with and without an ending slash ('/').
Dynamic `levels` are written like this: {variableName:regex}
And there exist some shorthands for basic data types:
  a: string without numbers and special characters
  s: string
  h: hex
  i: int
  f: float/double
  t: time and date values
See Router.php for more informations.

This example creates the following available url's:

{
    "result": {
        "GET://":                 "List of all functions which are supported at this API-level.",
        "GET://get":              "static get example",
        "POST://post":            "static post example",
        "POST://post/{number:i}": "dynamic post example with numbers",
        "POST://post/{string:a}": "dynamic post example with strings",
        "PUT://put":              "static put example",
        "DELETE://delete":        "static delete example"
    },
    "status": "OK",
    "environment": {
        "timestamp": "2018-25-02CET21:00:5355",
        "method": "GET",
        "url": ""
    }
}
 */
