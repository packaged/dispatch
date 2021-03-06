<?php
namespace Packaged\Dispatch\Resources;

use function array_merge;
use function file_get_contents;
use function md5;

abstract class AbstractResource implements DispatchResource
{
  protected $_content;
  protected $_options;
  protected $_hash;
  protected $_filePath;

  public static function fromFilePath($filePath)
  {
    $resource = new static();
    $resource->setFilePath($filePath);
    $resource->setContent(file_get_contents($filePath));
    return $resource;
  }

  /**
   * Set the path this asset was loaded from
   *
   * @param $path
   *
   * @return $this
   */
  public function setFilePath($path)
  {
    $this->_filePath = $path;
    return $this;
  }

  /**
   * Get the current options set
   *
   * @return mixed
   */
  public function getOptions()
  {
    return $this->_options;
  }

  /**
   * Append an options array onto the default options
   *
   * @param array $options
   *
   * @return $this
   */
  public function setOptions(array $options)
  {
    $this->_options = array_merge($this->_options, $options);
    return $this;
  }

  /**
   * Remove all options from the resource
   *
   * @return $this
   */
  public function clearOptions()
  {
    $this->_options = [];
    return $this;
  }

  /**
   * Set a single option
   *
   * @param $key
   * @param $value
   *
   * @return $this
   */
  public function setOption($key, $value)
  {
    $this->_options[$key] = $value;
    return $this;
  }

  /**
   * Retrieve an option
   *
   * @param $key
   * @param $default
   *
   * @return mixed
   */
  public function getOption($key, $default)
  {
    return isset($this->_options[$key]) ? $this->_options[$key] : $default;
  }

  public function getHash()
  {
    return $this->_hash ?: md5($this->getContent());
  }

  /**
   * @param mixed $hash
   *
   * @return AbstractResource
   */
  public function setHash($hash)
  {
    $this->_hash = $hash;
    return $this;
  }

  /**
   * Get the content for this resource
   *
   * @return mixed
   */
  public function getContent()
  {
    return $this->_content;
  }

  /**
   * Set the resource content
   *
   * @param $content
   *
   * @return AbstractResource
   */
  public function setContent($content)
  {
    $this->_content = $content;
    return $this;
  }

}
