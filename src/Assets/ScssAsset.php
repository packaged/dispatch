<?php
namespace Packaged\Dispatch\Assets;

use Leafo\ScssPhp\Compiler;

class ScssAsset extends AbstractDispatchableAsset
{
  private $_importPath = null;

  public function getExtension()
  {
    return 'scss';
  }

  public function getContentType()
  {
    return "text/css";
  }

  public function setImportPath($importPath)
  {
    $this->_importPath = $importPath;
  }

  public function getImportPath()
  {
    return $this->_importPath;
  }

  public function getContent()
  {
    //Set the import path
    if($importPath = $this->getOption('importPath', null))
    {
      $this->setImportPath($importPath);
    }

    $Compiler = new Compiler();
    if(!is_null($this->_importPath))
    {
      $Compiler->setImportPaths($this->_importPath);
    }

    return $Compiler->compile(parent::getContent());
  }
}
