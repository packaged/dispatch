<?php
namespace Packaged\Dispatch;

/** Filesystem boundary shared by URL handlers and resource lookups. */
class PathGuard
{
  public static function isSafe($path)
  {
    return is_string($path) && strpos($path, '..') === false
      && strpos($path, "\0") === false && strpos($path, '\\') === false
      && strpos($path, ':') === false;
  }

  /** Return a canonical path, or null for missing or unsafe paths. */
  public static function resolve($base, $path)
  {
    if(!is_string($base) || $base === '' || !is_string($path) || $path === '')
    {
      return null;
    }
    $base = realpath($base);
    $path = realpath($path);
    if($base === false || $path === false || !is_dir($base))
    {
      return null;
    }
    // The separator prevents a sibling such as assets-private matching assets.
    $prefix = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return strncmp($path, $prefix, strlen($prefix)) === 0 ? $path : null;
  }
}
