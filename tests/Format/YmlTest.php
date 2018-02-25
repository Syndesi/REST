<?php
namespace Syndesi\REST\Format;

use \PHPUnit\Framework\TestCase;


final class YmlTest extends TestCase {

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
    $yml = new Yml();
    $this->assertEquals(
      $this->obj,
      $yml->decode($yml->encode($this->obj)),
      'Expected cyclic output to represent the original data.'
    );
  }

  public function testEncodeGivesString(){
    $yml    = new Yml();
    $output = $yml->encode($this->obj);
    $this->assertTrue(
      is_string($output),
      'Expected output to be a string, instead got ['.gettype($output).']'
    );
  }

  public function testIndentReducesSize(){
    $yml         = new Yml();
    $smallOutput = $yml->encode($this->obj, false);
    $bigOutput   = $yml->encode($this->obj, true);
    $this->assertLessThan(
      strlen($bigOutput),
      strlen($smallOutput),
      'Expected the output without indentation to be smaller than with it.'
    );
  }

  public function testMimeTypeReturnsArray(){
    $yml   = new Yml();
    $array = $yml->getMimeType();
    $this->assertTrue(
      is_array($array),
      'Expected mimetypes to be strings, instead got ['.gettype($array).']'
    );
  }

  public function testMimeTypeContainsOnlyStrings(){
    $yml = new Yml();
    foreach($yml->getMimeType() as $i => $mimetype){
      $this->assertTrue(
        is_string($mimetype),
        'Expected mimetypes to be strings, instead got ['.gettype($mimetype).']'
      );
    }
  }

}
