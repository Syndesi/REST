<?php
namespace Syndesi\REST;


/**
 * Makes interactions with the client easier:
 * Multiple input- & output-formats are allowed and the core values are always present, so that the client allways knows what's going on.
 */
class ClientRequest extends Request {

  protected $timestamp     = null;           // The moment when this script was called.
  protected $clientFormat  = null;           // The format-type the client's data is encoded in (e.g. json, yaml).
  protected $outputFormat  = null;           // The format-type the response should be encoded in (e.g. json, yaml).
  protected $formats       = [];             // Array of available Format-classes
  protected $defaultFormat = null;           // The default format, at this moment json.
  protected $dateFormat    = 'Y-d-mTG:i:sz'; // The format the timestamp should be decoded into.
  protected $indent        = true;           // True: The result will have tabs (human readable). False: Compact machine-readable blob.
  protected $files         = [];             // Array containing informations about uploaded files.
  protected $environment   = [];             // Empty array for additional environment-key-value-pairs.
  protected $pathKey       = '__PATH';       // defined in .htaccess
  
    /**
     * Creates a new ClientRequest.
     * WARNING: This function should only be used once because the original client-request-body can only be read once.
     * Therefore additional objects of this class can't access the client's data.
     */
  public function __construct(){
    $this->loadClientRequest();
    $this->timestamp     = new \DateTime();
    $this->formats       = [
      new Format\Json(),
      new Format\Yml(),
      new Format\FormData()
    ];
    $this->defaultFormat = $this->formats[0];
    $this->clientFormat  = $this->getMatchingFormat('Content-Type');
    $this->outputFormat  = $this->getMatchingFormat('Accept');
    $this->data          = $this->decodeClientData();
    //$this->finish($this->clientFormat->getMimeType());
    //$this->finish($this->header);
  }

  /**
   * Returns the decoded data from the client.
   * @return array The data from the client.
   */
  public function getData(){
    return $this->data;
  }

  /**
   * Finishes the application with the status-code 'OK'
   * WARNING: This function ends the execution.
   * @param  *             $result      The data which should be sent to the client.
   * @param  string        $description A short explanation so that developers understand what happened. Not required.
   * @param  string        $status      The status of your API, in this case by default 'OK'.
   */
  public function finish($result = null, $description = null, $status = 'OK'){
    $this->send($result, $status, $description);
    exit;
  }

  /**
   * Aborts the application with the status-code 'ERROR'
   * WARNING: This function ends the execution.
   * @param  *             $result      The data which should be sent to the client.
   * @param  string        $description A short explanation so that developers understand what happened. Not required.
   * @param  string        $status      The status of your API, in this case by default 'ERROR'.
   */
  public function abort($result = null, $description = null, $status = 'ERROR'){
    $this->send($result, $status, $description);
    exit;
  }

  /**
   * Sends this object back to the client.
   * WARNING: This function does not stop execution automatically.
   * @param  *             $result      The data which should be sent to the client.
   * @param  string        $status      The status of your API, e.g. 'OK', 'ERROR'.
   * @param  string        $description A short explanation so that developers understand what happened. Not required.
   * @return ClientRequest This.
   */
  public function send($result = null, $status = null, $description = null){
    $obj = [
      'result'      => $result,
      'status'      => $status,
      'description' => $description,
      'environment' => [
        'timestamp' => $this->timestamp->format($this->dateFormat),
        'method'    => $this->getMethod(),
        'url'       => $this->getUrl(),
        'files'     => $this->getFiles(),
        'data'      => $this->getData()
      ]
    ];
    // attach additional environment-values
    foreach($this->environment as $key => $value){
      if(array_key_exists($key, $obj['environment'])){
        throw new \Exception('The additional environment variable ['.$key.'] is already defined');
      }
      $obj['environment'][$key] = $value;
    }
    // remove empty/null objects from the object
    if($obj['result']               === null){ unset($obj['result']); }
    if($obj['status']               === null){ unset($obj['status']); }
    if($obj['description']          === null){ unset($obj['description']); }
    if($obj['environment']['files'] ===   []){ unset($obj['environment']['files']); }
    if($obj['environment']['data']  ===   []){ unset($obj['environment']['data']); }
    // add some default headers
    $this->setHeader('X-Powered-By', 'Syndesi');
    $this->setHeader('Content-Type', $this->outputFormat->getMimeType()[0].'; charset=utf-8');
    $this->sendHeader();
    // return the body
    echo($this->outputFormat->encode($obj, $this->indent));
    return $this;
  }

