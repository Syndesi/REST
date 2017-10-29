<?php
namespace Syndesi\REST;

use \Symfony\Component\Yaml\Yaml;

class Request {

  public $client = [
    'api'     => '',
    'method'  => '',
    'path'    => '',
    'route'   => '',
    'headers' => '',
    'raw'     => ''
  ];
  public $format;
  public $data = [];
  public $file = [];
  public $indent = true;
  public $status;
  public $session;
  public $timestamp;

  const PATH_NAME = '_PATH';
  const FORMAT_JSON            = 'application/json';
  const FORMAT_XML             = 'application/xml';
  const FORMAT_YAML            = 'application/x-yaml';
  const FORMAT_FORM_URLENCODED = 'application/x-www-form-urlencoded';
  const FORMAT_FORM_DATA       = 'multipart/form-data';
  const FORMAT_OCTET_STREAM    = 'application/octet-stream';

  public function __construct(){
    $this->timestamp = new \DateTime();
    $this->session = new Session();
    $this->parseClientRequest();
  }

  public function getData(){}

  public function getAndDeleteData(string $key){
    if($this->isData($key)){
      $value = $this->data[$key];
      unset($this->data[$key]);
      return $value;
    }
    return false;
  }

  public function isData(string $key){
    if(!array_key_exists($key, $this->data)){
      return false;
    }
    if($this->data[$key] === NULL || $this->data[$key] == ''){
      return false;
    }
    return true;
  }

  public function redirect(string $url){
    $this->sendHeaders();
    header('Location: '.$url);
    exit;
  }

  public function finish($obj){
    $this->out($obj);
  }

  public function abort(int $status, $obj, string $message = 'An error occurred :('){
    $this->out(null, $status, $message);
  }

  public function sendFile(string $path, $filename = false){
    if(!file_exists($path)){
      throw new Exception('This file does not exist.');
    }
    if(!$filename){
      $filename = basename($path);
    }
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: '.filesize($path));
    readfile($path);
    exit;
  }

