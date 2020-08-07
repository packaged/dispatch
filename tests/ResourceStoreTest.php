<?php

namespace Packaged\Dispatch\Tests;

use Packaged\Dispatch\ResourceStore;
use PHPUnit\Framework\TestCase;

class ResourceStoreTest extends TestCase
{

  public function testClearStore()
  {
    $store = new ResourceStore();
    $store->requireCss('css/test.css');
    $store->requireJs('js/alert.js');
    $this->assertNotEmpty($store->generateHtmlIncludes(ResourceStore::TYPE_CSS));
    $store->clearStore();
    $this->assertEmpty($store->generateHtmlIncludes(ResourceStore::TYPE_CSS));

    $store->requireJs('js/alert.js');
    $store->requireCss('css/test.css');
    $store->clearStore(ResourceStore::TYPE_JS);
    $this->assertEmpty($store->generateHtmlIncludes(ResourceStore::TYPE_JS));
    $this->assertNotEmpty($store->generateHtmlIncludes(ResourceStore::TYPE_CSS));
  }

  public function testPriority()
  {
    $store = new ResourceStore();
    $store->requireCss('css/test.css');
    $store->requireCss('css/low.css', null, ResourceStore::PRIORITY_LOW);
    $store->requireCss('css/high.css', null, ResourceStore::PRIORITY_HIGH);

    $this->assertEquals(
      [
        'css/high.css',
        'css/test.css',
        'css/low.css',
      ],
      array_keys($store->getResources(ResourceStore::TYPE_CSS))
    );

    $this->assertEquals(
      [
        'css/high.css',
        'css/test.css',
      ],
      array_keys($store->getResources(ResourceStore::TYPE_CSS, null, [ResourceStore::PRIORITY_LOW]))
    );

    $this->assertEquals(
      ['css/high.css'],
      array_keys($store->getResources(ResourceStore::TYPE_CSS, ResourceStore::PRIORITY_HIGH))
    );
  }

  public function testPreload()
  {
    $store = new ResourceStore();
    $store->requireCss('css/test.css');
    $store->requireCss('css/preload.css', [], ResourceStore::PRIORITY_PRELOAD);

    $this->assertEquals(
      [
        'css/preload.css',
        'css/test.css',
      ],
      array_keys($store->getResources(ResourceStore::TYPE_CSS))
    );

    $this->assertEquals(
      ['css/preload.css'],
      array_keys($store->getResources(ResourceStore::TYPE_CSS, ResourceStore::PRIORITY_PRELOAD))
    );
    $this->assertEquals(
      ['css/preload.css'],
      array_keys($store->getResources(ResourceStore::TYPE_CSS, -1))
    );
  }

  public function testGenerateHtmlIncludes()
  {
    $store = new ResourceStore();
    $store->requireCss('css/test.css');
    $store->requireJs('js/alert.js');
    $store->requireJs('js/defer.js', ['defer' => null, 'type' => 'application/javascript']);
    $store->requireInlineJs("alert('hi');");
    $store->requireInlineCss("body{background:red;}");
    $this->assertContains('href="css/test.css"', $store->generateHtmlIncludes(ResourceStore::TYPE_CSS));
    $this->assertContains('src="js/alert.js"', $store->generateHtmlIncludes(ResourceStore::TYPE_JS));
    $this->assertContains(
      'src="js/defer.js" defer type="application/javascript"',
      $store->generateHtmlIncludes(ResourceStore::TYPE_JS)
    );
    $this->assertContains('<script>alert(\'hi\');</script>', $store->generateHtmlIncludes(ResourceStore::TYPE_JS));
    $this->assertContains(
      '<style>body{background:red;}</style>',
      $store->generateHtmlIncludes(ResourceStore::TYPE_CSS)
    );
  }
}
