<?php
/*******************************************************************************

  Key - Value 値をデータベースへ保存するテーブル・アクセサ

  Meta クラスから派生する。meta_fkeyはインスタンス毎に固定で使う。

  All Written by K.,Nakagawa.

*******************************************************************************/

class CacheableDictionary extends Dictionary implements CacheableCollection
{
  private $cache;

  public function __construct($dsn,$cachePrefix,$options = array())
  {
    parent::__construct($dsn,$options);
    $this->cache = new ApcuCache($cachePrefix);
  }

  /*------------------------------------------------------------------------------
    implement CacheableCollection
  ------------------------------------------------------------------------------*/
  public function getCache()
  {
    return $this->cache;
  }
  public function setCache(KeyValueCollection $collection)
  {
    $this->cache = $collection;
  }
  public function clearCache()
  {
    $this->clear();
  }
  public function getTTL()
  {
    return $this->cache->getTTL();
  }
  public function setTTL($value)
  {
    return $this->cache->setTTL($value);
  }

  /*------------------------------------------------------------------------------
    override parent method
  ------------------------------------------------------------------------------*/
  protected function kv_set($k,$v,$options)
  {
    $rv = parent::kv_set($k,$v,$options);
    $this->cache->set($k,$v,$options);
    return $rv;
  }
  protected function kv_get($k,$v,$options)
  {
    if($this->cache->exists($k))
    {
      $rv = $this->cache->get($k);
    }
    else
    {
      $rv = parent::kv_get($k,$v,$options);
      $this->cache->set($k,$rv);
    }

    return $rv;
  }
  protected function kv_delete($k,$v,$options)
  {
    $rv = parent::kv_delete($k,$v,$options);
    $this->cache->delete($k);

    return $rv;
  }

  protected function kv_clear($k,$v,$options)
  {
    parent::kv_clear($k,$v,$options);
    $this->cache->clear();
  }
}
