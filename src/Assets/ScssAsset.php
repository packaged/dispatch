<?php
namespace Packaged\Dispatch\Assets;

use Leafo\ScssPhp\Compiler;

class ScssAsset extends AbstractDispatchableAsset
{
  public function getExtension()
  {
    return 'scss';
  }

  public function getContentType()
  {
    return "text/css";
  }

  public function getContent()
  {
    return (new Compiler())->compile(parent::getContent());
  }
}
