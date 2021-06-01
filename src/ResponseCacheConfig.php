<?php
namespace Packaged\Dispatch;

class ResponseCacheConfig
{
  protected $_varyHeader = 'Accept-Encoding, Accept';
  protected $_cacheSeconds = 31536000;
  protected $_immutable = true;

  /**
   * @return string
   */
  public function getVaryHeader(): string
  {
    return $this->_varyHeader;
  }

  /**
   * @param string $vary
   *
   * @return ResponseCacheConfig
   */
  public function setVaryHeader(string $vary)
  {
    $this->_varyHeader = $vary;
    return $this;
  }

  /**
   * @return int
   */
  public function getCacheSeconds(): int
  {
    return $this->_cacheSeconds;
  }

  /**
   * @param int $cacheTimeSeconds
   *
   * @return ResponseCacheConfig
   */
  public function setCacheSeconds(int $cacheTimeSeconds)
  {
    $this->_cacheSeconds = $cacheTimeSeconds;
    return $this;
  }

  /**
   * @return bool
   */
  public function isImmutable(): bool
  {
    return $this->_immutable;
  }

  /**
   * @param bool $immutable
   *
   * @return ResponseCacheConfig
   */
  public function setImmutable(bool $immutable)
  {
    $this->_immutable = $immutable;
    return $this;
  }

}
