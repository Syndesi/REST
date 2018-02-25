<?php
namespace Syndesi\REST\Format;

/**
 * Interface to standartize file-formats, e.g. json/yaml.
 */
interface iFormat {

  public function encode($data, $indent = true);
  public function decode($string);
  public function getMimeType();

}