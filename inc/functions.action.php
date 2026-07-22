<?php
/*******************************************************************************

  アクション/フィルターオブジェクトのシングルトン・インスタンス

  All rights reserved.

*******************************************************************************/
/*-------------------------------------------------------------------------------
 アクションオブジェクトの生成と取得
------------------------------------------------------------------------------*/
function get_action(string $actionID = '') : Action
{
  static $aid = null;

  if($aid == null)
    $aid = empty($actionID) ? str_uniqid() : $actionID;

  return Action::GetInstance($aid);
}

/*-------------------------------------------------------------------------------
 アクション登録
------------------------------------------------------------------------------*/
function add_action(string $name,callable $callback) : void
{
  get_action()->add($name,$callback);
}

function add_actions(array $array) : void
{
  foreach($array as $key => $func_name)
    add_action($key,$func_name);
}

function clear_actions(string $action_name) : void
{
  get_action()->delete($action_name);
}

/*-------------------------------------------------------------------------------
 アクション実行
------------------------------------------------------------------------------*/
function do_action(string $name,array $args = []) : mixed
{
  return get_action()->fire($name,$args);
}


/*-------------------------------------------------------------------------------
 フィルターオブジェクトの生成と取得
------------------------------------------------------------------------------*/
function get_filter(string $filterID = '') : Filter
{
  static $fid = null;

  if($fid == null)
    $fid = empty($filterID) ? str_uniqid() : $filterID;

  return Filter::GetInstance($fid);
}

/*-------------------------------------------------------------------------------
 フィルター登録
------------------------------------------------------------------------------*/
function add_filter(string $name,callable $callback,int $priority = -1,int $count = -1) : string
{
  return get_filter()->insert($name,$callback,$priority,$count);
}

function addonce_filter(string $name,callable $callback) : string|false
{
  return get_filter()->append($name,$callback,1);
}

function append_filter(string $name,callable $callback,int $count = -1) : string|false
{
  return get_filter()->append($name,$callback,$count);
}

function prepend_filter(string $name,callable $callback,int $count = -1) : string|false
{
  return get_filter()->prepend($name,$callback,$count);
}

function add_filters(array $array,int $count = -1) : void
{
  foreach($array as $key => $func_name)
    append_filter($key,$func_name,$count);
}

function clear_filter(string $filter_name) : void
{
  get_filter()->delete($filter_name);
}

/*-------------------------------------------------------------------------------
 フィルター実行
------------------------------------------------------------------------------*/
function do_filter(string $name,mixed $initial = '') : mixed
{
  return get_filter()->fire($name,$initial);
}
