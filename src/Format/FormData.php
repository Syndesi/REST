<?php
namespace Syndesi\REST\Format;

/**
 * Format-class to support multipart/form-data and application/x-www-form-urlencoded.
 */
class FormData implements iFormat {

  public function encode($data, $indent = true){
    throw new \Exception('This API does not support the output in the [multipart/form-data] format');
  }

  public function decode($string){
    return $_POST;
  }

  /**
   * Returns a list of supported mimetypes for this format.
   * WARNING: This includes non-standartized mimetypes.
   * @return array An array of all supported mimetypes.
   */
  public function getMimeType(){
    return [
      'application/x-www-form-urlencoded',
      'multipart/form-data'
    ];
  }

}