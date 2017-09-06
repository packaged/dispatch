<?php

class AssetResponseTest extends \PHPUnit\Framework\TestCase
{
  public function testClassGeneration()
  {
    $response = new \Packaged\Dispatch\AssetResponse();
    $class = $response->assetByExtension('css');
    $this->assertInstanceOf('\Packaged\Dispatch\Assets\IAsset', $class);
    $this->assertInstanceOf('\Packaged\Dispatch\Assets\CssAsset', $class);

    $class = $response->assetByExtension('unknown');
    $this->assertInstanceOf('Packaged\Dispatch\Assets\UnknownAsset', $class);
    $this->assertNull($class->getExtension());
  }

  public function testResponse()
  {
    $builder = new \Packaged\Dispatch\AssetResponse();
    $asset = new \Packaged\Dispatch\Assets\CssAsset();
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

  public function testCustomType()
  {
    $exts = \Packaged\Dispatch\AssetResponse::getExtensions();
    $this->assertFalse(array_search('mock', $exts));

    $builder = new \Packaged\Dispatch\AssetResponse();
    $this->assertInstanceOf(
      '\Packaged\Dispatch\Assets\UnknownAsset',
      $builder->assetByExtension('mock')
    );
    \Packaged\Dispatch\AssetResponse::addAssetType('mock', new MockAssetType());

    $asset = $builder->assetByExtension('mock');
    $this->assertInstanceOf('\MockAssetType', $asset);

    $this->assertEquals('mock', $asset->getExtension());
    $this->assertEquals('mock/asset', $asset->getContentType());
  }
}

class MockAssetType extends \Packaged\Dispatch\Assets\AbstractAsset
{
  public function getExtension()
  {
    return 'mock';
  }

  public function getContentType()
  {
    return 'mock/asset';
  }
}
