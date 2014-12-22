<?php

class DispatchTest extends PHPUnit_Framework_TestCase
{
  public function testNonDispatchPath()
  {
    $opt        = [];
    $dispatcher = new \Packaged\Dispatch\Dispatch(new DummyKernel(), $opt);
    $response   = $dispatcher->handle(
      \Symfony\Component\HttpFoundation\Request::createFromGlobals()
    );
    $this->assertEquals('Original', $response->getContent());
  }

  public function testSetters()
  {
    $opts       = [];
    $opt        = new \Packaged\Config\Provider\ConfigSection('', $opts);
    $dispatcher = new \Packaged\Dispatch\Dispatch(new DummyKernel(), $opt);
    $dispatcher->setBaseDirectory(__DIR__);
    $this->assertEquals(__DIR__, $dispatcher->getBaseDirectory());
    $this->assertSame($dispatcher, $dispatcher->prepare());
    $dispatcher->setFileHashTable(['a' => '1']);
    $dispatcher->addFileHashEntry('b', '2');
    $this->assertEquals('1', $dispatcher->getFileHash('a'));
    $this->assertEquals('2', $dispatcher->getFileHash('b'));
  }

  /**
   * @param $config
   * @param $path
   * @param $host
   * @param $expect
   *
   * @dataProvider urlProvider
   */
  public function testUrls($config, $path, $host, $expect)
  {
    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', $host);
    $request->server->set('REQUEST_URI', '/' . $path);

    $dispatcher = new \Packaged\Dispatch\Dispatch(new DummyKernel(), $config);
    $resp       = $dispatcher->handle($request);
    $this->assertContains($expect, $resp->getContent());
  }

  public function urlProvider()
  {
    $baseConfig = [
      'source_dir' => 'tests',
      'assets_dir' => 'tests/asset',
      'aliases'    => [
        'tdir' => 'tests/asset',
        'ast2' => 'tests/asset2',
        'vend' => 'vendor'
      ],
      'css_config' => ['minify' => false]
    ];
    $tests      = [];

    $tests[] = [
      array_merge($baseConfig, []),
      '',
      'www.packaged.in',
      'Original'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/test.css',
      'www.packaged.in',
      'test.css could not be located'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/test.php',
      'www.packaged.in',
      '*.php files are not currently unsupported'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/',
      'www.packaged.in',
      'The URL you requested appears to be mythical'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/test',
      'www.packaged.in',
      'The URL you requested appears to be mythical'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/test.css',
      'www.packaged.in',
      'body { background: yellow ' .
      'url(\'//www.packaged.in/res/p/8cac7/b/d41d8cd/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/testurl.js',
      'www.packaged.in',
      'function myFunction()
{url();url(\'\');url(\'//www.packaged.in/res/p/8cac7/b/e2218e4/test.js\');}'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/test.js',
      'www.packaged.in',
      'function myFunction()
{alert("Hello\nHow are you?");}'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/test.css',
      'www.packaged.in',
      'body { background: yellow ' .
      'url(\'//www.packaged.in/res/p/8cac7/b/d41d8cd/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/tester.css',
      'www.packaged.in',
      'body { background: yellow ' .
      'url(\'//www.packaged.in/res/p/8cac7/b/d41d8cd/x.jpg?v1\'); }'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain//filehash/test.css',
      'www.packaged.in',
      'could not be located'
    ];

    $tests[] = [
      array_merge($baseConfig, ['assets_dir'=>'tests/empty']),
      'res/p/domain//filehash/test.css',
      'www.packaged.in',
      'could not be located'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/v/packaged/config/domain/b/filehash/composer.json',
      'www.packaged.in',
      'packaged/config'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/a/tdir/domain/b/filehash/test.css',
      'www.packaged.in',
      'body { background: yellow ' .
      'url(\'//www.packaged.in/res/a/tdir/8cac7/b/d41d8cd/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/a/ast2/domain/b/filehash/sub/test.css',
      'www.packaged.in',
      'body { background: yellow ' .
      'url(\'//www.packaged.in/res/a/ast2/8cac7/b/d41d8cd/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/a/ast2/domain/b/filehash/sub/tester.css',
      'www.packaged.in',
      'body { background: yellow ' .
      'url(\'//www.packaged.in/res/a/ast2/8cac7/b/d41d8cd/sub2/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/a/ast2/domain/b/filehash/sub/subsub/tester.css',
      'www.packaged.in',
      'body { background: yellow ' .
      'url(\'//www.packaged.in/res/a/ast2/8cac7/b/d41d8cd/sub/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge($baseConfig, ['source_dir' => 'vendor/']),
      'res/s/domain/pa456;co456/filehash/composer.json',
      'www.packaged.in',
      'packaged/config'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/s/domain/asc04e3/filehash/test.css',
      'www.packaged.in',
      'background: yellow'
    ];

    //Duplicate test to check cache
    $tests[] = [
      array_merge($baseConfig, []),
      'res/s/domain/asc04e3/filehash/test.css',
      'www.packaged.in',
      'background: yellow'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/s/domain/asd3a46/filehash/test.css',
      'www.packaged.in',
      'background: red'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/s/domain/asd3b46/filehash/test.css',
      'www.packaged.in',
      'could not be located'
    ];

    $tests[] = [
      array_merge($baseConfig, []),
      'res/a/noalias/domain/b/filehash/test.css',
      'www.packaged.in',
      'could not be located'
    ];

    $tests[] = [
      array_merge(
        $baseConfig,
        ['run_on' => 'subdomain', 'run_match' => 'static.']
      ),
      'a/tdir/domain/b/filehash/test.css',
      'static.packaged.in',
      'body { background: yellow ' .
      'url(\'//static.packaged.in/a/tdir/6ea79/b/d41d8cd/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge(
        $baseConfig,
        ['run_on' => 'domain', 'run_match' => 'static.packaged.tld']
      ),
      'a/tdir/domain/b/filehash/test.css',
      'static.packaged.tld',
      'body { background: yellow ' .
      'url(\'//static.packaged.tld/a/tdir/71084/b/d41d8cd/x.jpg\'); }'
    ];

    $tests[] = [
      array_merge(
        $baseConfig,
        ['run_on' => 'nothing']
      ),
      'a/tdir/domain/b/filehash/test.css',
      'static.packaged.tld',
      'Original'
    ];

    $tests[] = [
      array_merge(
        $baseConfig,
        ['run_on' => 'subdomain', 'run_match' => 'static.']
      ),
      'a/tdir/domain/b/filehash/no-parse-test.css',
      'static.packaged.in',
      'body { background: yellow url(x.jpg); }'
    ];

    /**
     * Do not minify .min.ext files
     * @link https://github.com/packaged/dispatch/issues/1
     * */
    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/test.min.js',
      'www.packaged.in',
      'function myFunction()
{
  alert("Hello\nHow are you?");
}'
    ];

    /**
     * Invalid dispatch links should remain as the default
     * @link https://github.com/packaged/dispatch/issues/5
     * */
    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/dispatch.test.js',
      'www.packaged.in',
      'function f(){setCss(\'background:url(https://),url(https://),'
      . 'red url(https://)\');return(/(url\s*\(.*?){3}/).'
      . 'test(mStyle.background);}'
    ];

