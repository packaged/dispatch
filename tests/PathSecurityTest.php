<?php

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\PathGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PathSecurityTest extends TestCase
{
  private $root;

  protected function setUp()
  {
    $this->root = sys_get_temp_dir() . '/dispatch-security-' . uniqid();
    foreach(['resources/nested', 'public', 'src', 'vendor/acme/package', 'resources-private'] as $dir)
    {
      mkdir($this->root . '/' . $dir, 0700, true);
      file_put_contents($this->root . '/' . $dir . '/asset.css', 'safe asset');
    }
    $this->root = realpath($this->root);
    file_put_contents($this->root . '/secret.css', 'private configuration');
    file_put_contents($this->root . '/src/Secret.php', '<?php private source');
    file_put_contents($this->root . '/resources/safe.css', 'safe asset');
    file_put_contents($this->root . '/resources/space name.css', 'safe asset');
    symlink($this->root . '/secret.css', $this->root . '/resources/leak.css');
    symlink($this->root . '/resources-private', $this->root . '/resources/escape');
    symlink($this->root . '/resources/safe.css', $this->root . '/resources/inside.css');
    symlink($this->root . '/resources', $this->root . '/linked-resources');
    symlink($this->root . '/absent', $this->root . '/resources/broken.css');
  }

  protected function tearDown()
  {
    $files = new RecursiveIteratorIterator(
      new RecursiveDirectoryIterator($this->root, FilesystemIterator::SKIP_DOTS),
      RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach($files as $file)
    {
      if($file->isDir() && !$file->isLink())
      {
        rmdir($file->getPathname());
      }
      else
      {
        unlink($file->getPathname());
      }
    }
    rmdir($this->root);
  }

  public function testCanonicalBoundary()
  {
    $base = $this->root . '/resources';
    foreach(['leak.css', 'escape/asset.css', '../secret.css', 'broken.css', 'missing.css'] as $file)
    {
      $this->assertNull(PathGuard::resolve($base, $base . '/' . $file), $file);
    }
    $this->assertSame($base . '/safe.css', PathGuard::resolve($base, $base . '/inside.css'));
    $this->assertSame($base . '/safe.css', PathGuard::resolve($this->root . '/linked-resources', $base . '/safe.css'));
  }

  private function dispatch()
  {
    return (new Dispatch(new SecurityTestKernel(), [
      'assets_dir' => 'resources', 'source_dir' => 'src',
      'aliases' => ['files' => 'resources'],
      'css_config' => ['minify' => false],
    ]))->setBaseDirectory($this->root);
  }

  private function assertNotServed($path)
  {
    $response = $this->dispatch()->getResponseForPath($path, Request::create('/' . $path));
    $this->assertSame(404, $response->getStatusCode(), $path);
  }

  public function testTraversalRequests()
  {
    foreach(['p/4c7cd/b/12d6d74/', 's/domain/b/hash/', 'a/files/domain/b/hash/', 'v/acme/package/domain/b/hash/'] as $prefix)
    {
      foreach(['../secret.css', '..%2Fsecret.css', '%2e%2E%2fsecret.css', '..%5csecret.css', '%00secret.css', '%252e%252e%252fsecret.css', 'missing.css', '..%2F..%2F..%2F..%2Fsecret.css'] as $file)
      {
        $this->assertNotServed($prefix . $file);
      }
    }
    foreach(['p/domain/b/hash/leak.css', 'p/domain/b/hash/escape/asset.css', 'p/domain/es123/hash/asset.css', 'v/../resources/domain/b/hash/safe.css', 'p/domain/b/hash/broken.css'] as $path)
    {
      $this->assertNotServed($path);
    }
    $request = Request::create('/res/p/4c7cd/b/12d6d74/..%2Fsecret.css');
    $request->server->set('HTTP_IF_MODIFIED_SINCE', 'Mon, 28 Sep 2026 06:00:00 GMT');
    $this->assertSame(404, $this->dispatch()->handle($request)->getStatusCode());
  }

  public function testUnknownAliasDoesNotMapToBaseDirectory()
  {
    $this->assertNotServed('a/unknown/domain/b/hash/secret.css');
  }

  public function testSourceFilesAreNotServed()
  {
    $this->assertNotServed('s/domain/b/hash/Secret.php');
  }

  public function testNotFoundIsNotRenderedAsHtml()
  {
    $path = 'p/domain/b/hash/<b>missing</b>.css';
    $response = $this->dispatch()->getResponseForPath($path, Request::create('/' . rawurlencode($path)));
    $this->assertSame(404, $response->getStatusCode());
    $this->assertSame('text/plain', $response->headers->get('Content-Type'));
    $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
  }

  public function testOldCachedLeakIsNotServed()
  {
    if(!function_exists('apcu_enabled') || !apcu_enabled())
    {
      $this->markTestSkipped('Enable apc.enable_cli to test cached responses');
    }
    $request = Request::create('/res/p/4c7cd/b/12d6d74/..%2Fsecret.css');
    $key = 'dsptch:' . base64_encode($request->getUri());
    apcu_store($key, new \Symfony\Component\HttpFoundation\Response('private configuration'));
    try
    {
      $this->assertSame(404, $this->dispatch()->handle($request)->getStatusCode());
    }
    finally
    {
      apcu_delete($key);
    }
  }

  public function testValidRequests()
  {
    $dispatch = $this->dispatch();
    foreach(['p/domain/b/hash/safe.css', 'p/domain/b/hash/nested/asset.css', 'p/domain/b/hash/space%20name.css', 'p/domain/b/hash/inside.css', 'a/files/domain/b/hash/safe.css', 'v/acme/package/domain/b/hash/asset.css', 's/domain/b/hash/asset.css'] as $path)
    {
      $response = $dispatch->getResponseForPath($path, Request::create('/' . $path));
      $this->assertSame(200, $response->getStatusCode(), $path);
      $this->assertSame('safe asset', $response->getContent());
    }
  }
}

class SecurityTestKernel implements \Symfony\Component\HttpKernel\HttpKernelInterface
{
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = true)
  {
    return new \Symfony\Component\HttpFoundation\Response('Original');
  }
}
