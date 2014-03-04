<?php
namespace Packaged\Dispatch;

use Symfony\Component\EventDispatcher\Event;

class DispatchEvent extends Event
{
  protected $_filename;
  protected $_result;
  protected $_lookupParts;
  protected $_mapType;
  protected $_path;

  public function setFilename($filename)
  {
    $this->_filename = $filename;
    return $this;
  }

  public function getFilename()
  {
    return $this->_filename;
  }

  public function setLookupParts(array $parts)
  {
    $this->_lookupParts = $parts;
    return $this;
  }

  public function getLookupParts()
  {
    return $this->_lookupParts;
  }

  public function setMapType($mapType)
  {
    $this->_mapType = $mapType;
    return $this;
  }

  public function getMapType()
  {
    return $this->_mapType;
  }

  public function setPath($path)
  {
    $this->_path = $path;
    return $this;
  }

  public function getPath()
  {
    return $this->_path;
  }

  public function setResult($result)
  {
    $this->_result = $result;
    return $this;
  }

  public function getResult()
  {
    return $this->_result;
  }
}
