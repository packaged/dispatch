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
    $compiler = new Compiler();

    if($this->_assetManager !== null)
    {
      $compiler->setImportPaths(
        build_path(
          $this->_workingDirectory,
          build_path($this->_assetManager->getRelativePath())
        )
      );
    }
    return $compiler->compile(parent::getContent());
  }
}
