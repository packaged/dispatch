<?php

namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Resources\Image\JpgResource;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;

class AbstractResourceTest extends TestCase
{

  public function testFromFilePath()
  {
    $jpg = JpgResource::fromFilePath(Path::system(dirname(__DIR__), '_root', 'resources', 'img', 'x.jpg'));
    $this->assertEquals('d68e763c825dc0e388929ae1b375ce18', $jpg->getHash());
  }
}
