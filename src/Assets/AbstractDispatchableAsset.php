<?php
namespace Packaged\Dispatch\Assets;

use Packaged\Dispatch\AssetManager;

abstract class AbstractDispatchableAsset extends AbstractAsset
  implements IDispatchableAsset
{
  /**
   * @var AssetManager
   */
  protected $_assetManager;
  protected $_processedContent = false;

  public function setAssetManager(AssetManager $am)
  {
    $this->_assetManager = $am;
  }

  /**
   * Set the asset content
   *
   * @param $content
   *
   * @return $this
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
    if(strpos($this->_content, '@' . 'do-not-dispatch') !== false)
    {
      return;
    }

    //Treat as a standard asset if no asset manager has been set.
    if(!isset($this->_assetManager))
    {
      return;
    }

    //Find all URL(.*) and dispatch their values
    $this->_content = preg_replace_callback(
      '~url\(\s*[\'"]?([^\s\'"]*?)[\'"]?\s*\)~',
      array($this, "_dispatchNestedUrl"),
      $this->_content
    );

    //Stop the process from running for every fetch of the content
    $this->_processedContent = true;
  }

  /**
   * Dispatch a nested URL
   *
   * @param $uri
   *
   * @return string
   */
  protected function _dispatchNestedUrl($uri)
  {
    // if url path is empty, return unchanged
    if(empty($uri[1]))
    {
      return $uri[0];
    }

    $prefix = '';
    list($path, $append) = exploded('?', $uri[1], [$uri[1], null], 2);

    //Take a root link as it comes
    if(!starts_with($path, '/'))
    {
      $relPath = $this->_assetManager->getRelativePath();
      if(starts_with($path, '../'))
      {
        $max   = count($relPath);
        $depth = substr_count($path, '../');
        $path  = substr($path, $depth * 3);
        if($depth > 0 && $depth < $max)
        {
          $rel    = array_slice($relPath, 0, $depth);
          $prefix = implode('/', $rel);
        }
      }
      else
      {
        $prefix = implode('/', $relPath);
      }
    }

    $path = ltrim($path, '/');
    $url  = $this->_assetManager->getResourceUri(
      build_path_unix($prefix, $path)
    );

    if(empty($url))
    {
      return $uri[0];
    }

    if(!empty($append))
    {
      return "url('$url?$append')";
    }
    return "url('$url')";
  }

  /**
   * Get the content for this asset
   *
   * @return mixed
   */
  public function getContent()
  {
    if(!$this->_processedContent)
    {
      $this->_processContent();
    }
    return parent::getContent();
  }
}
