<?php

class AssetResponseTest extends PHPUnit_Framework_TestCase
{
  public function testClassGeneration()
  {
    $response = new \Packaged\Dispatch\AssetResponse();
    $class    = $response->assetByExtension('css');
    $this->assertInstanceOf(
      '\Packaged\Dispatch\Assets\IAsset',
      $class
    );
    $this->assertInstanceOf(
      '\Packaged\Dispatch\Assets\CssAsset',
      $class
    );

    $class = $response->assetByExtension('invalid');
    $this->assertNull($class);
  }

  public function testResponse()
  {
    $builder = new \Packaged\Dispatch\AssetResponse();
    $asset   = new \Packaged\Dispatch\Assets\CssAsset();
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $asset->setContent('body{ background:blue; }');

    $response = $builder->createResponse($asset, $request);

    $this->assertEquals('text/css', $response->headers->get('Content-Type'));
    $this->assertContains('background:blue', (string)$response);
    $this->assertInstanceOf(
      '\Symfony\Component\HttpFoundation\Response',
      $response
    );
    $this->assertEquals(200, $response->getStatusCode());

    $request->server->set('HTTP_IF_MODIFIED_SINCE', '1234');
    $response = $builder->createResponse($asset, $request);
    $this->assertEquals(304, $response->getStatusCode());
  }
}
