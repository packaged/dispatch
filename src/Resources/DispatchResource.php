<?php
namespace Packaged\Dispatch\Resources;

interface DispatchResource
{
  public function getExtension();

  public function getContent();

  public function getContentType();

  public function getHash();

  public function setOptions(array $options);
}
