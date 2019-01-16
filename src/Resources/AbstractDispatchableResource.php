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
    $isCurrentWorkingDir = $workingDirectory === '.';

    // inline levelUps
    $relativePath = preg_replace('~[^\/]+\/..\/~', '', Path::url($workingDirectory, $relativePath));
    // root levelUps
    $relativePath = preg_replace('~^..\/~', '', $relativePath, -1, $moves);
    $upLimit = $isCurrentWorkingDir ? 0 : count(explode('/', $workingDirectory));
    if($moves > $upLimit)
    {
      return null;
    }
    // currentDir
    $relativePath = preg_replace('~(?<=\/).\/~', '', $relativePath);
    // working dir
    $relativePath = preg_replace('~^.\/~', $isCurrentWorkingDir ? '' : $workingDirectory . '/', $relativePath);
    return $relativePath;
  }

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

    list($newPath, $append) = Strings::explode('?', $path, [$path, null], 2);

    $newPath = $this->_makeFullPath($newPath, dirname($this->_path));

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

  protected function _getResolvedPath($path)
  {
    if(!$this->_manager->isExternalUrl($path))
    {
      $path = Path::system($this->_makeFullPath(dirname($path), dirname($this->_path)), basename($path));
    }
    return $path;
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
