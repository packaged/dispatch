<?php
namespace Packaged\Dispatch\Assets;

abstract class AbstractAsset implements IAsset
{
  protected $_content;
  protected $_options;

  public function getContent()
  {
    return $this->_content;
  }

  public function setContent($content)
  {
    $this->_content = $content;
    return $this;
  }

  public function getOptions()
  {
    return $this->_options;
  }

  public function setOptions(array $options)
  {
    $this->_options = array_merge($this->_options, $options);
    return $this;
  }

  public function getOption($key, $default)
  {
    return isset($this->_options[$key]) ? $this->_options[$key] : $default;
  }
}