  /**
   * Returns a list of all uploaded files without their tmp_names (to increase securrity).
   * @return [type] [description]
   */
  public function getFiles(){
    $out = [];
    foreach($this->files as $i => $file){
      $out[$i] = $file;
      unset($out[$i]['tmp_name']);
    }
    return $out;
  }

  /**
   * Checks if an uploaded file exists.
   * @param  string  $key The identifier of the uploaded file.
   * @return boolean      True: The file exists. False: It does not.
   */
  public function isFile(string $key){
    return array_key_exists($key, $this->files);
  }

  /**
   * Returns a specific file.
   * @param  string $key The identifier of the file.
   * @return array       An associative array which contains all metadata like $_FILES.
   */
  public function getFile(string $key){
    if(!$this->isFile($key)){
      throw new \Exception('The file ['.$key.'] does not exist');
    }
    return $this->files[$key];
  }

  /**
   * Removes the file from this request.
   * WARNING: The file is still accesable by $_FILES.
   * @param  string $key The identifier of the file.
   */
  public function unsetFile(string $key, $value){
    unset($this->files[$key]);
  }

  /**
   * Moves a file to another place.
   * @param  string  $key         The identifier of the file.
   * @param  string  $destination The path of the file's destination.
   * @return boolean              @see http://php.net/manual/en/function.move-uploaded-file.php
   */
  public function moveFile($key, $destination){
    if(!$this->isFile($key)){
      throw new \Exception('The file ['.$key.'] does not exist');
    }
    return move_uploaded_file($this->files[$key]['tmp_name'], $destination);
  }

  /**
   * Enables CORS-requests acros different hosts/domains.
   * WARNING: This can be a security threat and should only be used in development.
   * @param  array         $allowedMethods Access-Control-Allow-Methods.
   * @param  int           $maxAge         Access-Control-Max-Age.
   * @return ClientRequest                 This.
    */
  public function enableCORS($allowedMethods = ['GET', 'POST', 'PUSH', 'DELETE'], $maxAge = 1000){
    $this->setHeader('Access-Control-Allow-Origin', '*');
    $this->setHeader('Access-Control-Allow-Credentials', 'true');
    $this->setHeader('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
    $this->setHeader('Access-Control-Max-Age', $maxAge);
    $this->setHeader('Access-Control-Allow-Headers', 'Origin, Content-Type, X-Auth-Token , Authorization');
    return $this;
  }

  // add additional environmental variables to the output

  /**
   * Checks if a variable exists in the environment-object of the response.
   * WARNING: This does not include the default key-value-pairs.
   * @param  string  $key The key of the variable.
   * @return boolean      True: The variable does exist. False: It does not.
   */
  public function isEnvironmentVariable(string $key){
    return array_key_exists($key, $this->environment);
  }

  /**
   * Returns a variable from the environment-object.
   * WARNING: This does not include the default key-value-pairs.
   * @param  string $key The key of the variable.
   * @return *           The corresponding value.
   */
  public function getEnvironmentVariable(string $key){
    if(!$this->isEnvironmentVariable($key)){
      throw new \Exception('The requested environment variable ['.$key.'] does not exist');
    }
    return $this->environment[$key];
  }

  /**
   * Adds a key-value-pair to the `environment`-part of the response.
   * @param string $key   The key under which the value can be found.
   * @param  *             $value The value which should be saved.
   * @return ClientRequest        This.
   */
  public function setEnvironmentVariable(string $key, $value){
    $this->environment[$key] = $value;
    return $this;
  }

  /**
   * Decodes the data from the client.
   * @return array An associated array of the merged data.
   */
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
    if(count($_FILES) > 0){
      $this->files = $_FILES;
    }
    return $data;
  }

  /**
   * Assigns Format-classes to known mimetypes (e.g. json, yaml).
   * @param  string  $headerField The key of the header-field, e.g. `Content-Type`.
   * @return iFormat              The class which is associated to the mimetype or the default format class in case the mimetype isn't given or unknown.
   */
  protected function getMatchingFormat($headerField){
    if($this->isHeader($headerField)){
      foreach(explode(', ', explode('; ', $this->getHeader($headerField), 2)[0]) as $a => $contentType){
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