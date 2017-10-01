<?php
require_once '../vendor/autoload.php';
require_once 'lib/request.php';


try{
  $r = new \lib\Request();
  $apiName = $r->api;
  if(!ctype_alnum($apiName)){
    throw new Exception('The API-name must be alphanumeric.');
  }
  $apiPath = 'route/'.$apiName.'Route.php';
  if(file_exists($apiPath)){
    include_once($apiPath);
    $apiName = ucfirst($apiName.'Route');
    $a = new $apiName($r);
    $a->resolveRoute($r->route);
  } else {
    throw new Exception('This API does not exist.');
  }
} catch(Exception $e){
  $r->abort($e->getCode(), $e->getMessage());
}

exit;

?>