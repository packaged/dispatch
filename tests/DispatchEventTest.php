<?php

class DispatchEventTest extends PHPUnit_Framework_TestCase
{
  public function testAccess()
  {
    $event = new \Packaged\Dispatch\DispatchEvent();

    $event->setFilename("filename");
    $this->assertEquals("filename", $event->getFilename());

    $event->setResult("result");
    $this->assertEquals("result", $event->getResult());

    $event->setLookupParts(["lookup"]);
    $this->assertEquals(["lookup"], $event->getLookupParts());

    $event->setMapType("maptype");
    $this->assertEquals("maptype", $event->getMapType());

    $event->setPath("path");
    $this->assertEquals("path", $event->getPath());
  }
}
