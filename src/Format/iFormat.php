<?php
namespace Syndesi\REST\Format;


interface iFormat {

  public function encode($data, $indent = true);
  public function decode($string);
  public function getMimeType();

}