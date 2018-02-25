<?php
namespace Syndesi\REST;

/**
 * A basic Request-object to represent single requests (e.g. from the client).
 */
class Request {

  protected $url;
  protected $method;
  protected $header;
  protected $body;

  /**
   * A basic Request-object to represent single requests (e.g. from the client).
   * @param string|null $url    The URL under which the script is called.
   * @param string      $method The HTTP-method which was used.
   * @param array       $header Array of headers which were used.
   * @param string|null $body   The raw data from the body.
   */
  public function __construct(string $url = null, string $method = 'GET', array $header = [], string $body = null){
    $this->url    = $url;
    $this->method = $method;
    $this->header = $header;
    $this->body   = $body;
    return $this;
  }

  /**
   * Helper to load all client-functions at the same time.
   * @return Request This.
   */
  public function loadClientRequest(){
    $this->loadClientUrl();
    $this->loadClientMethod();
    $this->loadClientHeader();
    $this->loadClientBody();
    return $this;
  }

  /**
   * Warning: This is no replacement for cUrl, it just sends the data to the client.
   * P.S.: It's a good idea to exit the request manually.
   * @return Request This.
   */
  public function send(){
    $this->sendHeader();
    echo($this->body);
    return $this;
  }

  /**
   * Used to send files without additional context/wrappers.
   * @return Request This.
   */
  public function sendRawData($data){
    $this->sendHeader();
    echo($data);
    return $this;
  }

  // URL-functions
  
  /**
   * Returns the URL under which this script is called.
   * @return string The called URL.
   */
  public function getUrl(){
    return $this->url;
  }

  /**
   * Sets the URL of this request.
   * @param string $url The URL under which this script is called.
   */
  public function setUrl(string $url){
    $this->url = $url;
    return $this;
  }

  /**
   * Loads the URL under which the script is called.
   * WARNING: This feature is experimentel, untested and overwritten by the ClientRequest-function.
   * @return Request This.
   */
  public function loadClientUrl(){
    $this->url = $_SERVER['REQUEST_URI'];
    return $this;
  }

  // METHOD-functions

  /**
   * Gets the method of this request.
   * @return string The method, e.g. 'GET'.
   */
  public function getMethod(){
    return $this->method;
  }

  /**
   * Sets the method.
   * @param string $method The method, e.g. 'GET'.
   * @return Request This.
   */
  public function setMethod($method){
    $this->method = strtoupper(trim($method));
    return $this;
  }

  /**
   * Loads the client's method (e.g. GET/POST/PUT/DELETE).
   * @return Request This.
   */
  public function loadClientMethod(){
    $this->method = $_SERVER['REQUEST_METHOD'];
    return $this;
  }

  // HEADER-functions

  /**
   * Checks if a header-value is already stored or not.
   * @param  string  $key The key of the value, e.g. 'Content-Type'.
   * @return boolean      True: It is stored. False: It's not.
   */
  public function isHeader(string $key){
    return array_key_exists($key, $this->header);
  }

  /**
   * Gets an already defined header value.
   * @param  string $key The key of the pair, e.g. 'Content-Type'.
   * @return string      The value of the pair, e.g. 'application/json'.
   */
  public function getHeader(string $key){
    if(!$this->isHeader($key)){
      throw new \Exception('The requested header parameter ['.$key.'] does not exist');
    }
    return $this->header[$key];
  }

  /**
   * Sets a header key-value-pair.
   * @param string $key   The key of the pair, e.g. 'Content-Type'.
   * @param string $value The value which should be stored, e.g. 'application/json'.
   * @return Request This.
   */
  public function setHeader(string $key, string $value){
    $this->header[$key] = $value;
    return $this;
  }

  /**
   * Sends all headers - independently of the actual echo-output.
   * Should only be called once.
   * @return Request This.
   */
  public function sendHeader(){
    foreach($this->header as $key => $value){
      header(rtrim($key.': '.$value, ': '));
    }
    return $this;
  }

  /**
   * Load the headers from the client's original request.
   * @return Request This.
   */
  public function loadClientHeader(){
    $this->header = getallheaders();
    return $this;
  }

  // BODY-functions

  /**
   * Returns the body.
   * @return string The body.
   */
  public function getBody(){
    return $this->body;
  }

  /**
   * Sets the body of this response.
   * @param string $body The data which should be saved as the body.
   * @return Request this.
   */
  public function setBody(string $body){
    $this->body = $body;
    return $this;
  }

  /**
   * Loads the raw client-body.
   * WARNING: Can only be used once because the php://input-stream can only be read once.
   * @return Request This.
   */
  public function loadClientBody(){
    $this->body = file_get_contents('php://input'); // can only be called once
    return $this;
  }

}