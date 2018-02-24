<?php
namespace Syndesi\REST\Format;


class Json implements iFormat {

  public function encode($data, $indent = true){
    if($indent){
      return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
      return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
  }

  public function decode($string){
    return json_decode($string, true);
  }

  public function getMimeType(){
    return [
      'application/json',
      'text/json',
      'json'
    ];
  }

}