<?php
/*******************************************************************************

 ●フィルターの登録、定義、実行を行うクラスです。

  All Written by K.,Nakagawa.

*******************************************************************************/

class Filter extends KeyValueCollection
{
  private $id = null;
  private function inserter(string $name,callable $callable,int $pos = 0,int $count = -1) : string
  {
    $container = &$this->get_container();
    if(!$this->exists($name))
      $container[$name] = array();
    else if(!is_array($container[$name]))
      $container[$name] = array($container[$name]);

    $id = str_uniqid(); 
    array_inserter($container[$name],array($id,$callable,$count),$pos);
    
    return $id;
  }

  //コンストラクタ
  public function __construct(string $filterID = '',array $params = [])
  {
    $this->id(empty($filterID) ? str_uniqid() : $filterID);

    if(isset($params['container']) && !empty($params['container']))
      $this->set_container($params);
  }

  public function set(int|string|array $k,mixed $v = null,mixed $options = []) : mixed
  {
    throw new Exception(_('filter can not use get accessor'));
  }

  public function prepend(string $name,callable $filter,int $count = -1) : string|false
  {
    return $this->insert($name,$filter,0,$count);
  }

  public function append(string $name,callable $filter,int $count = -1) : string|false
  {
    return $this->insert($name,$filter,-1,$count);
  }

  public function insert(string $name,callable $filter,int $pos,int $count = -1) : string|false
  {
    if(!is_callable($filter))
      return false;

    return $this->inserter($name,$filter,$pos,$count);
  }

  public function fire(string $name,mixed $params = '') : mixed
  {
    $rv = null;
    $result = '';
    $container = &$this->get_container();

    if(!array_key_exists($name,$container) || empty($container[$name]))
    {
      if(!empty($params))
        $result = $params;
    }
    else
    {
      if(!is_array($container[$name]))
        throw new Exception(_('invalid filter value'));

      $result = $params;
      foreach($container[$name] as $i => &$filter)
      {
        if(!is_array($filter))
          throw new Exception(_('invalid filter value'));

        list($id,$callable,$count) = $filter;
        if($count != 0)
        {
          $result = call_user_func($callable,$result);
          if(--$count == 0)
            unset($container[$name][$i]);
        }
      }
    }
    return $result;
  }
}