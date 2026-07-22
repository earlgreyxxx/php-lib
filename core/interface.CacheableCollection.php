<?php
/*******************************************************************************

  define CacheableCollection interfaces

  All Written by K.,Nakagawa.
*******************************************************************************/
interface CacheableCollection
{
  public function getCache() : ApcuCache;
  public function setCache(KeyValueCollection $instance) : void;
  public function clearCache() : void;
  public function getTTL() : int;
  public function setTTL(int $value) : static;
}
