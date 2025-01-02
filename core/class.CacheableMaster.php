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

  protected $Key;
  protected $Type;
  protected $Prefix;
  protected $TTL;

  public function __construct($pdo,$tablename,$options =  array())
  {
    $options = array_merge(static::$DEFAULT_OPTION,$options);

    $this->Key = md5($options['key_prefix'].$tablename);
    $this->Prefix = $options['key_prefix'];
    $this->TTL = $options['ttl'];
    $this->Type = $options['type'];

    parent::__construct($pdo,$tablename,$options);
  }

  protected function init()
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

  public function cacheStore(array $data)
  {
    return apcu_store($this->Key,$data,$this->TTL);
  }
  public function cacheFetch()
  {
    return apcu_fetch($this->Key);
  }
  public function cacheClear()
  {
    apcu_clear_cache();
  }
  public function cacheExists()
  {
    return apcu_exists($this->Key);
  }


  public function setData(?array $data = null)
  {
    if(empty($data))
      $data = $this->selector();

    $rv = false;
    if(!empty($data))
      $rv = $this->cacheStore($data);

    return $rv;
  }
  public function getData()
  {
    if(!$this->cacheExists($this->Key))  
      $this->setData();

    return $this->cacheFetch();
  }

  public function clearData()
  {
    if($this->cacheExists())
      $this->clearCache();
  }

  public function refreshData()
  {
    $this->clearData();
    $this->setData();
  }

  public function getCacheType()
  {
    return $this->Type;
  }
  public function getTTL()
  {
    return $this->TTL;
  }
  public function setTTL($sec)
  {
    $this->TTL = $sec;
  }
  public function getCacheKeyPrefix()
  {
    return $this->Prefix;
  }
  public function setCacheKeyPrefix($prefix_str)
  {
    $this->Prefix = $prefix_str;
  }
}

