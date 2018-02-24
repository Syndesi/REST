<?php
namespace Syndesi\REST;


class Request {

  protected $url;
  protected $method;
  protected $header;
  protected $body;

  public function __construct(string $url = null, string $method = 'GET', array $header = null, string $body = null){
    $this->url    = $url;
    $this->method = $method;
    $this->header = $header;
    $this->body   = $body;
    return $this;
  }

  // combined functions

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
   */
  public function send(){
    $this->sendHeader();
    echo($this->body);
    return $this;
  }

  /**
   * Used to send files without "environment variables" and so on.
   */
  public function sendRawData($data){
    $this->sendHeader();
    echo($data);
    return $this;
  }

  // URL-functions
  
  public function getUrl(){
    return $this->url;
  }

  public function setUrl(string $url){
    $this->url = $url;
    return $this;
  }

  public function loadClientUrl(){
    $this->method = $_SERVER['REQUEST_URI'];
    return $this;
  }

  // METHOD-functions

  public function getMethod(){
    return $this->method;
  }

  public function setMethod($method){
    $this->method = strtoupper(trim($method));
    return $this;
  }

  public function loadClientMethod(){
    $this->method = $_SERVER['REQUEST_METHOD'];
    return $this;
  }

  // HEADER-functions

  public function isHeader(string $key){
    return array_key_exists($key, $this->header);
  }

  public function getHeader(string $key){
    if(!$this->isHeader($key)){
      throw new \Exception('The requested header parameter ['.$key.'] does not exist');
    }
    return $this->header[$key];
  }

  public function setHeader(string $key, string $value){
    $this->header[$key] = $value;
    return $this;
  }

  public function sendHeader(){
    foreach($this->header as $key => $value){
      header(rtrim($key.': '.$value, ': '));
    }
    return $this;
  }

  public function loadClientHeader(){
    $this->header = getallheaders();
    return $this;
  }

  // BODY-functions

  public function getBody(){
    return $this->body;
  }

  public function setBody(string $body){
    $this->body = $body;
    return $this;
  }

  public function loadClientBody(){
    $this->body = file_get_contents('php://input'); // can only be called once
    return $this;
  }

}