<?php

class ScssAssetTest extends PHPUnit_Framework_TestCase
{
  public function testCompile()
  {
    $original = '.navigation {
    ul {
        line-height: 20px;
        color: blue;
        a {
            color: red;
        }
    }
}

.footer {
    .copyright {
        color: silver;
    }
}';

    $expect = '.navigation ul {
  line-height: 20px;
  color: blue; }
  .navigation ul a {
    color: red; }

.footer .copyright {
  color: silver; }
';
    $asset  = new \Packaged\Dispatch\Assets\ScssAsset();

    $asset->setContent($original);
    $this->assertEquals($expect, $asset->getContent());
  }

  public function testCompileScssImports()
  {
    $testPath = __DIR__ . '/../asset3/';
    $content  = file_get_contents($testPath . 'test.scss');
    $expect   = file_get_contents($testPath . 'expect.css');

    $asset = new \Packaged\Dispatch\Assets\ScssAsset();
    $asset->setContent($content);
    $asset->setImportPath(realpath($testPath));

    $this->assertEquals($expect, $asset->getContent());
  }

  public function testImportPath() {
    $asset = new \Packaged\Dispatch\Assets\ScssAsset();
    $asset->setImportPath(__DIR__);
    $this->assertEquals(__DIR__, $asset->getImportPath());
  }

  public function testAsset()
  {
    $asset = new \Packaged\Dispatch\Assets\ScssAsset();
    $this->assertEquals('scss', $asset->getExtension());
    $this->assertEquals('text/css', $asset->getContentType());
  }
}
