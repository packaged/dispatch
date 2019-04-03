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
    $this->assertContains('url(r/395d1a0e1a36/img/x.jpg)', $content);
    $this->assertContains('url(\'r/942e325b9de6/css/css.jpg\')', $content);
    $this->assertContains('url("r/942e325be1fb/css/sub/subimg.jpg")', $content);
    $this->assertContains('url(\'http://www.example.com/background.jpg\')', $content);
    $this->assertContains('url(../img/missing-file.jpg)', $content);
    $this->assertContains('url(../../../img/missing-file.jpg)', $content);

    $resource->setProcessingPath('css/do-not-modify.css');
    $resource->setContent(file_get_contents(Path::system($root, Dispatch::RESOURCES_DIR, 'css', 'do-not-modify.css')));
    $this->assertContains('url(../img/x.jpg)', $resource->getContent());
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
    $this->assertContains('import test from \'./test\';', $content);
    $this->assertContains('import {default as alert} from \'r/f417133e49d9/js/alert.js\';', $content);
    $this->assertContains('import misc from \'r/b6ccf604cd1d/js/misc.js\';', $content);
    $this->assertContains('"url(" + test(p) + ")"', $content);
  }
}
