<?php
/*******************************************************************************

  define Cacheable interface

  All Written by K.,Nakagawa.
*******************************************************************************/
interface Cacheable
{
  public function getCacheType();
  public function getTTL() : int;
  public function setTTL(int $sec) : void;
  public function getCacheKeyPrefix() : string;
  public function setCacheKeyPrefix(string $prefix_str) : void;
  public function cacheStore(array $data);
  public function cacheFetch() : mixed;
  public function cacheClear() : mixed;
  public function cacheExists() : bool;
}
