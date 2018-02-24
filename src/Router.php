<?php
namespace Syndesi\REST;

/**
 * A small router.
 * Inspired by nikic´s {@link http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html tutorial}.
 */
class Router {

  protected $routes       = [];
  protected $defaultRoute = null;
  protected $request      = null;
  protected $regex        = [
    's'  => ':[a-zA-Z0-9+_\-\.]+}',  // string
    'h'  => ':[0-9A-Fa-f]+}',        // hex
    'i'  => ':[0-9]+}',              // int
    'f'  => ':[0-9.]+}',             // float
    't'  => ':[0-9-:.]+}'            // time and date values
  ];

  public function __construct(ClientRequest $request){
    $this->request = $request;
    $this->setRoute('GET', '', new Route(function(){$this->help();}, 'List of all functions which are supported at this API-level.'));
  }

  protected function help(){
    $help = [];
    foreach($this->routes as $method => $tmp){
      foreach($tmp as $path => $route){
        $help[$method.'://'.$path] = $route->getDescription();
      }
    }
    $this->request->finish($help);
  }

  public function run(){
    $this->help();
  }

  public function isRoute($method, $path){
    $method = strtoupper($method);
    if(!$this->isMethod($method)){
      return false;
    }
    return array_key_exists($path, $this->routes[$method]);
  }

  public function getRoute($method, $path){
    $method = strtoupper($method);
    if(!$this->isRoute($method, $path)){
      throw new \Exception('The route ['.$method.'://'.$path.'] does not exist');
    }
    return $this->routes[$method][$path];
  }

  public function setRoute($method, $path, Route $route){
    $method = strtoupper($method);
    if(!$this->isMethod($method)){
      $this->routes[$method] = [];
    }
    $this->routes[$method][$path] = $route;
  }

  public function isMethod($method){
    return array_key_exists(strtoupper($method), $this->routes);
  }

}