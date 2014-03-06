<?php

class ResourceGeneratorTest extends PHPUnit_Framework_TestCase
{
  public function testHash()
  {
    $kernel = $this->getMock(
      '\Symfony\Component\HttpKernel\HttpKernelInterface'
    );
    /**
     * @var $kernel Symfony\Component\HttpKernel\HttpKernelInterface
     */
    $dispatch = new \Packaged\Dispatch\Dispatch($kernel, []);
    $request  = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $gen      = new \Packaged\Dispatch\ResourceGenerator($dispatch, $request);

    $this->assertEquals('8cac7', $gen->hashDomain('www.packaged.in'));
    $this->assertEquals(
      'b05403212c66bdc8ccc597fedf6cd5fe',
      $gen->getFileHash(
        __DIR__ . DIRECTORY_SEPARATOR . 'asset' .
        DIRECTORY_SEPARATOR . 'checksum.txt'
      )
    );
  }

  /**
   * @dataProvider eventProvider
   *
   * @param \Packaged\Dispatch\DispatchEvent $event
   * @param                                  $dispatch
   * @param                                  $request
   * @param                                  $expect
   * @param                                  $run
   */
  public function testEvents(
    \Packaged\Dispatch\DispatchEvent $event, $dispatch, $request, $expect,
    $run = 1
  )
  {
    $gen = new \Packaged\Dispatch\ResourceGenerator($dispatch, $request);
    for($i = 0; $i < $run; $i++)
    {
      $gen->processEvent($event);
    }
    $this->assertEquals($expect, $event->getResult());
  }

  public function eventProvider()
  {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', 'packaged.in');
    $tests = [];

    $dispatch = $this->getDispatch();
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('test.css');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_SOURCE);
    $event->setPath('asset');
    $expect  = '//packaged.in/res/s/dfcbf/asc04e3/edc2182/test.css';
    $tests[] = [$event, $dispatch, $request, $expect];

    $dispatch = $this->getDispatch(['run_on' => 'subdomain']);
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('test.css');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_SOURCE);
    $event->setPath('asset');
    $expect  = '//static.packaged.in/s/dfcbf/asc04e3/edc2182/test.css';
    $tests[] = [$event, $dispatch, $request, $expect];

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', 'www.packaged.in');

    $dispatch = $this->getDispatch(['run_on' => 'subdomain']);
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('test.css');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_SOURCE);
    $event->setPath('asset');
    $expect  = '//static.packaged.in/s/8cac7/asc04e3/edc2182/test.css';
    $tests[] = [$event, $dispatch, $request, $expect];

    $dispatch = $this->getDispatch(
      ['run_on' => 'domain', 'run_match' => 'packagedstatic.com']
    );
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('test.css');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_SOURCE);
    $event->setPath('asset');
    $expect  = '//packagedstatic.com/s/8cac7/asc04e3/edc2182/test.css';
    $tests[] = [$event, $dispatch, $request, $expect];

    $dispatch = $this->getDispatch();
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('composer.json');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_VENDOR);
    $event->setPath('');
    $event->setLookupParts(['packaged', 'config']);
    $expect  = '//www.packaged.in/res/v/packaged/' .
      'config/8cac7/b/90c1901/composer.json';
    $tests[] = [$event, $dispatch, $request, $expect];

    $dispatch = $this->getDispatch();
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('missing.composer.json');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_VENDOR);
    $event->setPath('');
    $event->setLookupParts(['packaged', 'config']);
    $expect  = null;
    $tests[] = [$event, $dispatch, $request, $expect];

    $dispatch = $this->getDispatch(
      ['aliases' => ['cfger' => 'vendor/packaged/config']]
    );
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('composer.json');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_ALIAS);
    $event->setLookupParts(['cfger']);
    $expect  = '//www.packaged.in/res/a/cfger/8cac7/b/90c1901/composer.json';
    $tests[] = [$event, $dispatch, $request, $expect];

    $dispatch = $this->getDispatch(
      ['assets_dir' => 'tests/asset']
    );
    $event    = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('test.css');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_ASSET);
    $expect  = '//www.packaged.in/res/p/8cac7/b/edc2182/test.css';
    $tests[] = [$event, $dispatch, $request, $expect, 2];

    return $tests;
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
