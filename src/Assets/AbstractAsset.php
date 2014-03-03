<?php
namespace Packaged\Dispatch\Assets;

abstract class AbstractAsset implements IAsset
{
  protected $_content;

  public function getContent()
  {
    return $this->_content;
  }

  public function setContent($content)
  {
    $this->_content = $content;
    return $this;
  }
}
