<?php

class JavascriptAssetTest extends PHPUnit_Framework_TestCase
{
  public function testMinify()
  {
    $original = 'function myFunction()
    {
      alert("Hello\nHow are you?");
    }';

    $nominify = '@' . 'do-not-minify
    function myFunction()
    {
      alert("Hello\nHow are you?");
    }';

    $asset = new \Packaged\Dispatch\Assets\JavascriptAsset();

    $asset->setContent($original);
    $this->assertEquals(
      'function myFunction(){alert("Hello\nHow are you?");}',
      $asset->getContent()
    );

    $asset->setContent($nominify);
    $this->assertEquals($nominify, $asset->getContent());

    $asset->setContent($original);
    $asset->setOptions(['minify' => 'false']);
    $this->assertEquals($original, $asset->getContent());
  }

  public function testAsset()
  {
    $asset = new \Packaged\Dispatch\Assets\JavascriptAsset();
    $this->assertEquals('js', $asset->getExtension());
    $this->assertEquals('text/javascript', $asset->getContentType());
  }
}
