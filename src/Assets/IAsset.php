<?php
namespace Packaged\Dispatch\Assets;

interface IAsset
{
  public function getExtension();

  public function getContent();

  public function getContentType();
}
