<?php
namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Resources\CssResource;
use PHPUnit\Framework\TestCase;

class CssResourceTest extends TestCase
{
  public function testMinify()
  {
    $original = 'body
          {
          background:red;
          }';

    $nominify = '@' . 'do-not-minify
    body
          {
          background:red;
          }';

    $resource = new CssResource();

    $resource->setContent($original);
    $this->assertStringStartsWith('body{background:red}', $resource->getContent());

    $resource->setContent($nominify);
    $this->assertStringStartsWith($nominify, $resource->getContent());

    $resource->setContent($original);
    $resource->setOptions(['minify' => 'false']);
    $this->assertStringStartsWith($original, $resource->getContent());
  }

  public function testResource()
  {
    $resource = new CssResource();
    $this->assertEquals('css', $resource->getExtension());
    $this->assertEquals('text/css', $resource->getContentType());
  }
}
