<?php
namespace Packaged\Dispatch;

use Packaged\Helpers\ValueAs;

class ResourceStore
{
  const TYPE_CSS = 'css';
  const TYPE_JS = 'js';

  protected $_store = [];

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

    foreach($this->_store[$for] as $uri => $options)
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
   * @param $type
   * @param $uri
   * @param $options
   */
  protected function _addToStore($type, $uri, $options = null)
  {
    if(!empty($uri))
    {
      if(!isset($this->_store[$type]))
      {
        $this->_store[$type] = [];
      }
      $this->_store[$type][$uri] = $options;
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
   * @param $filename
   * @param $options
   */
  public function requireJs($filename, $options = null)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      static::_addToStore(self::TYPE_JS, $filename, $options);
    }
  }

  /**
   * Add a js script to the store
   *
   * @param $javascript
   */
  public function requireInlineJs($javascript)
  {
    static::_addToStore(self::TYPE_JS, md5($javascript), $javascript);
  }

  /**
   * Add a css file to the store
   *
   * @param $filename
   * @param $options
   */
  public function requireCss($filename, $options = null)
  {
    $filenames = (array)$filename;
    foreach($filenames as $filename)
    {
      static::_addToStore(self::TYPE_CSS, $filename, $options);
    }
  }

  /**
   * Add css to the store
   *
   * @param $stylesheet
   */
  public function requireInlineCss($stylesheet)
  {
    static::_addToStore(self::TYPE_CSS, md5($stylesheet), $stylesheet);
  }
}
