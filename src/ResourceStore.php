<?php
namespace Packaged\Dispatch;

use function htmlspecialchars;
use function ksort;
use function md5;
use function sprintf;
use function stristr;
use function strlen;

class ResourceStore
{
  const TYPE_CSS = 'css';
  const TYPE_JS = 'js';
  /** @deprecated  - Please use priorities */
  const TYPE_PRE_CSS = 'pre.css';
  /** @deprecated  - Please use priorities */
  const TYPE_PRE_JS = 'pre.js';
  /** @deprecated  - Please use priorities */
  const TYPE_POST_CSS = 'post.css';
  /** @deprecated  - Please use priorities */
  const TYPE_POST_JS = 'post.js';

  const PRIORITY_PRELOAD = 1;
  const PRIORITY_HIGH = 10;
  const PRIORITY_DEFAULT = 500;
  const PRIORITY_LOW = 1000;

  // [type][priority][uri] = options
  protected $_store = [];

  public function generateHtmlIncludes($for = self::TYPE_CSS, int $priority = null, array $excludePriority = [])
  {
    if(!isset($this->_store[$for]) || empty($this->_store[$for]))
    {
      return '';
    }

    $template = '<link href="%s"%s>';
    if($for == self::TYPE_JS)
    {
      $template = '<script src="%s"%s></script>';
    }
    $return = '';

    foreach($this->getResources($for, $priority, $excludePriority) as $uri => $options)
    {
      if(strlen($uri) == 32 && !stristr($uri, '/'))
      {
        $inlineContent = isset($options['_']) ? $options['_'] : null;
        if($for == self::TYPE_CSS)
        {
          $return .= '<style>' . $inlineContent . '</style>';
        }
        else if($for == self::TYPE_JS)
        {
          $return .= '<script>' . $inlineContent . '</script>';
        }
      }
      else if(!empty($uri))
      {
        $opts = '';
        foreach((array)$options as $key => $value)
        {
          $opts .= " $key";
          if($value === null || $value === true)
          {
            //Do not append value
          }
          else if(is_string($value))
          {
            $opts .= '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
          }
          else if(is_numeric($value))
          {
            $opts .= '="' . $value . '"';
          }
        }
        $return .= sprintf($template, $uri, $opts);
      }
    }
    return $return;
  }

  public function getResources($type, int $priority = null, array $excludePriority = [])
  {
    if(isset($this->_store[$type][$priority]))
    {
      return $this->_store[$type][$priority];
    }

    if($priority === null && isset($this->_store[$type]))
    {
      $return = [];
      //Sort based on store priority
      ksort($this->_store[$type]);
      foreach($this->_store[$type] as $currentPriority => $resources)
      {
        if(!in_array($currentPriority, $excludePriority))
        {
          foreach($resources as $uri => $options)
          {
            $return[$uri] = $options;
          }
        }
      }
      return $return;
    }

    return [];
  }

  /**
   * Clear the entire resource store with a type of null, or all items stored
   * by a type if supplied
   *
   * @param string $type Store Type e.g. ResourceStore::TYPE_CSS
   */
  public function clearStore(string $type = null)
  {
    if($type === null)
    {
      $this->_store = [];
    }
    else
    {
      unset($this->_store[$type]);
    }
  }

  /**
   * Add a js file to the store
   *
   * @param     $filename
   * @param     $options
   * @param int $priority
   */
  public function requireJs($filename, ?array $options = [], int $priority = self::PRIORITY_DEFAULT)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      $this->addResource(self::TYPE_JS, $filename, $options, $priority);
    }
  }

  /**
   * Add a resource to the store, along with its type
   *
   * @param     $type
   * @param     $uri
   * @param     $options
   * @param int $priority
   */
  public function addResource(string $type, string $uri, ?array $options = [], int $priority = self::PRIORITY_DEFAULT)
  {
    if(!empty($uri))
    {
      if(!isset($this->_store[$type]))
      {
        $this->_store[$type] = [$priority => []];
      }
      else if(!isset($this->_store[$type][$priority]))
      {
        $this->_store[$type][$priority] = [];
      }

      $this->_store[$type][$priority][$uri] = $this->_defaultOptions($type, $options, $priority);
    }
  }

  protected function _defaultOptions(string $type, ?array $options, int $priority): array
  {
    if($options === null)
    {
      $options = [];
    }
    if($priority === self::PRIORITY_PRELOAD)
    {
      $options['rel'] = 'preload';
      $options['as'] = $type === self::TYPE_JS ? 'script' : 'style';
    }

    switch($type)
    {
      case self::TYPE_CSS:
        if(!isset($options['rel']))
        {
          $options['rel'] = 'stylesheet';
        }
        if(!isset($options['type']))
        {
          $options['type'] = 'text/css';
        }
        break;
    }
    return $options;
  }

  /**
   * Add a js script to the store
   *
   * @param     $javascript
   * @param int $priority
   */
  public function requireInlineJs($javascript, int $priority = self::PRIORITY_DEFAULT)
  {
    $this->addResource(self::TYPE_JS, md5($javascript), ['_' => $javascript], $priority);
  }

  /**
   * Add a css file to the store
   *
   * @param     $filename
   * @param     $options
   * @param int $priority
   */
  public function requireCss($filename, ?array $options = [], int $priority = self::PRIORITY_DEFAULT)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      $this->addResource(self::TYPE_CSS, $filename, $options, $priority);
    }
  }

  /**
   * Add css to the store
   *
   * @param     $stylesheet
   * @param int $priority
   */
  public function requireInlineCss($stylesheet, int $priority = self::PRIORITY_DEFAULT)
  {
    $this->addResource(self::TYPE_CSS, md5($stylesheet), ['_' => $stylesheet], $priority);
  }
}
