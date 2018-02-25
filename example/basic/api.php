<?php
require_once '../../vendor/autoload.php';
use Syndesi\REST\Router;
use Syndesi\REST\ClientRequest;

// creates a new router
$router = new Router(new ClientRequest());

// add some routes
$router->setRoute(
  'GET',                                   // the HTTP-method which should be used (e.g. GET/POST/PUT/DELETE)
  'helloWorld',                            // the path for this route, here it is domain/api/helloWorld
  function($request, $args){               // this function is executed when this route is called
    $request->finish('Hello world! :D');   // finish($object, $description, $status) will send the result to the client and stops the execution
  },
  'Basic Hello-World-Example'              // a brief explanation for this route
);

// tell the router to resolve the routes
$router->resolve();