<?php
/*******************************************************************************

  Key - Value 値をデータベースへ保存するテーブル・アクセサ

  Meta クラスから派生する。meta_fkeyはインスタンス毎に固定で使う。

  All Written by K.,Nakagawa.

*******************************************************************************/

class CacheableDictionary extends Dictionary implements CacheableCollection
{
  private ApcuCache $cache;

  public function __construct(string|PDOExtension $dsn,string $cachePrefix,array $options = [])
  {
    parent::__construct($dsn,$options);
    $this->cache = new ApcuCache($cachePrefix);
  }

  /*------------------------------------------------------------------------------
    implement CacheableCollection
  ------------------------------------------------------------------------------*/
  public function getCache() : ApcuCache
  {
    return $this->cache;
  }
  public function setCache(KeyValueCollection $collection) : void
  {
    $this->cache = $collection;
  }
  public function clearCache() : void
  {
    $this->clear();
  }
  public function getTTL() : int
  {
    return $this->cache->getTTL();
  }
  public function setTTL(int $value) : static
  {
    $this->cache->setTTL($value);
    return $this;
  }

  /*------------------------------------------------------------------------------
    override parent method
  ------------------------------------------------------------------------------*/
  protected function kv_set(int|string|array $k,mixed $v,array $options) : mixed
  {
    $rv = parent::kv_set($k,$v,$options);
    $this->cache->set($k,$v,$options);
    return $rv;
  }
  protected function kv_get(int|string|array $k,mixed $v,array $options) : mixed
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
  protected function kv_delete(int|string $k,mixed $v,array $options) : mixed
  {
    $rv = parent::kv_delete($k,$v,$options);
    $this->cache->delete($k);

    return $rv;
  }

  protected function kv_clear(int|string|null $k,mixed $v,array $options) : mixed
  {
    parent::kv_clear($k,$v,$options);
    $this->cache->clear();

    return null;
  }
}
