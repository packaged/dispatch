<?php
namespace Packaged\Dispatch\Resources;

use Packaged\Dispatch\ResourceManager;
use Packaged\Helpers\Path;
use Packaged\Helpers\Strings;
use Packaged\Helpers\ValueAs;

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
   * @return Resource
   */
  public function setContent($content)
  {
    $this->_processedContent = false;
    return parent::setContent($content);
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
  }

  protected function _minify() { }

  protected function _dispatch() { }

  /**
   * Dispatch a nested URL
   *
   * @param $uri
   *
   * @return string
   * @throws \Exception
   */
  protected function _dispatchNestedUrl($uri)
  {
    $quote = $uri[1];
    $path = $uri[2];

    list($path, $append) = Strings::explode('?', $path, [$path, null], 2);

    if(!$this->_manager->isExternalUrl($path))
    {
      $path = Path::system($this->_makeFullPath(dirname($path), dirname($this->_path)), basename($path));
    }

    $url = $this->_manager->getResourceUri($path);

    if(empty($url))
    {
      return $quote . ($path ?? $uri[0]) . $quote;
    }

    if(!empty($append))
    {
      return $quote . "$url?$append" . $quote;
    }
    return $quote . "$url" . $quote;
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
    if($relativePath == '.')
    {
      return $workingDirectory;
    }
    $levelUps = substr_count($relativePath, '../');
    if($levelUps > 0)
    {
      $relativePath = str_replace('../', '', $relativePath);
      $workingDirectoryParts = explode('/', $workingDirectory);
      $moves = count($workingDirectoryParts) - $levelUps;
      if($moves < 0)
      {
        //Relative to this directory is not allowed
        return null;
      }
      return Path::custom('/', array_merge(array_slice($workingDirectoryParts, 0, $moves), [$relativePath]));
    }
    return $workingDirectory[0] !== '.' ? Path::url($workingDirectory, $relativePath) : $relativePath;
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
}
