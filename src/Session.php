<?php
namespace Syndesi;

class Session{

  public function __construct(){
    session_start();
  }

  public function destroy(){
    session_unset();
    session_destroy();
  }

  public function restart(){
    $this->destroy();
    session_start();
  }

  public function set($id, $v){
    $_SESSION[$id] = $v;
  }

  public function get($id){
    if(isset($_SESSION[$id])){
      return $_SESSION[$id];
    }
    return false;
  }

}

?>