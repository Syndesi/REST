<?php
namespace Syndesi\REST\Format;

use \PHPUnit\Framework\TestCase;


final class FormDataTest extends TestCase {

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

  public function testMimeTypeReturnsArray(){
    $formData = new FormData();
    $array    = $formData->getMimeType();
    $this->assertTrue(
      is_array($array),
      'Expected mimetypes to be strings, instead got ['.gettype($array).']'
    );
  }

  public function testMimeTypeContainsOnlyStrings(){
    $formData = new FormData();
    foreach($formData->getMimeType() as $i => $mimetype){
      $this->assertTrue(
        is_string($mimetype),
        'Expected mimetypes to be strings, instead got ['.gettype($mimetype).']'
      );
    }
  }

}
