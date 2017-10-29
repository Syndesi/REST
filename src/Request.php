<?php
namespace Syndesi\REST;

use \Symfony\Component\Yaml\Yaml;

/**
 * This class parses the HTTP-request and trys to give the client a meaningfull answer.
 */
class Request{

  public $api;            // e.g. user
  public $method;
  public $path;           // remaining parts of the url
  public $route;          // e.g. POST:/login
  public $status;
  public $timestamp;
  public $clientHeaders;
  public $format;         // the format which is returned (e.g. 'json', 'yml' or 'xml')
  public $data = [];
  public $file = [];
  public $indent = true;
  protected $rawInput;
  protected $supportedMethods = ['GET', 'POST', 'PUT', 'DELETE'];

  const DATE_TIMEZONE          = new \DateTimeZone('Europe/Berlin');
  const DATE_FORMAT            = \DateTime::ATOM; // ISO 8601, can be replaced by a string like 'Y-m-d H:i:s'
  const FORMAT_JSON            = 'application/json';
  const FORMAT_XML             = 'application/xml';
  const FORMAT_YAML            = 'application/x-yaml';
  const FORMAT_FORM_URLENCODED = 'application/x-www-form-urlencoded';
  const FORMAT_FORM_DATA       = 'multipart/form-data';
  const FORMAT_OCTET_STREAM    = 'application/octet-stream';
  const PATH_NAME              = '_PATH'; // The "variable" which is used by .htaccess to store the end of the route.
  const OK                     = 'OK';
  const REQUEST_DENIED         = 'REQUEST_DENIED';
  const INVALID_REQUEST        = 'INVALID_REQUEST';
  const UNKNOWN_ERROR          = 'UNKNOWN_ERROR';

  public function __construct(){
    $this->timestamp = gmdate($this::DATE_FORMAT);
    $this->clientHeaders = getallheaders();
    $this->rawInput = file_get_contents('php://input');
    $this->format = $this::FORMAT_JSON;
    $this->status = $this::OK;
    $this->session = new \Syndesi\Session();
    $this->parseClientRequest();
    $this->overwriteFormat();
    $path = explode('/', trim($_REQUEST[$this::PATH_NAME], '/'));
    if(count($path) == 0 || !$path[0]){
      $this->abort($this::INVALID_REQUEST, 'No API specified');
    }
    $this->api = array_shift($path);
    $this->method = $this->getMethod();
    $this->path = $path;
    $this->route = $this->method.':/'.implode('/', $this->path);
  }

  /**
   * Returns which type of HTTP-method was used.
   * Can be overwriten with "m"/"method" parameters in the clients´ request.
   * @return string The uppercase version of the method.
   */
  public function getMethod(){
    $method = $_SERVER['REQUEST_METHOD'];
    if($this->isData('m')){
      $method = $this->data['m'];
      unset($this->data['m']);
    }
    $method = strtoupper(trim($method));
    return $method;
  }

