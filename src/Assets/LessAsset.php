<?php
namespace Packaged\Dispatch\Assets;

class LessAsset extends AbstractDispatchableAsset
{
  public function getExtension()
  {
    return 'less';
  }

  public function getContentType()
  {
    return "text/css";
  }

  public function getContent()
  {
    $less = new \lessc;
    return $less->compile(parent::getContent());
  }
}
