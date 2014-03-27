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
      '~url\(\s*[\'"]?([^\s\'"]*)[\'"]?\s*\)~',
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
    $url = $this->_assetManager->getResourceUri($uri[1]);
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
