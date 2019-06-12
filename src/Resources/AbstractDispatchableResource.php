<?php
namespace Packaged\Dispatch\Resources;

use Packaged\Dispatch\ResourceManager;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;
use Packaged\Helpers\ValueAs;
use function array_shift;
use function base64_encode;
use function basename;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function preg_replace;
use function strpos;

abstract class AbstractDispatchableResource extends AbstractResource implements DispatchableResource
{
  /**
   * @var ResourceManager
   */
  protected $_manager;
  protected $_path;
  protected $_workingDirectory;
  protected $_processedContent = false;

  public function setManager(ResourceManager $am)
  {
    $this->_manager = $am;
  }

  public function setProcessingPath($path)
  {
    $this->_path = $path;
    return $this;
  }

  /**
   * Set the resource content
   *
   * @param $content
   *
   * @return DispatchResource
   */
  public function setContent($content)
  {
    $this->_processedContent = false;
    return parent::setContent($content);
  }

  /**
   * Get the content for this resource
   *
   * @return mixed
   */
  public function getContent()
  {
    if(!$this->_processedContent)
    {
      $this->_processContent();
      //Stop the process from running for every fetch of the content
      $this->_processedContent = true;
    }
    return parent::getContent();
  }

  /**
   * Dispatch the raw content
   *
   * @return void
   */
  protected function _processContent()
  {
    if(isset($this->_manager)
      && ValueAs::bool($this->getOption('dispatch', false))
      && strpos($this->_content, '@' . 'do-not-dispatch') === false)
    {
      $this->_dispatch();
    }

    $file = basename($this->_filePath);
    $preMinified = (strpos($file, '.min.') > 0 || strpos($file, '-min.') > 0);

    //Return the raw content if minification has been disabled or @do-not-minify is set
    if(!$preMinified
      && ValueAs::bool($this->getOption('minify', false))
      && strpos($this->_content, '@' . 'do-not-minify') === false)
    {
      $this->_minify();
    }

    if($this->getOption('sourcemap', false))
    {
      $map = $this->_filePath . '.map';
      if(file_exists($map))
      {
        $this->_content .= '/*# sourceMappingURL=data:application/json;charset=utf8;base64,'
          . base64_encode(file_get_contents($map))
          . '*/';
      }
    }
  }

  protected function _dispatch() { }

  protected function _minify() { }

  /**
   * @param $path
   *
   * @return string
   * @throws \Exception
   */
  protected function _getDispatchUrl($path): string
  {
    if($this->_manager->isExternalUrl($path))
    {
      return $path;
    }

    $queryPos = strpos($path, '?');
    $fragPos = strpos($path, '#');

    if($queryPos + $fragPos > 0)
    {
      if($fragPos === false)
      {
        $appendChar = '?';
      }
      else if($queryPos === false)
      {
        $appendChar = '#';
      }
      else
      {
        $appendChar = min($queryPos, $fragPos) == $queryPos ? '?' : '#';
      }
      list($newPath, $append) = Strings::explode($appendChar, $path, [$path, null], 2);
      $append = $append ? $appendChar . $append : null;
    }
    else
    {
      $append = '';
      $newPath = $path;
    }

    $newPath = $this->_makeFullPath($newPath, dirname($this->_path));

    try
    {
      $url = $this->_manager->getResourceUri($newPath);
    }
    catch(\RuntimeException $e)
    {
      $url = null;
    }
    if(empty($url))
    {
      return $path;
    }
    if(!empty($append))
    {
      $url .= $append;
    }
    return $url;
  }

  /**
   * Make the relative path
   *
   * @param $relativePath
   * @param $workingDirectory
   *
   * @return string
   */
  protected function _makeFullPath($relativePath, $workingDirectory)
  {
    // levelUps
    $newParts = [];
    $parts = explode('/', Path::url($workingDirectory, $relativePath));
    while($part = array_shift($parts))
    {
      if($part !== '..' && $parts && $parts[0] === '..')
      {
        array_shift($parts);
      }
      else
      {
        $newParts[] = $part;
      }
    }
    $relativePath = Path::url(...$newParts);

    // currentDir
    $relativePath = preg_replace('~(?<=\/|^).\/~', '', $relativePath);

    return $relativePath;
  }
}
