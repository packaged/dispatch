<?php

namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Resources\CssResource;
use Packaged\Dispatch\Resources\Image\IconResource;
use Packaged\Dispatch\Resources\ResourceFactory;
use Packaged\Dispatch\Resources\UnknownResource;
use Packaged\Dispatch\Resources\ZipResource;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ResourceFactoryTest extends TestCase
{

  public function testGetExtensions()
  {
    $this->assertContains('png', ResourceFactory::getExtensions());
  }

  public function testGetExtensionResource()
  {
    $this->assertInstanceOf(ZipResource::class, ResourceFactory::getExtensionResource('zip'));
    $this->assertInstanceOf(UnknownResource::class, ResourceFactory::getExtensionResource('wjeg'));
  }

  public function testAddExtension()
  {
    ResourceFactory::addExtension('tar', ZipResource::class);
    $this->assertInstanceOf(ZipResource::class, ResourceFactory::getExtensionResource('tar'));

    ResourceFactory::addExtension('gz', new ZipResource());
    $this->assertInstanceOf(ZipResource::class, ResourceFactory::getExtensionResource('gz'));
  }

  public function testCreate()
  {
    $resource = new CssResource();
    $resource->setContent('body{ background:red; }');
    $response = ResourceFactory::create($resource);
    $this->assertInstanceOf(Response::class, $response);
    $this->assertEquals($resource->getContentType(), $response->headers->get('Content-Type'));
  }

  public function testFavicon()
  {
    $path = Path::system(dirname(__DIR__), '_root', 'public', 'favicon.ico');
    $full = ResourceFactory::create(IconResource::fromFilePath($path));
    $fromFile = ResourceFactory::fromFile($path);
    $this->assertEquals($full, $fromFile);
    $this->assertEquals('"40ed4027e1e5f15c19a2fb287bcc3724"', $fromFile->getEtag());
  }

  public function testMissingFile()
  {
    $fromFile = ResourceFactory::fromFile('invalidfile');
    $this->assertInstanceOf(Response::class, $fromFile);
    $this->assertEquals(404, $fromFile->getStatusCode());
  }
}
