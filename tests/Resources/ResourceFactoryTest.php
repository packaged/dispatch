<?php

namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Resources\CssResource;
use Packaged\Dispatch\Resources\ResourceFactory;
use Packaged\Dispatch\Resources\UnknownResource;
use Packaged\Dispatch\Resources\ZipResource;
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
}
