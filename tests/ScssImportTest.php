<?php

class ScssImportTest extends PHPUnit_Framework_TestCase
{

  public function testScssImports()
  {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', 'packaged.in');

    $dispatch = $this->getDispatch(
      ['assets_dir' => 'tests/asset3']
    );
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('test.scss');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_ASSET);

    $gen = new \Packaged\Dispatch\ResourceGenerator($dispatch, $request);
    $gen->processEvent($event);

    $url     = $event->getResult();
    $urlInfo = parse_url($url);

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', $urlInfo['host']);
    $request->server->set('REQUEST_URI', $urlInfo['path']);

    $expect = file_get_contents(__DIR__ . '/asset3/' . 'expect.css');
    $this->assertEquals($expect, $dispatch->handle($request));
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