    /**
     * Scss import paths should work correctly when accessed through dispatch
     */
    $tests[] = [
      array_merge($baseConfig, []),
      'res/p/domain/b/filehash/test-import.scss',
      'www.packaged.in',
      file_get_contents(__DIR__ . '/asset/test-import.expect.css')
    ];

    return $tests;
  }

  public function testTrigger()
  {
    $event = new \Packaged\Dispatch\DispatchEvent();
    NewDispatcher::clear();
    NewDispatcher::trigger($event);
    $this->assertNull($event->getResult());

    $request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->headers->set('HOST', 'packaged.in');
    $request->server->set('REQUEST_URI', '/');

    $dispatcher = new \Packaged\Dispatch\Dispatch(
      new DummyKernel(), ['source_dir' => 'tests']
    );
    $dispatcher->handle($request);
    $event = new \Packaged\Dispatch\DispatchEvent();
    $event->setFilename('test.css');
    $event->setMapType(\Packaged\Dispatch\DirectoryMapper::MAP_SOURCE);
    $event->setPath('asset');
    \Packaged\Dispatch\Dispatch::trigger($event);
    $expect = '//packaged.in/res/s/dfcbf/asc04e3/76d6c18/test.css';
    $this->assertEquals($expect, $event->getResult());
  }
}

class DummyKernel implements \Symfony\Component\HttpKernel\HttpKernelInterface
{
  public function handle(
    \Symfony\Component\HttpFoundation\Request $request,
    $type = self::MASTER_REQUEST, $catch = true
  )
  {
    return new \Symfony\Component\HttpFoundation\Response('Original');
  }
}

class NewDispatcher extends \Packaged\Dispatch\Dispatch
{
  public static function clear()
  {
    static::$dispatcher = null;
  }
}
