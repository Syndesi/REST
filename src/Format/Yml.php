<?php
namespace Syndesi\REST\Format;

use \Symfony\Component\Yaml\Yaml;


class Yml implements iFormat {

  public function encode($data, $indent = true){
    if($indent){
      return Yaml::dump($data, 10, 2);
    } else {
      return Yaml::dump($data, 0, 2);
    }
  }

  public function decode($string){
    return Yaml::parse($string);
  }

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