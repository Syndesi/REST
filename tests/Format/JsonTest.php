<?php
namespace Syndesi\REST\Format;

use \PHPUnit\Framework\TestCase;


final class JsonTest extends TestCase {

  protected $obj = [
    'a' => 1,
    'b' => 1.2,
    'c' => 'string',
    'd' => ['a', 'b', 'c'],
    'e' => [
      'a' => 1,
      'b' => 2,
      'c' => 3
    ]
  ];

  public function testEncodeDecodeReturnsSameData(){
    $json = new Json();
    $this->assertEquals(
      $this->obj,
      $json->decode($json->encode($this->obj)),
      'Expected cyclic output to represent the original data.'
    );
  }

  public function testEncodeGivesString(){
    $json   = new Json();
    $output = $json->encode($this->obj);
    $this->assertTrue(
      is_string($output),
      'Expected output to be a string, instead got ['.gettype($output).']'
    );
  }

  public function testIndentReducesSize(){
    $json        = new Json();
    $smallOutput = $json->encode($this->obj, false);
    $bigOutput   = $json->encode($this->obj, true);
    $this->assertLessThan(
      strlen($bigOutput),
      strlen($smallOutput),
      'Expected the output without indentation to be smaller than with it.'
    );
  }

  public function testMimeTypeReturnsArray(){
    $json  = new Json();
    $array = $json->getMimeType();
    $this->assertTrue(
      is_array($array),
      'Expected mimetypes to be strings, instead got ['.gettype($array).']'
    );
  }

  public function testMimeTypeContainsOnlyStrings(){
    $json = new Json();
    foreach($json->getMimeType() as $i => $mimetype){
      $this->assertTrue(
        is_string($mimetype),
        'Expected mimetypes to be strings, instead got ['.gettype($mimetype).']'
      );
    }
  }

}
