<?php
namespace Syndesi\REST\Format;

use \Symfony\Component\Yaml\Yaml;

/**
 * Format-class to support the yaml-filetype.
 */
class Yml implements iFormat {

  /**
   * Encodes data into a yaml-string.
   * @param  *       $data   The data which should be encoded.
   * @param  boolean $indent True: The result is human readable. False: Result is a machine-readable blob.
   * @return string          The encoded yaml-string.
   */
  public function encode($data, $indent = true){
    if($indent){
      return Yaml::dump($data, 10, 2);
    } else {
      return Yaml::dump($data, 0, 2);
    }
  }

  /**
   * Decodes a yaml-string into it's corresponding data-types.
   * @param  string $string The yaml-encoded string.
   * @return *              The decoded data.
   */
  public function decode($string){
    return Yaml::parse($string);
  }

  /**
   * Returns a list of supported mimetypes for this format.
   * WARNING: This includes non-standartized mimetypes.
   * @return array An array of all supported mimetypes.
   */
  public function getMimeType(){
    return [
      'text/vnd.yaml',       // proposed mime-type for YAML, see https://www.ietf.org/mail-archive/web/media-types/current/msg00688.html
      'application/x-yaml',
      'application/x-yml',
      'application/yaml',
      'application/yml',
      'text/x-yaml',
      'text/x-yml',
      'text/yaml',
      'text/yml',
      'yaml',
      'yml'
    ];
  }

}