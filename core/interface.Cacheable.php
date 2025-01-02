<?php
/*******************************************************************************

  define Cacheable interface

  All Written by K.,Nakagawa.
*******************************************************************************/
interface Cacheable
{
  public function getCacheType();
  public function getTTL();
  public function setTTL($sec);
  public function getCacheKeyPrefix();
  public function setCacheKeyPrefix($prefix_str);
  public function cacheStore(array $data);
  public function cacheFetch();
  public function cacheClear();
  public function cacheExists();
}
