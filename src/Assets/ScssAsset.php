<?php
namespace Packaged\Dispatch\Assets;

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
    return (new \scssc)->compile(parent::getContent());
  }
}
