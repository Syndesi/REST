<?php
namespace Syndesi\REST;

/**
 * This class standartizes the work with sessions.
 */
class Session {

  /**
   * Starts a new session
   */
  public function __construct(){
    session_start();
  }

  /**
   * Destroys the current session and deletes all variables.
   */
  public function destroy(){
    session_unset();
    session_destroy();
  }

  /**
   * Restarts the current session so that no variable is set anymore.
   */
  public function restart(){
    $this->destroy();
    session_start();
  }

  /**
   * Saves a variable to the session.
   * @param string   $id    The id/key of the saved variable
   * @param variable $value The value of the saved variable
   */
  public function set($id, $value){
    $_SESSION[$id] = $v;
  }

  /**
   * Returns a saved variable or false if it does not exist.
   * @param  string   $id The key of the wanted variable
   * @return variable     The value of the wanted variable
   */
  public function get($id){
    if($this->is($id)){
      return $_SESSION[$id];
    }
    return false;
  }

  /**
   * Checks if the given key is saved in the current session.
   * @param  string  $id The key of the variable
   * @return boolean     True: The variable exists, False: It does not exist
   */
  public function is($id){
    return isset($_SESSION[$id]);
  }

  /**
   * Removes a variable from the session.
   * @param  string $id The key of the variable
   * @return boolean    True: The variable does not exist anymore, False: An error occurred
   */
  public function unset($id){
    if($this->is($id)){
      unset($_SESSION[$id]);
    }
    return !$this->is($id);
  }

}

?>