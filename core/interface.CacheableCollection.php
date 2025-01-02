<?php
/*******************************************************************************

  define CacheableCollection interfaces

  All Written by K.,Nakagawa.
*******************************************************************************/
interface CacheableCollection
{
  public function getCache();
  public function setCache(KeyValueCollection $instance);
  public function clearCache();
  public function getTTL();
  public function setTTL($value);
}
