<?php
namespace Syndesi\REST;

class Route {

  protected $function;
  protected $description;

  public function __construct($function = null, $description = null){
    $this->function    = $function;
    $this->description = $description;
  }

  public function setFunction($function){
    $this->function = $function;
    return $this;
  }

  public function getFunction(){
    return $this->function;
  }

  public function runFunction($args){
    $this->function($args);
    return $this;
  }

  public function setDescription($description){
    $this->description = $description;
    return $this;
  }

  public function getDescription(){
    return $this->description;
  }

}