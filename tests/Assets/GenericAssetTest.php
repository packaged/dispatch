<?php

class GenericAssetTest extends PHPUnit_Framework_TestCase
{
  /**
   * @dataProvider assetProvider
   */
  public function testAsset($ext, \Packaged\Dispatch\Assets\IAsset $class)
  {
    $extType = [
      "ico"   => "image/x-icon",
      "css"   => "text/css",
      "less"  => "text/css",
      "scss"  => "text/css",
      "js"    => "text/javascript",
      "json"  => "application/json",
      "png"   => "image/png",
      "jpg"   => "image/jpeg",
      "jpeg"  => "image/jpeg",
      "gif"   => "image/gif",
      "swf"   => "application/x-shockwave-flash",
      "flv"   => "video/x-flv",
      "mp4"   => "video/mp4",
      "mpeg"  => "video/mpeg",
      "mov"   => "video/quicktime",
      "ttf"   => "application/x-font-ttf",
      "ttc"   => "application/x-font-ttc",
      "pfb"   => "application/x-font-pfb",
      "pfm"   => "application/x-font-pfm",
      "otf"   => "application/x-font-opentype",
      "dfont" => "application/x-font-dfont",
      "pfa"   => "application/x-font-pfa",
      "afm"   => "application/x-font-afm",
      "svg"   => "image/svg+xml",
      "eot"   => "application/vnd.ms-fontobject",
      "woff"  => "application/x-font-woff",
      "zip"   => "application/zip",
      "pdf"   => "application/pdf",
    ];

    $this->assertEquals($ext, $class->getExtension());
    $this->assertEquals($extType[$ext], $class->getContentType());
  }

  public function assetProvider()
  {
    $attempt = [];
    $resp    = new \Packaged\Dispatch\AssetResponse();
    foreach(\Packaged\Dispatch\AssetResponse::$assetMap as $ext => $class)
    {
      $attempt[] = [$ext, $resp->assetByExtension($ext)];
    }
    return $attempt;
  }

  public function testAbstract()
  {
    $asset = $this->getMockForAbstractClass(
      '\Packaged\Dispatch\Assets\AbstractAsset'
    );

    /**
     * @var $asset \Packaged\Dispatch\Assets\AbstractAsset
     */
    $asset->setContent('test content');
    $this->assertEquals('test content', $asset->getContent());

    $this->assertEmpty($asset->getOptions());

    $asset->setOption('first', 'val');
    $this->assertEquals(['first' => 'val'], $asset->getOptions());

    $asset->setOptions(['second' => 2, 'third' => true]);
    $this->assertEquals(
      ['first' => 'val', 'second' => 2, 'third' => true],
      $asset->getOptions()
    );

    $asset->clearOptions();
    $this->assertEmpty($asset->getOptions());
  }
}
