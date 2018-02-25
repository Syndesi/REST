<?php
namespace Syndesi\REST;
require_once '../vendor/autoload.php';


try{
  $router = new Router(new ClientRequest());
  $router->setRoute('GET', 'demo/', function($request, $args){$request->finish('demo');}, 'demo route');
  $router->setRoute('GET', 'regex/{r:a}/', function($request, $args){$request->finish($args);}, 'demo regex route');
  $router->resolve();
  //$apiName = $r->api;
  //if(!ctype_alnum($apiName)){
  //  throw new Exception('The API-name must be alphanumeric.');
  //}
  //$apiPath = 'route/'.$apiName.'Route.php';
  //if(file_exists($apiPath)){
  //  include_once($apiPath);
  //  $apiName = ucfirst($apiName.'Route');
  //  $a = new $apiName($r);
  //  $a->resolveRoute($r->route);
  //} else {
  //  throw new Exception('This API does not exist.');
  //}
} catch(Exception $e){
  $request->abort($e->getCode(), $e->getMessage());
}

exit;