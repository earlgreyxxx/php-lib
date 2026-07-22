<?php
/*******************************************************************************

  Master table management ... fetch and cache to APC or others

    - Constructor parameters ($dsn,$serializeType)

    - Usage :
       $master = new CacheableMaster(GetPdoInstance('sqlite:./zipcode.sqlite'),'zipcode');

  All Written by K.,Nakagawa.
*******************************************************************************/

class CacheableMaster extends Master implements Cacheable
{
  protected static $DEFAULT_OPTION = array('ttl' => 86400, 'type' => 'apcu','key_prefix' => 'Master-');

  protected string $Key;
  protected string $Type;
  protected string $Prefix;
  protected int $TTL;

  public function __construct(PDOExtension $pdo,string $tablename,array $options = [])
  {
    $options = array_merge(static::$DEFAULT_OPTION,$options);

    $this->Key = md5($options['key_prefix'].$tablename);
    $this->Prefix = strval($options['key_prefix']);
    $this->TTL = intval($options['ttl']);
    $this->Type = strval($options['type']);

    parent::__construct($pdo,$tablename,$options);
  }

  protected function init() : void
  {
    if(!function_exists('apcu_exists'))
      throw new Exception('not support APCu');

    $obj = $this;
    $setDataFunc = function() use($obj) { $res = $obj->setData(); };
    $action = $this->action;
    $action->add('insert-done',$setDataFunc);
    $action->add('update-done',$setDataFunc);
    $action->add('delete-done',$setDataFunc);

    if(!apcu_exists($this->Key))
      $this->setData();
  }

  public function cacheStore(array $data) : bool
  {
    return apcu_store($this->Key,$data,$this->TTL);
  }
  public function cacheFetch() : mixed
  {
    return apcu_fetch($this->Key);
  }
  public function cacheClear() : bool
  {
    return apcu_clear_cache();
  }
  public function cacheExists() : bool
  {
    return apcu_exists($this->Key);
  }


  public function setData(?array $data = null) : true
  {
    if(empty($data))
      $data = $this->selector();

    $rv = false;
    if(!empty($data))
      $rv = $this->cacheStore($data);

    return $rv;
  }
  public function getData() : ?array
  {
    if(!$this->cacheExists())  
      $this->setData();
    $rv = $this->cacheFetch();

    return is_array($rv) ? $rv : [];
  }

  public function clearData() : void
  {
    if($this->cacheExists())
      $this->cacheClear();
  }

  public function refreshData() : void
  {
    $this->clearData();
    $this->setData();
  }

  public function getCacheType() : string
  {
    return $this->Type;
  }
  public function getTTL() : int
  {
    return $this->TTL;
  }
  public function setTTL(int $sec) : void
  {
    $this->TTL = $sec;
  }
  public function getCacheKeyPrefix() : string
  {
    return $this->Prefix;
  }
  public function setCacheKeyPrefix(string $prefix_str) : void
  {
    $this->Prefix = $prefix_str;
  }
}

