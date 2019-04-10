<?php
namespace Packaged\Dispatch;

use Packaged\Helpers\ValueAs;

class ResourceStore
{
  const TYPE_CSS = 'css';
  const TYPE_JS = 'js';
  const TYPE_PRE_CSS = 'pre.css';
  const TYPE_PRE_JS = 'pre.js';
  const TYPE_POST_CSS = 'post.css';
  const TYPE_POST_JS = 'post.js';

  const PRIORITY_HIGH = 10;
  const PRIORITY_DEFAULT = 500;
  const PRIORITY_LOW = 1000;

  // [type][priority][uri] = options
  protected $_store = [];

  public function getResources($type = null, int $priority = null)
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
      foreach($this->_store[$type] as $resources)
      {
        foreach($resources as $uri => $options)
        {
          $return[$uri] = $options;
        }
      }
      return $return;
    }

    return [];
  }

  public function generateHtmlIncludes($for = self::TYPE_CSS)
  {
    if(!isset($this->_store[$for]) || empty($this->_store[$for]))
    {
      return '';
    }

    $template = '<link href="%s"%s>';
    if($for == self::TYPE_CSS)
    {
      $template = '<link href="%s" rel="stylesheet" type="text/css"%s>';
    }
    else if($for == self::TYPE_JS)
    {
      $template = '<script src="%s"%s></script>';
    }
    $return = '';

    foreach($this->getResources($for) as $uri => $options)
    {
      if(strlen($uri) == 32 && !stristr($uri, '/'))
      {
        if($for == self::TYPE_CSS)
        {
          $return .= '<style>' . $options . '</style>';
        }
        else if($for == self::TYPE_JS)
        {
          $return .= '<script>' . $options . '</script>';
        }
      }
      else if(!empty($uri))
      {
        $opts = $options;
        if(is_array($options))
        {
          $opts = '';
          foreach($options as $key => $value)
          {
            if($value === null)
            {
              $opts .= " $key";
            }
            else
            {
              $value = ValueAs::string($value);
              $opts .= " $key=\"$value\"";
            }
          }
        }
        $return .= sprintf($template, $uri, $opts);
      }
    }
    return $return;
  }

  /**
   * Add a resource to the store, along with its type
   *
   * @param     $type
   * @param     $uri
   * @param     $options
   * @param int $priority
   */
  protected function _addToStore($type, $uri, $options = null, int $priority = self::PRIORITY_DEFAULT)
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
      $this->_store[$type][$priority][$uri] = $options;
    }
  }

  /**
   * Clear the entire resource store with a type of null, or all items stored
   * by a type if supplied
   *
   * @param null $type
   */
  public function clearStore($type = null)
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
  public function requireJs($filename, $options = null, int $priority = self::PRIORITY_DEFAULT)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      static::_addToStore(self::TYPE_JS, $filename, $options, $priority);
    }
  }

  /**
   * Add a js script to the store
   *
   * @param     $javascript
   * @param int $priority
   */
  public function requireInlineJs($javascript, int $priority = self::PRIORITY_DEFAULT)
  {
    static::_addToStore(self::TYPE_JS, md5($javascript), $javascript, $priority);
  }

  /**
   * Add a css file to the store
   *
   * @param     $filename
   * @param     $options
   * @param int $priority
   */
  public function requireCss($filename, $options = null, int $priority = self::PRIORITY_DEFAULT)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      static::_addToStore(self::TYPE_CSS, $filename, $options, $priority);
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
    static::_addToStore(self::TYPE_CSS, md5($stylesheet), $stylesheet, $priority);
  }
}
