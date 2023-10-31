<?php

namespace Packaged\Dispatch\Tests\Resources;

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\ResourceManager;
use Packaged\Dispatch\Resources\CssResource;
use Packaged\Dispatch\Resources\JavascriptResource;
use Packaged\Helpers\Path;
use PHPUnit\Framework\TestCase;

class AbstractDispatchableResourceTest extends TestCase
{
  public function testProcessesContent()
  {
    $root = Path::system(dirname(__DIR__), '_root');
    Dispatch::bind(new Dispatch($root))->addAlias('root', Path::system($root, Dispatch::RESOURCES_DIR));
    $manager = ResourceManager::resources();

    $resource = new CssResource();
    $resource->setManager($manager);
    $resource->setProcessingPath('css/test.css');
    $resource->setContent(file_get_contents(Path::system($root, Dispatch::RESOURCES_DIR, 'css', 'test.css')));
    $content = $resource->getContent();
    $this->assertStringContainsString('url(r/395d1a0e8999/img/x.jpg)', $content);
    $this->assertStringContainsString('url(\'r/942e325b86c0/css/css.jpg\')', $content);
    $this->assertStringContainsString('url("r/942e325be95f/css/sub/subimg.jpg")', $content);
    $this->assertStringContainsString('url(\'http://www.example.com/background.jpg\')', $content);
    $this->assertStringContainsString('url(../img/missing-file.jpg)', $content);
    $this->assertStringContainsString('url(../../../img/missing-file.jpg)', $content);

    $resource->setProcessingPath('css/do-not-modify.css');
    $resource->setContent(file_get_contents(Path::system($root, Dispatch::RESOURCES_DIR, 'css', 'do-not-modify.css')));
    $this->assertStringContainsString('url(../img/x.jpg)', $resource->getContent());
  }

  public function testJsContent()
  {
    $root = Path::system(dirname(__DIR__), '_root');
    Dispatch::bind(new Dispatch($root))->addAlias('root', Path::system($root, Dispatch::RESOURCES_DIR));
    $manager = ResourceManager::resources();

    $resource = new JavascriptResource();
    $resource->setManager($manager);
    $resource->setProcessingPath('js/url.min.js');
    $resource->setFilePath($manager->getFilePath('js/url.min.js'));
    $resource->setContent(file_get_contents(Path::system($root, Dispatch::RESOURCES_DIR, 'js', 'url.min.js')));
    $content = $resource->getContent();
    $this->assertStringContainsString('import test from \'./test\';', $content);
    $this->assertStringContainsString('import {default as alert} from \'r/f417133ec50f/js/alert.js\';', $content);
    $this->assertStringContainsString('import misc from \'r/b6ccf604ae88/js/misc.js\';', $content);
    $this->assertStringContainsString('"url(" + test(p) + ")"', $content);
  }
}
