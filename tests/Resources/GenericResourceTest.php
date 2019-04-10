<?php
namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Resources\AbstractResource;
use Packaged\Dispatch\Resources\DispatchResource;
use Packaged\Dispatch\Resources\ResourceFactory;
use Packaged\Dispatch\Resources\UnknownResource;
use PHPUnit\Framework\TestCase;

class GenericResourceTest extends TestCase
{
  /**
   * @dataProvider resourceProvider
   *
   * @param          $ext
   * @param DispatchResource $class
   */
  public function testResource($ext, DispatchResource $class)
  {
    $extType = [
      "ico"   => "image/x-icon",
      "css"   => "text/css",
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
      "webm"  => "video/webm",
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
      null    => "application/octet-stream",
    ];

    $this->assertEquals($ext, $class->getExtension());
    $this->assertEquals($extType[$ext], $class->getContentType());
  }

  public function resourceProvider()
  {
    $attempt = [];
    $attempt[] = [null, new UnknownResource()];
    foreach(ResourceFactory::getExtensions() as $ext)
    {
      $attempt[] = [$ext, ResourceFactory::getExtensionResource($ext)];
    }
    return $attempt;
  }

  /**
   * @throws \ReflectionException
   */
  public function testAbstract()
  {
    $resource = $this->getMockForAbstractClass(AbstractResource::class);

    /**
     * @var $resource \Packaged\Dispatch\Resources\AbstractResource
     */
    $resource->setContent('test content');
    $this->assertEquals('test content', $resource->getContent());

    $this->assertEmpty($resource->getOptions());

    $resource->setOption('first', 'val');
    $this->assertEquals(['first' => 'val'], $resource->getOptions());

    $resource->setOptions(['second' => 2, 'third' => true]);
    $this->assertEquals(
      ['first' => 'val', 'second' => 2, 'third' => true],
      $resource->getOptions()
    );

    $resource->clearOptions();
    $this->assertEmpty($resource->getOptions());

    $this->assertEquals(md5($resource->getContent()), $resource->getHash());
    $resource->setHash('abcdef');
    $this->assertEquals('abcdef', $resource->getHash());
  }
}
