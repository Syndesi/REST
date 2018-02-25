<?php
namespace Syndesi\REST\Format;

/**
 * Format-class to support the json-filetype.
 */
class Json implements iFormat {

  /**
   * Encodes data into a json-string.
   * @param  *       $data   The data which should be encoded.
   * @param  boolean $indent True: The result is human readable. False: Result is a machine-readable blob.
   * @return string          The encoded json-string.
   */
  public function encode($data, $indent = true){
    if($indent){
      return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
      return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
  }

  /**
   * Decodes a json-string into it's corresponding data-types.
   * @param  string $string The json-encoded string.
   * @return *              The decoded data.
   */
  public function decode($string){
    return json_decode($string, true);
  }

  /**
   * Returns a list of supported mimetypes for this format.
   * WARNING: This includes non-standartized mimetypes.
   * @return array An array of all supported mimetypes.
   */
  public function getMimeType(){
    return [
      'application/json',
      'text/json',
      'json'
    ];
  }

}