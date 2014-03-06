<?php

class CssAssetTest extends PHPUnit_Framework_TestCase
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

    $asset = new \Packaged\Dispatch\Assets\CssAsset();

    $asset->setContent($original);
    $this->assertEquals('body{background:red}', $asset->getContent());

    $asset->setContent($nominify);
    $this->assertEquals($nominify, $asset->getContent());

    $asset->setContent($original);
    $asset->setOptions(['minify' => 'false']);
    $this->assertEquals($original, $asset->getContent());
  }

  public function testAsset()
  {
    $asset = new \Packaged\Dispatch\Assets\CssAsset();
    $this->assertEquals('css', $asset->getExtension());
    $this->assertEquals('text/css', $asset->getContentType());
  }
}
