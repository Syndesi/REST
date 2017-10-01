<?php
namespace Syndesi;

/**
 * returns the formated size in KB, MB, GB and so on
 * @source  https://stackoverflow.com/a/2510459/4417327 The original code, slightly modified
 * @param  [type]  $bytes     [description]
 * @param  integer $precision [description]
 * @return [type]             [description]
 */
function formatBytes($bytes, $precision = 2){
  $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
  $bytes = max($bytes, 0);
  $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
  $pow = min($pow, count($units) - 1);
  return round($bytes/pow(1024, $pow), $precision).' '.$units[$pow]; 
}

/**
 * Returns the XML representation of an array.
 * Based on {@link http://stackoverflow.com/a/26964222 Ghost answer}.
 * @param  array                 $array The array which should be parsed.
 * @param  bool|SimpleXMLElement $xml   Only used internally to work recursively with the current XML-Object.
 * @return string                       The resulting XML.
 */
function xml_encode($array, $xml = false){
  if($xml === false){
    $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><result />');
  }
  foreach($array as $key => $value){
    $key = str_replace(' ', '_', $key);
    if(is_numeric($key)){
      $key = 'entry';
    }
    if(is_array($value)){
      xml_encode($value, $xml->addChild($key));
    } else {
      $xml->addChild($key, $value);
    }
  }
  return $xml->asXML();
}

/**
 * Decodes a XML string.
 * Based on {@link http://stackoverflow.com/a/26964222 Ghost answer}.
 * @param  string       $xml   The XML string being decoded.
 * @param  bool         $assoc When TRUE, the returned object will be converted into associative arrays.
 * @return object|array        The resulting object/array.
 */
function xml_decode($xml, $assoc = true){
  if(!$xml){
    return false;
  }
  $xml = simplexml_load_string($xml);
  $json = json_encode($xml);
  return json_decode($json, $assoc);
}

?>