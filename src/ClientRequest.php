<?php
namespace Syndesi\REST;


class ClientRequest extends Request {

  protected $timestamp     = null;
  protected $clientFormat  = null;
  protected $outputFormat  = null;
  protected $formats       = [];
  protected $defaultFormat = null;
  protected $format        = null;
  protected $dateFormat    = 'Y-d-mTG:i:sz';
  protected $indent        = true;
  protected $environment   = [];
  protected $pathKey       = '__PATH';  // defined in .htaccess
  
  public function __construct(){
    $this->loadClientRequest();
    $this->timestamp     = new \DateTime();
    $this->formats       = [
      new Format\Json(),
      new Format\Yml()
    ];
    $this->defaultFormat = $this->formats[0];
    $this->clientFormat  = $this->getMatchingFormat('Content-Type');
    $this->outputFormat  = $this->getMatchingFormat('Accept');
    $this->data          = $this->decodeClientData();
  }

  public function getData(){
    return $this->data;
  }

  public function finish($result = null, $description = null, $status = 'OK'){
    $this->send($result, $status, $description);
    exit;
  }

  public function abort($result = null, $description = null, $status = 'ERROR'){
    $this->send($result, $status, $description);
    exit;
  }

  public function send($result = null, $status = null, $description = null){
    $obj = [
      'result'      => $result,
      'status'      => $status,
      'description' => $description,
      'environment' => [
        'timestamp' => $this->timestamp->format($this->dateFormat),
        'method'    => $this->getMethod(),
        'url'       => $this->getUrl(),
        'data'      => $this->getData()
      ]
    ];
    foreach($this->environment as $key => $value){
      if(array_key_exists($key, $obj['environment'])){
        throw new \Exception('The additional environment variable ['.$key.'] is already defined');
      }
      $obj['environment'][$key] = $value;
    }
    // remove empty/null objects from the object
    if($obj['result']              === null){ unset($obj['result']); }
    if($obj['status']              === null){ unset($obj['status']); }
    if($obj['description']         === null){ unset($obj['description']); }
    if($obj['environment']['data'] ===   []){ unset($obj['environment']['data']); }
    $this->setHeader('X-Powered-By', 'Syndesi');
    $this->setHeader('Content-Type', $this->outputFormat->getMimeType()[0].'; charset=utf-8');
    $this->sendHeader();
    echo($this->outputFormat->encode($obj, $this->indent));
    return $this;
  }

  public function enableCORS($allowedMethods = ['GET', 'POST', 'PUSH', 'DELETE'], $maxAge = 1000){
    $this->setHeader('Access-Control-Allow-Origin', '*');
    $this->setHeader('Access-Control-Allow-Credentials', 'true');
    $this->setHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
    $this->setHeader('Access-Control-Max-Age', $maxAge);
    $this->setHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token , Authorization');
  }

  // add additional environmental variables to the output

  public function isEnvironmentVariable(string $key){
    return array_key_exists($key, $this->environment);
  }

  public function getEnvironmentVariable(string $key){
    if(!$this->isEnvironmentVariable($key)){
      throw new \Exception('The requested environment variable ['.$key.'] does not exist');
    }
    return $this->environment[$key];
  }

  public function setEnvironmentVariable(string $key, $value){
    $this->environment[$key] = $value;
    return $this;
  }

  protected function decodeClientData(){
    $data = [];
    if(is_array($_GET)){
      if(array_key_exists($this->pathKey, $_GET)){
        $this->url = trim($_GET[$this->pathKey], '/');
        unset($_GET[$this->pathKey]);
      }
      $data = array_merge($data, $_GET);
    }
    if(is_array($decodedBody = $this->clientFormat->decode($this->body))){
      $data = array_merge($data, $decodedBody);
    }
    return $data;
  }

  protected function getMatchingFormat($headerField){
    if($this->isHeader($headerField)){
      foreach(explode(', ', $this->getHeader($headerField)) as $a => $contentType){
        foreach($this->formats as $key => $format){
          foreach($format->getMimeType() as $key => $mimeType){
            if($contentType == $mimeType){
              return $format;
            }
          }
        }
      }
    }
    return $this->defaultFormat;
  }

}