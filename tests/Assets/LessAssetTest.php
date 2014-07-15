<?php

class LessAssetTest extends PHPUnit_Framework_TestCase
{
  public function testCompile()
  {
    $original = '@base: 24px;
@border-color: #B2B;

.underline { border-bottom: 1px solid green }

#header {
  color: black;
  border: 1px solid @border-color + #222222;

  .navigation {
    font-size: @base / 2;
    a {
      .underline;
    }
  }
  .logo {
    width: 300px;
    :hover { text-decoration: none }
  }
}
';

    $expect = '.underline {
  border-bottom: 1px solid green;
}
#header {
  color: black;
  border: 1px solid #dd44dd;
}
#header .navigation {
  font-size: 12px;
}
#header .navigation a {
  border-bottom: 1px solid green;
}
#header .logo {
  width: 300px;
}
#header .logo :hover {
  text-decoration: none;
}
';

    $asset = new \Packaged\Dispatch\Assets\LessAsset();

    $asset->setContent($original);
    $this->assertEquals($expect, $asset->getContent());
  }

  public function testAsset()
  {
    $asset = new \Packaged\Dispatch\Assets\LessAsset();
    $this->assertEquals('less', $asset->getExtension());
    $this->assertEquals('text/css', $asset->getContentType());
  }
}
