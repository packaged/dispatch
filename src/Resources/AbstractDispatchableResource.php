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
   * @param $path
   *
   * @return string
   * @throws \Exception
   */
  protected function _getDispatchUrl($path): string
  {
    list($newPath, $append) = Strings::explode('?', $path, [$path, null], 2);

    if(!$this->_manager->isExternalUrl($newPath))
    {
      $newPath = Path::system($this->_makeFullPath(dirname($newPath), dirname($this->_path)), basename($newPath));
    }

    $url = $this->_manager->getResourceUri($newPath);
    if(empty($url))
    {
      return $path;
    }
    if(!empty($append))
    {
      $url .= '?' . $append;
    }
    return $url;
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
