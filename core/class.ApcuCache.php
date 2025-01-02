<?php
/*******************************************************************************
 *
 *  APCu Cache Base class
 *
 *   All written by Kenji, Nakagawa.
 *   許可なく配布もしくは使用はできません。
 *
******************************************************************************/

class ApcuCache extends KeyValueCollection
{
  protected $prefix;
  protected $TTL;

  //コンストラクタ
  public function __construct($prefix,$ttl = 0)
  {
    if(!is_int($ttl))
      $ttl = 0;

    $this->id(sha1($prefix));
    $this->TTL = $ttl;
    $this->prefix = $prefix;
  }

  public function getTTL()
  {
    return $this->TTL;
  }
  public function setTTL($value)
  {
    $this->TTL = intval($value);
    return $this;
  }

  //内部メソッドの実装
  protected function kv_exists($k,$v,$options)
  {
    return apcu_exists($this->prefix . $k);
  }
  protected function kv_keys($k,$v,$options)
  {
    $ci = apcu_cache_info();
    $rv = [];
    foreach($ci['cache_list'] as $el)
    {
      $ar = preg_split(sprintf('/%s/u',preg_quote($this->prefix)),$el['info'],2);
      if(count($ar) < 2)
        $ar[] = '';

      list(,$key) = $ar;
      if(!empty($key))
        $rv[] = $key;
    }
    return $rv;
  }
  protected function kv_set($k,$v,$options)
  {
    return apcu_store($this->prefix . $k,$v,$this->TTL);
  }
  protected function kv_get($k,$v,$options)
  {
    return apcu_fetch($this->prefix . $k);
  }
  protected function kv_delete($k,$v,$options)
  {
    return apcu_delete($this->prefix . $k);
  }
  protected function kv_clear($k,$v,$options)
  {
    foreach($this->keys() as $key)
      $this->delete($key);
  }
}

