<?php
/*******************************************************************************

  アクション/フィルターオブジェクトのシングルトン・インスタンス

  All rights reserved.

*******************************************************************************/
/*-------------------------------------------------------------------------------
 アクションオブジェクトの生成と取得
------------------------------------------------------------------------------*/
function get_action($actionID = '')
{
  static $aid = null;

  if($aid == null)
    $aid = empty($actionID) ? str_uniqid() : $actionID;

  return Action::GetInstance($aid);
}

/*-------------------------------------------------------------------------------
 アクション登録
------------------------------------------------------------------------------*/
function add_action($name,$callback)
{
  return get_action()->add($name,$callback);
}

function add_actions(array $array)
{
  foreach($array as $key => $func_name)
    add_action($key,$func_name);
}

function clear_actions($action_name)
{
  get_action()->delete($action_name);
}

/*-------------------------------------------------------------------------------
 アクション実行
------------------------------------------------------------------------------*/
function do_action($name,$args = array())
{
  return get_action()->fire($name,$args);
}


/*-------------------------------------------------------------------------------
 フィルターオブジェクトの生成と取得
------------------------------------------------------------------------------*/
function get_filter($filterID = '')
{
  static $fid = null;

  if($fid == null)
    $fid = empty($filterID) ? str_uniqid() : $filterID;

  return Filter::GetInstance($fid);
}

/*-------------------------------------------------------------------------------
 フィルター登録
------------------------------------------------------------------------------*/
function add_filter($name,$callback,$priority = -1,$count = -1)
{
  return get_filter()->insert($name,$callback,$priority,$count);
}

function addonce_filter($name,$callback)
{
  return get_filter()->append($name,$callback,1);
}

function append_filter($name,$callback,$count = -1)
{
  return get_filter()->append($name,$callback,$count);
}

function prepend_filter($name,$callback,$count = -1)
{
  return get_filter()->prepend($name,$callback,$count);
}

function add_filters(array $array,$count = -1)
{
  foreach($array as $key => $func_name)
    append_filter($key,$func_name,$count);
}

function clear_filter($filter_name)
{
  get_filter()->delete($filter_name);
}

/*-------------------------------------------------------------------------------
 フィルター実行
------------------------------------------------------------------------------*/
function do_filter($name,$initial = '')
{
  return get_filter()->fire($name,$initial);
}
