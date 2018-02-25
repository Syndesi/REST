<?php
namespace Syndesi\REST;

use \PHPUnit\Framework\TestCase;


final class RequestTest extends TestCase {

  public function testConstructor(){
    $request = new Request('url', 'GET', ['a' => 'b'], 'body');
    $this->assertEquals(
      $request->getUrl(),
      'url',
      'Expected that the URL would be saved internally by the constructor.'
    );
    $this->assertEquals(
      $request->getMethod(),
      'GET',
      'Expected that the method would be saved internally by the constructor.'
    );
    $this->assertEquals(
      $request->getHeader('a'),
      'b',
      'Expected that headers would be saved internally by the constructor.'
    );
    $this->assertEquals(
      $request->getBody(),
      'body',
      'Expected that the body would be saved internally by the constructor.'
    );
  }

  public function testSetGetUrl(){
    $request = new Request();
    $request->setUrl('abc');
    $this->assertEquals(
      $request->getUrl(),
      'abc',
      'Expected that the URL could be overwritten.'
    );
  }

  public function testLoadClientUrl(){
    $_SERVER['REQUEST_URI'] = 'abc';
    $request = new Request();
    $request->loadClientUrl();
    $this->assertEquals(
      $request->getUrl(),
      'abc',
      'Expected that loadClientUrl would load the client´s URL.'
    );
  }

  public function testSetGetMethod(){
    $request = new Request();
    $request->setMethod('POST');
    $this->assertEquals(
      $request->getMethod(),
      'POST',
      'Expected that the Method could be overwritten.'
    );
  }

  public function testMethodIsSavedAsUppercase(){
    $request = new Request();
    $request->setMethod('post');
    $this->assertEquals(
      $request->getMethod(),
      'POST',
      'Expected that the Method would be saved in uppercase letters.'
    );
  }

  public function testLoadClientMethod(){
    $_SERVER['REQUEST_METHOD'] = 'PUSH';
    $request = new Request();
    $request->loadClientMethod();
    $this->assertEquals(
      $request->getMethod(),
      'PUSH',
      'Expected that loadClientMethod would load the client´s method.'
    );
  }

  public function testIsSetGetHeader(){
    $request = new Request();
    $request->setHeader('a', 'b');
    $this->assertTrue(
      $request->isHeader('a'),
      'Expected that saved values can be tested for their existence.'
    );
    $this->assertEquals(
      $request->getHeader('a'),
      'b',
      'Expected that the Header could be overwritten.'
    );
  }

  /**
   * @expectedException Exception
   */
  public function testGetUnsetHeaderThrowsException(){
    $request = new Request();
    $request->getHeader('unknown');
  }

  public function testSetGetBody(){
    $request = new Request();
    $request->setBody('abc');
    $this->assertEquals(
      $request->getBody(),
      'abc',
      'Expected that the body could be overwritten.'
    );
  }

}