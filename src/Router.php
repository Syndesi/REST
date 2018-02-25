<?php
namespace Syndesi\REST;

/**
 * A small router.
 * Inspired by nikic´s {@link http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html tutorial}.
 */
class Router {

  protected $routes       = [];
  protected $defaultRoute = null;
  public    $request      = null;
  protected $regex        = [
    'a' => '[a-zA-Z]+',           // string without numbers and special characters
    's' => '[a-zA-Z0-9+_\-\.]+',  // string
    'h' => '[0-9A-Fa-f]+',        // hex
    'i' => '[0-9]+',              // int
    'f' => '[0-9.]+',             // float/double
    't' => '[0-9-:.]+'            // time and date values
  ];

  public function __construct(ClientRequest $request){
    $this->request = $request;
    $this->setRoute('GET', '', function($request, $args){$this->help($request, $args);}, 'List of all functions which are supported at this API-level.');
  }

  public function resolve(){
    $possibleRoutes = [];
    $args           = [];
    $url            = explode('/', $this->request->getUrl());
    $method         = $this->request->getMethod();
    $finalRoute     = false;
    if(!$this->isMethod($method)){
      throw new \Exception('The method ['.$method.'] could not be found');
    }
    foreach($this->routes[$method] as $path => $route){
      $args = [];
      $pathLevel = explode('/', $path);
      foreach($pathLevel as $i => $level){
        // variable: {name:regex}
        // static:   string
        if(!array_key_exists($i, $url)){ // break because url is too short
          $possibleRoutes[$path] = $i;
          break;
        }
        $test = $this->compareLevel($pathLevel[$i], $url[$i]);
        if(!$test){                      // break because levels don't match
          $possibleRoutes[$path] = $i;
          break;
        }
        if(is_array($test)){
          $args = array_merge($test);
        }
        if($i + 1 == count($url) && $i + 1 == count($pathLevel)){
          $finalRoute = $route;
          break 2;
        }
      }
    }
    if(!$finalRoute){
      $this->request->abort('no route found :/');
    }
    $route['function']($this->request, $args);



    //$args = [];
    //$this->help($this->request, $args);
  }

  protected function compareLevel($pathLevel, $urlLevel){
    preg_match('/^{.*}$/', $pathLevel, $isRegex);
    if($isRegex){
      // the path requires a regex
      $pathLevel = ltrim($pathLevel, '{');
      $pathLevel = rtrim($pathLevel, '}');
      $pathLevel = explode(':', $pathLevel, 2);
      $variableName = $pathLevel[0];
      $regex        = $pathLevel[1];
      if(array_key_exists($regex, $this->regex)){
        $regex = $this->regex[$regex];
      }
      preg_match('/'.$regex.'/', $urlLevel, $isMatch);
      if(!$isMatch){
        return false;
      }
      if($isMatch[0] != $urlLevel){
        return false;
      }
      return [$variableName => $urlLevel];
    }
    return $pathLevel == $urlLevel; // static route
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

  public function setRoute($method, $path, $function, $description){
    $method = strtoupper($method);
    if(!$this->isMethod($method)){
      $this->routes[$method] = [];
    }
    $this->routes[$method][trim($path, '/')] = [
      'function'    => $function,
      'description' => $description
    ];
  }

  public function isMethod($method){
    return array_key_exists(strtoupper($method), $this->routes);
  }

  /**
   * Default helper function
   */
  protected function help($request, $args){
    $help = [];
    foreach($this->routes as $method => $tmp){
      foreach($tmp as $path => $route){
        $help[$method.'://'.$path] = $route['description'];
      }
    }
    $request->finish($help);
  }

}