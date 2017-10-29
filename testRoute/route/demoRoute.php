<?php
set_time_limit(0);

require_once __DIR__.'/../lib/route.php';
use Propel\Runtime\Propel;

class DemoRoute extends \Syndesi\rest\Route {

  public function __construct($r){
    parent::__construct($r);
    $this->addRoute('GET:/demo', function($p){$this->demo();});
  }

  private function demo(){
    $this->r->finish('demo');
  }

}

?>