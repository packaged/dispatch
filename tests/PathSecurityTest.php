<?php

use Packaged\Dispatch\Dispatch;
use Packaged\Dispatch\PathGuard;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PathSecurityTest extends TestCase
{
  private $root;

  protected function setUp(): void
  {
    $this->root = sys_get_temp_dir() . '/dispatch-security-' . uniqid();
    foreach(['resources/nested', 'public', 'src/_resources', 'vendor/acme/package', 'resources-private'] as $dir)
    {
      mkdir($this->root . '/' . $dir, 0700, true);
      file_put_contents($this->root . '/' . $dir . '/asset.json', 'safe asset');
    }
    $this->root = realpath($this->root);
    file_put_contents($this->root . '/secret.ini', 'private configuration');
    file_put_contents($this->root . '/resources/safe.json', 'safe asset');
    file_put_contents($this->root . '/resources/space name.json', 'safe asset');
    symlink($this->root . '/secret.ini', $this->root . '/resources/leak.json');
    symlink($this->root . '/resources-private', $this->root . '/resources/escape');
    symlink($this->root . '/resources/safe.json', $this->root . '/resources/inside.json');
    symlink($this->root . '/resources', $this->root . '/linked-resources');
    symlink($this->root . '/absent', $this->root . '/resources/broken.json');
  }

  protected function tearDown(): void
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
    foreach(['leak.json', 'escape/asset.json', '../secret.ini', 'broken.json', 'missing.json'] as $file)
    {
      $this->assertNull(PathGuard::resolve($base, $base . '/' . $file), $file);
    }
    $this->assertSame($base . '/safe.json', PathGuard::resolve($base, $base . '/inside.json'));
    $this->assertSame($base . '/safe.json', PathGuard::resolve($this->root . '/linked-resources', $base . '/safe.json'));
  }

  private function dispatch()
  {
    file_put_contents($this->root . '/src/Component.php', '<?php');
    $loader = new \Composer\Autoload\ClassLoader();
    $loader->addClassMap(['SecurityComponent' => $this->root . '/src/Component.php']);
    return (new Dispatch($this->root, '/assets', $loader))->addAlias('files', 'resources');
  }

  private function request($dispatch, $map, $base, $file)
  {
    // A valid, attacker-computable relative hash must never bypass containment.
    $relative = $base . '/' . urldecode($file);
    $hash = '00000000' . $dispatch->generateHash($relative, 4);
    return $dispatch->handleRequest(Request::create('/assets/' . $map . '/' . $hash . '/' . $file));
  }

  public function testTraversalRequests()
  {
    $dispatch = $this->dispatch();
    foreach(['r' => 'resources', 'p' => 'public', 'a/files' => 'resources', 'v/acme/package' => 'vendor/acme/package', 'c/1/SecurityComponent' => 'src/_resources'] as $map => $base)
    {
      foreach(['../secret.ini', '..%2Fsecret.ini', '%2e%2E%2fsecret.ini', '..%5csecret.ini', '%00secret.ini', '%252e%252e%252fsecret.ini', 'missing.json'] as $file)
      {
        $this->assertSame(404, $this->request($dispatch, $map, $base, $file)->getStatusCode(), $map . '/' . $file);
      }
    }
    foreach(['leak.json', 'escape/asset.json', 'broken.json', 'nested'] as $file)
    {
      $this->assertSame(404, $this->request($dispatch, 'r', 'resources', $file)->getStatusCode(), $file);
    }
    foreach(['v/../resources', 'v/acme/..', 'a/unknown'] as $map)
    {
      $this->assertSame(404, $this->request($dispatch, $map, '', 'secret.ini')->getStatusCode(), $map);
    }
  }

  public function testValidRequests()
  {
    $dispatch = $this->dispatch();
    foreach(['safe.json', 'nested/asset.json', 'space%20name.json'] as $file)
    {
      $response = $this->request($dispatch, 'r', 'resources', $file);
      $this->assertSame(200, $response->getStatusCode());
      $this->assertSame('safe asset', $response->getContent());
    }
    $manager = \Packaged\Dispatch\ResourceManager::resources([], $dispatch);
    $uri = $manager->getResourceUri('inside.json');
    $this->assertSame(200, $dispatch->handleRequest(Request::create($uri))->getStatusCode());
    $this->assertSame('', $manager->getFilePath('leak.json'));
    $this->expectException(\RuntimeException::class);
    $manager->getFilePath('../secret.ini');
  }

  public function testOnlyRegisteredFileTypesAreServed()
  {
    $dispatch = $this->dispatch();
    foreach(['r' => 'resources', 'p' => 'public', 'a/files' => 'resources', 'v/acme/package' => 'vendor/acme/package', 'c/1/SecurityComponent' => 'src/_resources'] as $map => $base)
    {
      foreach(['source.php', 'source.go', 'config.ini', '.env', 'unknown.xyz', 'README', 'source.php.json'] as $file)
      {
        $path = $this->root . '/' . $base . '/' . $file;
        if($file === 'source.php.json')
        {
          if(!is_link($path))
          {
            symlink($this->root . '/' . $base . '/source.php', $path);
          }
        }
        else
        {
          file_put_contents($path, 'private content');
        }
        $response = $this->request($dispatch, $map, $base, $file);
        $this->assertSame(404, $response->getStatusCode(), $map . '/' . $file);
        $this->assertSame('File Not Found', $response->getContent());
      }
      foreach(['json' => 'application/json', 'map' => 'application/json', 'mp3' => 'audio/mpeg', 'woff2' => 'font/woff2', 'webp' => 'image/webp', 'JSON' => 'application/json'] as $ext => $mime)
      {
        $file = 'asset.' . $ext;
        file_put_contents($this->root . '/' . $base . '/' . $file, 'safe asset');
        $response = $this->request($dispatch, $map, $base, $file);
        $this->assertSame(200, $response->getStatusCode(), $map . '/' . $file);
        $this->assertSame('safe asset', $response->getContent());
        $this->assertSame($mime, $response->headers->get('Content-Type'));
      }
    }
  }

  public function testCustomRegisteredFileTypeIsServed()
  {
    \Packaged\Dispatch\Resources\ResourceFactory::addExtension('customjson', \Packaged\Dispatch\Resources\JsonResource::class);
    file_put_contents($this->root . '/resources/asset.customjson', '{}');
    $response = $this->request($this->dispatch(), 'r', 'resources', 'asset.customjson');
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('{}', $response->getContent());
    $this->assertSame('application/json', $response->headers->get('Content-Type'));
  }

  public function testDerivedFilesStayInsideMapping()
  {
    $dispatch = $this->dispatch();
    $manager = \Packaged\Dispatch\ResourceManager::resources([], $dispatch);
    file_put_contents($this->root . '/resources/app.js', 'const safe = true;');
    symlink($this->root . '/secret.ini', $this->root . '/resources/app.js.map');
    $resource = new \Packaged\Dispatch\Resources\JavascriptResource();
    $resource->setManager($manager);
    $resource->setProcessingPath('app.js');
    $resource->setFilePath($manager->getFilePath('app.js'));
    $resource->setContent('const safe = true;');
    $resource->setOptions(['sourcemap' => true, 'minify' => false, 'dispatch' => false]);
    $this->assertSame('const safe = true;', $resource->getContent());

    file_put_contents($this->root . '/resources/image.png', 'image');
    symlink($this->root . '/secret.ini', $this->root . '/resources/image.png.webp');
    $dispatch->setAcceptableContentTypes(['image/webp']);
    $dispatch->config()->addItem('optimisation', 'webp', true);
    $manager->setOption(\Packaged\Dispatch\ResourceManager::OPT_THROW_ON_FILE_NOT_FOUND, false);
    $this->assertNull($manager->getResourceUri('image.png'));
  }

}
