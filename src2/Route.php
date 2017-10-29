<?php
namespace Syndesi\REST;

/**
 * A small router.
 * Inspired by nikic´s {@link http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html tutorial}.
 */
class Route {

  private $r = null;                  // the request-object
  private $routes = [];               // an array of all registered routes
  private $regexShortcuts = [         // an array of all available "variable types"
    ':i}'  => ':[0-9]+}',             //   int
    ':a}'  => ':[0-9A-Za-z]+}',       //   alphanumeric
    ':h}'  => ':[0-9A-Fa-f]+}',       //   hex
    ':s}'  => ':[a-zA-Z0-9+_\-\.]+}', //   string
    ':d}'  => ':[0-9-]+}',            //   date
    ':t}'  => ':[0-9:]+}',            //   time
    ':dt}' => ':[0-9-:]+}',           //   datetime
    ':f}'  => ':[0-9.]+}'             //   floats
  ];
  private $methods = [                // an array of all possible HTTP-methods
    'GET',
    'POST',
    'PUT',
    'PATCH',
    'DELETE',
    'COPY',
    'HEAD',
    'OPTIONS',
    'LINK',
    'UNLINK',
    'PURGE',
    'LOCK',
    'UNLOCK',
    'PROPFIND',
    'VIEW',
    'TRACE'
  ];

  public function __construct($r){
    $this->r = $r;
    $this->addRoute('OPTIONS:/', function(){$this->getRoutes();}, 'Lists all available routes.');
  }

  //public function addMethod($method){
  //  if($this->isMethod($method)){
  //    $this->methods[] = $method;
  //  }
  //}

  //public function isMethod($method){
  //  return isset($this->methods[$method]);
  //}

  /**
   * Adds a route.
   * @param string $route  The route.
   * @param function $func The function which should run when the route is fired.
   * @param string $desc   The description for the current route.
   */
  public function addRoute($route, $func, $desc = 'No description available.'){
    preg_match('/^('.implode('|', $this->methods).'):\//', $route, $tmp);
    if(count($tmp) > 0){
      $this->routes[$route] = [
        'func' => $func,
        'desc' => $desc
      ];
      return true;
    }
    return false;
  }

  /**
   * Returns a list of all available routes to the client.
   */
  public function getRoutes(){
    $res = [];
    foreach($this->routes as $route => $el){
      $res[] = [$route => $el['desc']];
    }
    $this->r->finish($res);
  }

  /**
   * Resolves the current route.
   * @param  string $route The current route from the client.
   */
  public function resolveRoute($route){
    foreach($this->routes as $i => $el){
      if($this->compareRoute($i, $route)){
        $el['func']($this->getArgs($i, $route));
        $this->requestNotClosed();
      }
    }
    // run this route as the default route
    $this->getRoutes();
  }

  /**
   * This function is executed if the route-function does not end the request (e.g. with finish/abort).
   */
  public function requestNotClosed(){
    $this->r->abort(501, 'WIP');
  }

  /**
   * Returns an array of all variables from the route
   * @param  string $route the "rule" for the route
   * @param  string $check the route from the client
   * @return array         an associative array containing all variables
   */
  public function getArgs($route, $check){
    $args = [];
    $route = explode('/', $route);
    $check = explode('/', $check);
    if(count($route) != count($check)){
      return false;
    }
    foreach($route as $i => $level){
      preg_match('/^{.*}$/', $level, $tmp);
      if(count($tmp) > 0){
        $level = $this->getRule($level);
        if(count($level) == 1){
          // no variable type declared
          $args[$level[0]] = $check[$i];
        } else {
          preg_match('/'.$level[1].'/', $check[$i], $tmp);
          if(count($tmp) > 0){
            if($tmp[0] == $check[$i]){
              $args[$level[0]] = $check[$i];
            }
          }
        }
      }
    }
    return $args;
  }

  /**
   * Checks if the given route fulfills the preset's rules.
   * @param  string  $preset The defined route
   * @param  string  $route  The route from the client
   * @return boolean         True: Both are matching, False: The route does not fulfill the preset
   */
  protected function compareRoute($preset, $route){
    $preset = explode('/', $preset);
    $route = explode('/', $route);
    if(count($preset) != count($route)){
      return false;
    }
    foreach($preset as $i => $level){
      if(!$this->compareLevel($level, $route[$i])){
        return false;
      }
    }
    return true;
  }

  /**
   * Checks if a single level of a route fulfills the requirements of the preset.
   * @param  string  $preset The preset (plain text/regex-rule)
   * @param  string  $route  The level of the client's request
   * @return boolean         True: Both are matching, False: The route does not fulfill the preset
   */
  protected function compareLevel($preset, $route){
    preg_match('/^{.*}$/', $preset, $tmp);
    if(count($tmp) > 0){
      // regex/dynamic level
      $rule = $this->getRule($preset);
      if(count($rule) == 1){
        // no variable type declared
        return true;
      } else {
        preg_match('/'.$rule[1].'/', $route, $tmp); // rule: ['name', 'regexRule']
        if(count($tmp) > 0){
          // check if the matched string is the whole string
          return $tmp[0] == $route;
        }
      }
    }
    return $preset == $route; // static route
  }

  /**
   * Tries to return the name-regexRule pair of a level, if possible
   * @param  string $level The level from which the rule should be returned
   * @return array         ['name', 'regexRule']
   */
  protected function getRule($level){
    $level = strtr($level, $this->regexShortcuts);
    $level = ltrim($level, '{');
    $level = rtrim($level, '}');
    return explode(':', $level, 2);
  }

}

?>