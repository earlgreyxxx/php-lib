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
  protected string $prefix;
  protected int $TTL;

  //コンストラクタ
  public function __construct(string $prefix,int $ttl = 0)
  {
    if(!is_int($ttl))
      $ttl = 0;

    $this->id(sha1($prefix));
    $this->TTL = $ttl;
    $this->prefix = $prefix;
  }

  public function getTTL() : int
  {
    return $this->TTL;
  }
  public function setTTL(int $value) : static
  {
    $this->TTL = intval($value);
    return $this;
  }

  //内部メソッドの実装
  protected function kv_exists(int|string $k,mixed $v,array $options) : bool
  {
    return apcu_exists($this->prefix . $k);
  }
  protected function kv_keys(int|string|null $k,mixed $v,array $options) : array
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
  protected function kv_set(int|string|array $k,mixed $v,array $options) : mixed
  {
    return apcu_store($this->prefix . $k,$v,$this->TTL);
  }
  protected function kv_get(int|string|array $k,mixed $v,array $options) : mixed
  {
    return apcu_fetch($this->prefix . $k);
  }
  protected function kv_delete(int|string $k,mixed $v,array $options) : mixed
  {
    return apcu_delete($this->prefix . $k);
  }
  protected function kv_clear(int|string|null $k,mixed $v,array $options) : mixed
  {
    foreach($this->keys() as $key)
      $this->delete($key);

    return null;
  }
}

