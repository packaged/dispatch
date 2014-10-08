<?php

class ScssImportTest extends PHPUnit_Framework_TestCase
{

  public function testScssImports()
  {
    $expect = file_get_contents(__DIR__ . '/asset3/' . 'expect.css');

    $asset = new \Packaged\Dispatch\Assets\ScssAsset();
    $am    = RelativePathAssetManager::assetType();
    $am->setRelativePath(__DIR__ . '/asset3/');
    $asset->setAssetManager($am);
    $asset->setContent(file_get_contents(__DIR__ . '/asset3/' . 'test.scss'));

    $this->assertEquals($expect, $asset->getContent());
  }

  public function getDispatch(array $options = [])
  {
    $options = array_merge(['source_dir' => 'tests'], $options);
    $kernel  = $this->getMock(
      '\Symfony\Component\HttpKernel\HttpKernelInterface'
    );
    /**
     * @var $kernel Symfony\Component\HttpKernel\HttpKernelInterface
     */
    $dispatch = new \Packaged\Dispatch\Dispatch($kernel, $options);
    $dispatch->setBaseDirectory(dirname(__DIR__));
    return $dispatch;
  }
}

class RelativePathAssetManager extends \Packaged\Dispatch\AssetManager
{
  public function setRelativePath($path)
  {
    $this->_path = $path;
    return $this;
  }
}
