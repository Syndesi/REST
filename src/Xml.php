<?php
namespace Syndesi;

/**
 * This class is used to encode and decode XML.
 */
public class Xml{

  protected $data;
  protected $assoc;

  /**
   * Creates a new XML-parser instance.
   * @param object|array|string $data The data/XML which should be internally saved as the current data;
   */
  public function __construct($data, $assoc = false){
    $this->assoc = $assoc;
    $this->loadData($data);
  }

  /**
   * Loads data/xml into this object.
   * @param  array|object|string $data The data which should be loaded.
   */
  public function loadData($data){
    switch(gettype($data)){
      case 'array':
        $this->data = $data;
        break;
      case 'object':
        $this->data = json_encode(json_decode($data), $this->assoc);
        break;
      default:
        $this->data = $this->decode($data);
        break;
    }
  }

  /**
   * Returns the internally saved data.
   * @return array The created/saved array.
   */
  public function getData(){
    return $this->data;
  }

  /**
   * Converts XML to an (associative) array.
   * @param  string     $xml The XML-encoded data.
   * @return array|bool      The resulting array.
   */
  public function decode($xml){
    if(!$xml){
      return false;
    }
    $xml = simplexml_load_string($xml);
    $this->data = json_decode(json_encode($xml), $this->assoc);
    return $this->data;
  }

  /**
   * Converts an array to XML, based on Ghost´s answer: http://stackoverflow.com/a/26964222
   * @param  bool|array   $array False or an array (only used internally).
   * @param  bool|string  $xml   False or the created XML (only used internally).
   * @return string              The resulting xml string.
   */
  public function encode($array = false, $xml = false){
    if($xml === false){
      $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><result />');
    }
    if($array === false){
      $array = $this->data;
    }
    foreach($array as $key => $value){
      $key = str_replace(' ', '_', $key);
      if(is_numeric($key)){
        $key = 'entry';
      }
      if(is_array($value)){
        $this->encode($value, $xml->addChild($key));
      } else {
        $xml->addChild($key, $value);
      }
    }
    return $xml->asXML();
  }

}

?>