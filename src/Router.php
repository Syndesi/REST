<?php
namespace Syndesi\REST;

/**
 * A small router which supports static and variable routes (via regex).
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

  /**
   * A small router which supports static and variable routes, later defined by regex.
   * @param ClientRequest $request Used in order to resolve the called route.
   */
  public function __construct(ClientRequest $request){
    $this->request = $request;
    $this->setRoute('GET', '', function($request, $args){$this->help($request, $args);}, 'List of all functions which are supported at this API-level.');
  }

  /**
   * Tries to resolve the URL from it's internal request to any of the registered routes.
   * The resolved route is then called and the execution is not stoped (but can be by e.g. `$this->request->finsih(...)`).
   */
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
  }

  /**
   * Compares a single level. Non-static parts (regex) is supported.
   * @param  string $pathLevel The level of the route, e.g. `{variable-name:regex-code}`.
   * @param  string $urlLevel  The level of the URL, e.g. `page`.
   * @return *                 Different formats, e.g. boolean for static routes and false/array for regex-routes.
   */
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

  /**
   * Checks if a route is registered.
   * @param  string  $method The HTTP-method under which this route is registrated.
   * @param  string  $path   The plain path which is used by this route.
   * @return boolean         True: The route is registered. False: It is not.
   */
  public function isRoute($method, $path){
    $method = strtoupper($method);
    if(!$this->isMethod($method)){
      return false;
    }
    return array_key_exists($path, $this->routes[$method]);
  }

  /**
   * Returns a route by it's method and plain path (regex is not checked).
   * @param  string $method The HTTP-method under which this route is registrated.
   * @param  string $path   The plain path which is used by this route.
   * @return array          An array with the function and a brief explanation about this route.
   */
  public function getRoute($method, $path){
    $method = strtoupper($method);
    if(!$this->isRoute($method, $path)){
      throw new \Exception('The route ['.$method.'://'.$path.'] does not exist');
    }
    return $this->routes[$method][$path];
  }

  /**
   * Adds a new route to this router.
   * @param  string   $method      The type of HTTP-method which should trigger this route.
   * @param  string   $path        The path which should trigger this function. E.g.: `static/route/with/{regex:a}`
   * @param  function $function    The function which should be called when this route is triggered.
   * @param  string   $description A brief explanation for this route. It's returned e.g. by the help-route.
   * @return Router                This.
   */
  public function setRoute($method, $path, $function, $description){
    $method = strtoupper($method);
    if(!$this->isMethod($method)){
      $this->routes[$method] = [];
    }
    $this->routes[$method][trim($path, '/')] = [
      'function'    => $function,
      'description' => $description
    ];
    return $this;
  }

  /**
   * Checks if routes with the given method (e.g. 'GET') exist or not.
   * @param  string  $method The type of HTTP-method.
   * @return boolean         True: There are routes with this type of method. False: This method is not used by any route.
   */
  public function isMethod($method){
    return array_key_exists(strtoupper($method), $this->routes);
  }

  /**
   * Default helper function.
   * Returns all registrated functions and is called by default every time no other route matched.
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