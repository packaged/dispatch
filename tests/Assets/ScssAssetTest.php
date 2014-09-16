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

  public function testAsset()
  {
    $asset = new \Packaged\Dispatch\Assets\ScssAsset();
    $this->assertEquals('scss', $asset->getExtension());
    $this->assertEquals('text/css', $asset->getContentType());
  }
}