  /**
   * Parses the received data from the client.
   * // It was somewhat difficult to add the edge-cases.
   * @return void
   */
  public function parseClientRequest(){
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
        $this->abort($this::INVALID_REQUEST, 'error while parsing the request');
      }
    }
    if(count($_GET) > 0){
      $this->data = array_merge($_GET, $this->data);
      if(array_key_exists($this::PATH_NAME, $this->data)){
        unset($this->data[$this::PATH_NAME]);
      }
    }
  }

  /**
   * checks if the user want a special format. if it's supported by this application, it will be used
   * @param string $format the default format
   * @return void
   */
  protected function overwriteFormat($format = false){
    // priority:
    // 1.: argument named format
    // 2.: Header: Content-Type
    // 3.: Header: Accept
    if(!$format){
      $format = $this::FORMAT_JSON;
    }
    if(array_key_exists('Accept', $this->clientHeaders)){
      $format = $this->clientHeaders['Accept'];
    }
    if(array_key_exists('Content-Type', $this->clientHeaders)){
      $format = $this->clientHeaders['Content-Type'];
    }
    if(array_key_exists('format', $this->data)){
      $format = $this->data['format'];
      unset($this->data['format']);
    }
    $format = explode(';', $format)[0];
    $this->format = $this->standartizeFormat($format);
  }

  /**
   * Reads the given format and trys to find the "correct" standartized format.
   * The "correct" standart can be changed in the constants at the top of this file.
   * @param  string $format The format which should be standartized.
   * @return string         The standartized format.
   */
  protected function standartizeFormat($format){
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

  /**
   * returns received data or aborts the program, if enabled
   * @param  string  $key   the data-key
   * @param  boolean $abort true: the program will abort if the key isn't found, false: this function will return just false if the key isn't found
   * @return data         the received data
   */
  public function getData(string $key, bool $abort = false){
    if(array_key_exists($key, $this->data)){
      return $this->data[$key];
    } else {
      if($abort){
        $this->abort($this::INVALID_REQUEST, 'Argument `'.$key.'` not found.');
      } else {
        return null;
      }
    }
  }

  /**
   * checks if the client has send a special type of data
   * @param  string  $key the data-key
   * @return boolean      true: data was sent, false: data wasn't sent
   */
  public function isData(string $key){
    if(array_key_exists($key, $this->data)){
      if($this->data[$key] !== NULL && $this->data[$key] != ''){
        return true;
      }
    } else {
      return false;
    }
  }

  public function redirect(string $url){
    $this->sendHeaders();
    header('Location: '.$url);
    exit;
  }

  /**
   * This function is used to finish the program
   * @param  object $obj the result, will be encoded as JSON/XML/YAML
   * @return void
   */
  public function finish($obj){
    $this->out($obj);
  }

  /**
   * This function is used to abort the program
   * @param  string $status  the status of the program
   * @param  string $message explanation for the error
   * @return void
   */
  public function abort($status, $message){
    $this->out(null, $status, $message);
  }

  protected function sendFile($path, $filename = false){
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

  /**
   * This function returns the response with several additional informations
   * @param  object  $result        the result, if finished
   * @param  string  $status        the staus code, e.g. 'OK'
   * @param  string  $error_message the error-message, default is null
   * @return void                   exit before return
   */
  protected function out($result, $status = false, $error_message = null){
    if($status === false){
      $status = $this::OK;
    }
    $this->sendHeaders();
    header('Content-Type: '.$this->format.'; charset=utf-8');
    $obj = [
      'result'          => $result,
      'status'          => $status,
      'error_message'   => $error_message,
      'environment'     => [
        'api'           => $this->api,
        'requestMethod' => $this->method,
        'timestamp'     => $this->timestamp,
        'data'          => $this->data
      ]
    ];
    if(count($this->file) > 0){
      $files = [];
      foreach($this->file as $key => $file){
        $files[$key] = [
          "name"   => $file['name'],
          "type"   => $file['type'],
          "size"   => \lib\formatBytes($file['size'])
        ];
        $status = $this->getFileErrorCode($file['error']);
        $files[$key]['status'] = $status[0];
        if($status[1]){
          $files[$key]['error_message'] = $status[1];
        }
      }
      $obj['environment']['file'] = $files;
    }
    if($error_message === null){
      unset($obj['error_message']);
    } else {
      unset($obj['result']);
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

  public function sendHeaders(){
    header('X-Powered-By: Syndesi');
    header('Access-Control-Allow-Origin: *'); 
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Methods: '.implode(', ', $this->supportedMethods));
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token , Authorization');
    header('Accept: application/json, application/xml, text/xml, application/x-yaml, text/yaml, application/x-www-form-urlencoded, multipart/form-data');
  }

  /**
   * Returns the explanation for several error-codes, which occur while uploading files.
   * @param  int $code the native php-error-code
   * @return array     [0]: 'OK' or 'ERROR', [1]: false or the error explanation
   */
  protected function getFileErrorCode($code){
    switch($code){
      case UPLOAD_ERR_OK:
        return ['OK', false];
      case UPLOAD_ERR_INI_SIZE:
        return ['ERROR', 'File must be smaller than '.(ini_get('upload_max_filesize')/(1024*1024)).' mb'];
      case UPLOAD_ERR_FORM_SIZE:
        return ['ERROR', 'File exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'];
      case UPLOAD_ERR_PARTIAL:
        return ['ERROR', 'File was only partially uploaded'];
      case UPLOAD_ERR_NO_FILE:
        return ['ERROR', 'No File was uploaded'];
      case UPLOAD_ERR_NO_TMP_DIR:
      case UPLOAD_ERR_CANT_WRITE:
      case UPLOAD_ERR_EXTENSION:
      default:
        return ['ERROR', 'Internal error while processing the file.'];
    }
  }

}

?>