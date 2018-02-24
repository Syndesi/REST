<?php
namespace Syndesi\REST;

/**
 * A small router.
 * Inspired by nikic´s {@link http://nikic.github.io/2014/02/18/Fast-request-routing-using-regular-expressions.html tutorial}.
 */
class Route {

  private $routes = [];
  private $regexShortcuts = array(
    ':i}'  => ':[0-9]+}',             // int
    ':a}'  => ':[0-9A-Za-z]+}',       // alphanumeric
    ':h}'  => ':[0-9A-Fa-f]+}',       // hex
    ':s}'  => ':[a-zA-Z0-9+_\-\.]+}', // strings
    ':d}'  => ':[0-9-]+}',            // dates
    ':f}'  => ':[0-9.]+}'             // floats
  );

  /**
   * @param Syndesi\Request $r The request-object so that this lib can parse the URL and (sadly) output error codes ;)
   */
  public function __construct($r){
    $this->r = $r;
    $this->addRoute('GET:/', function(){$this->getRoutes();});
  }

  /**
   * Adds a specific route to this class.
   * @example
   * $route = new Route();
   * $route->addRoute('GET:/{id}', function($a){
   *   echo('id: '.$id.'<br>');
   * });
   * @param string   $route the route from this file, e.g. "GET:/user/{id:i}"
   * @param function $func  the function which should be executed if the route is called.
   */
  public function addRoute($route, $func){
    preg_match('/^(GET|POST|DELETE|PUT):\//', $route, $tmp);
    if(count($tmp) > 0){
      $this->routes[$route] = $func;
      return true;
    }
    return false;
  }

  /**
   * Shortcut for addRoute.
   */
  public function get($route, $func){
    return $this->addRoute('GET:/'.$route, $func);
  }

  /**
   * Shortcut for addRoute.
   */
  public function post($route, $func){
    return $this->addRoute('POST:/'.$route, $func);
  }

  /**
   * Shortcut for addRoute.
   */
  public function put($route, $func){
    return $this->addRoute('PUT:/'.$route, $func);
  }

  /**
   * Shortcut for addRoute.
   */
  public function delete($route, $func){
    return $this->addRoute('DELETE:/'.$route, $func);
  }

  /**
   * Returns a list of all activated routes.
   */
  public function getRoutes(){
    $res = [];
    foreach($this->routes as $route => $func){
      $res[] = $route;
    }
    $this->r->finish($res);
  }

  /**
   * Checks if a given route is covered by the classes´ routes and if it is, executes the function behind it.
   * @param  string $route the remaining part of the url
   */
  public function resolveRoute($route){
    foreach($this->routes as $i => $func){
      if($this->compareRoute($i, $route)){
        $func($this->getArgs($i, $route));
        return true;
      }
    }
    $this->defaultRoute($route);
  }

  /**
   * This function is called when no route matched the given route.
   * @param  string $route the route with no matches
   */
  public function defaultRoute($route = ''){
    throw new \Exception('This Route does not exist. You can get a list of all available routes under /api/'.$this->r->api.'/');
  }

  /**
   * Returns an array of all variables, defined by the given route-rule.
   * @param  string $route the rule for this route
   * @param  string $check the actual route from which the variables are extracted
   * @return array         an array containing all variables
   */
  private function getArgs($route, $check){
    $args = [];
    $route = explode('/', $route);
    $check = explode('/', $check);
    if(count($route) != count($check)){
      return false;
    }
    foreach($route as $i => $level){
      preg_match('/^{.*}$/', $level, $tmp);
      if(count($tmp) > 0){
        $level = $this->explodeRegex($level);
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
   * Checks if the route-preset and the actual route are matching.
   * @param  string $preset the route-preset
   * @param  string $route  the actual route
   * @return bool           true: the routes are matching, false: they are different
   */
  private function compareRoute($preset, $route){
    $preset = explode('/', $preset);
    $route = explode('/', $route);
    if(count($preset) != count($route)){
      return false;
    }
    $fits = true;
    foreach($preset as $i => $level){
      if(!$this->compareLevel($level, $route[$i])){
        $fits = false;
      }
    }
    return $fits;
  }

  /**
   * A small helper function to split the regex from the route-preset into an array.
   * @example
   * $tmp = $this->explodeRegex('{id:i}');
   * // $tmp = ['id', '[0-9]+'];
   * @param  string $regex the rule from the route-preset
   * @return array         an array with one/two elements, see example
   */
  private function explodeRegex($regex){
    $regex = strtr($regex, $this->regexShortcuts);
    $regex = ltrim($regex, '{');
    $regex = rtrim($regex, '}');
    return explode(':', $regex, 2); // limit the splitting to 2 elements because ':' could be part of the actual regex
  }

  /**
   * Compares one level of the route-preset with the coresponding level of the actual route to determine if both fit together.
   * @param  string $preset the level of the route-preset
   * @param  string $route  the level of the actual route
   * @return bool           true: both levels are identical/fit together, false: they are different
   */
  private function compareLevel($preset, $route){
    preg_match('/^{.*}$/', $preset, $tmp);
    if(count($tmp) > 0){
      // regex/dynamic level
      $preset = $this->explodeRegex($preset);
      if(count($preset) == 1){
        // no variable type declared
        return true;
      } else {
        preg_match('/'.$preset[1].'/', $route, $tmp); // {0: 'variableName', 1: 'regexPattern'}
        if(count($tmp) > 0){
          // check if the matched string is the whole string
          return $tmp[0] == $route;
        }
      }
    }
    return $preset == $route; // static route
  }

}

?>