  public function parseClientRequest(){
    $this->client['raw'] = file_get_contents('php://input');
    $this->client['headers'] = getallheaders();
    $this->client['method'] = $this->getMethod();
    $path = explode('/', trim($_REQUEST[$this::PATH_NAME], '/'));
    if(count($path) == 0 || !$path[0]){
      $this->abort(400, 'No API specified');
    }
    $this->client['api']    = array_shift($path);
    $this->client['path']   = $path;
    $this->client['route']  = $this->client['method'].':/'.implode('/', $path);

    if(!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'COPY', 'PURGE', 'UNLOCK'])){ // filter requests with a body
      if(array_key_exists('Content-Type', $this->clientHeaders)){
        $contentType = explode(';', $this->clientHeaders['Content-Type'])[0];
        $format = $this->standartizeFormat($contentType);
        switch($format){
          case $this::FORMAT_XML:
            $data = Syndesi\xml2array($this->rawInput);
            $this->format = $this::FORMAT_XML;
            break;
          case $this::FORMAT_YAML:
            $data = Yaml::parse($this->rawInput);
            $this->format = $this::FORMAT_YAML;
            break;
          case $this::FORMAT_FORM_URLENCODED:
            parse_str($this->rawInput, $this->data);
            break;
          case $this::FORMAT_FORM_DATA:
            $data = $_POST;
            $this->file = $_FILES;
            break;
          case $this::FORMAT_JSON:
          default:
            $data = json_decode($this->rawInput, true);
            $this->format = $this::FORMAT_JSON;
            break;
        }
        if($data){
          $this->data = $data;
        }
      }
      if(!is_array($this->data)){
        $this->abort(400, 'error while parsing the request');
      }
    }
    if(count($_GET) > 0){
      $this->data = array_merge($_GET, $this->data);
      if(array_key_exists($this::PATH_NAME, $this->data)){
        unset($this->data[$this::PATH_NAME]);
      }
    }
  }

  protected function getMethod(){
    $method = $_SERVER['REQUEST_METHOD'];
    if(tmp = $this->getAndDeleteData('m') !== false){
      $method = $tmp;
    }
    if(tmp = $this->getAndDeleteData('method') !== false){
      $method = $tmp;
    }
    $method = strtoupper(trim($method));
    return $method;
  }

  protected function overwriteFormat($format = false){
    if(!$format){
      $format = $this::FORMAT_JSON;
    }
    if(array_key_exists('Accept', $this->clientHeaders)){
      $format = $this->clientHeaders['Accept'];
    }
    if(array_key_exists('Content-Type', $this->clientHeaders)){
      $format = $this->clientHeaders['Content-Type'];
    }
    if(tmp = $this->getAndDeleteData('f') !== false){
      $format = $tmp;
    }
    if(tmp = $this->getAndDeleteData('format') !== false){
      $format = $tmp;
    }
    $format = explode(';', $format)[0];
    $this->format = $this->standartizeFormat($format);
  }

  protected function standartizeFormat(){
    switch($format){
      case 'application/xml':
      case 'text/xml':
      case 'xml':
        $format = $this::FORMAT_XML;
        break;
      case 'application/x-yaml':
      case 'application/x-yml':
      case 'application/yaml':
      case 'application/yml':
      case 'text/vnd.yaml':       // proposed mime-type for YAML, see https://www.ietf.org/mail-archive/web/media-types/current/msg00688.html
      case 'text/x-yaml':
      case 'text/x-yml':
      case 'text/yaml':
      case 'text/yml':
      case 'yaml':
      case 'yml':
        $format = $this::FORMAT_YAML;
        break;
      case 'application/x-www-form-urlencoded':
        $format = $this::FORMAT_FORM_URLENCODED;
        break;
      case 'multipart/form-data':
        $format = $this::FORMAT_FORM_DATA;
        break;
      case 'application/octet-stream':
        $format = $this::FORMAT_OCTET_STREAM;
        break;
      case 'application/json':
      case 'text/json':
      case 'json':
      default:
        $format = $this::FORMAT_JSON;
        break;
    }
    return $format;
  }

  protected function out($result, int $status = 0, string $description = ''){
    $this->sendHeaders();
    header('Content-Type: '.$this->format.'; charset=utf-8');
    $obj = [
      'result'          => $result,
      'status'          => $status,
      'description'     => $description,
      'environment'     => [
        'api'           => $this->client['api'],
        'requestMethod' => $this->client['method'],
        'timestamp'     => $this->timestamp,
        'data'          => $this->data
      ]
    ];
    //if(count($this->file) > 0){
    //  $files = [];
    //  foreach($this->file as $key => $file){
    //    $files[$key] = [
    //      "name"   => $file['name'],
    //      "type"   => $file['type'],
    //      "size"   => \lib\formatBytes($file['size'])
    //    ];
    //    $status = $this->getFileErrorCode($file['error']);
    //    $files[$key]['status'] = $status[0];
    //    if($status[1]){
    //      $files[$key]['description'] = $status[1];
    //    }
    //  }
    //  $obj['environment']['file'] = $files;
    //}
    if($description == ''){
      unset($obj['description']);
    }
    switch($this->format){
      case $this::FORMAT_YAML:
        if($this->indent){
          $data = Yaml::dump($obj, 10, 2);
        } else {
          $data = Yaml::dump($obj, 0, 2);
        }
        break;
      case $this::FORMAT_XML:
        if($this->indent){
          $dom = new \DOMDocument;
          $dom->loadXML(array2xml($obj));
          $dom->formatOutput = true;
          $data = $dom->saveXML();
        } else {
          $data = array2xml($obj);
        }
        break;
      case $this::FORMAT_JSON:
      default:
        if($this->indent){
          $data = json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
          $data = json_encode($obj, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        break;
    }
    // this line returns the whole response
    echo($data);
    exit;
  }

  protected function sendHeaders(){
    header('X-Powered-By: Syndesi');
    header('Access-Control-Allow-Origin: *'); 
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: '.implode(', ', $config['methods']));
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
    header('Accept: application/json, application/xml, text/xml, application/x-yaml, text/yaml, application/x-www-form-urlencoded, multipart/form-data');
  }

}

?>