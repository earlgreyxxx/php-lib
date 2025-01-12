<?php
/*******************************************************************************

  Accessor functions.

  All Written by K.,Nakagawa.

*******************************************************************************/

/*-------------------------------------------------------------------------------

 入力などのスーパーグローバル変数を得るラッパー関数。

------------------------------------------------------------------------------*/
function &get_inputs($type = '')
{
  $rv = false;
  $type = strtolower($type);

  switch($type)
    {
    case 'post':
      $rv = &$_POST;
      break;
    case 'get':
      $rv = &$_GET;
      break;
    case 'cookie':
      $rv = &$_COOKIE;
      break;
    case 'files':
      $rv = &$_FILES;
      break;
    case 'request':
      $rv = &$_REQUEST;
      break;
    }

  return $rv;
}

/*-------------------------------------------------------------------------------

  リクエストを得る

------------------------------------------------------------------------------*/
function &get_request($request = null)
{
  static $r = null;

  if($r == null)
    {
      if($request == null)
        $r = &get_inputs('request');
      else
        $r = $request;
    }

  return $r;
}

/*-------------------------------------------------------------------------------

  リクエストを得る（get/post/cookie)

------------------------------------------------------------------------------*/
function &get_post($posts = null)
{
  static $p = null;

  if($p === null)
    {
      if($posts == null)
        $p = &get_inputs('post');
      else
        $p = $posts;
    }

  return $p;
}

function &get_get($gets = null)
{
  static $g = null;

  if($g === null)
    {
      if($gets === null)
        $g = &get_inputs('get');
      else
        $g = $gets;
    }

  return $g;
}

function &get_cookie($cookies = null)
{
  static $c = null;

  if($c === null)
    {
      if($cookies === null)
        $c = &get_inputs('cookie');
      else
        $c = $cookies;
    }

  return $c;
}


/*-------------------------------------------------------------------------------

 ファイル配列を得る。

------------------------------------------------------------------------------*/
function &get_files($files = null)
{
  static $f = null;

  if($f === null)
    {
      if($files === null)
        $f = &get_inputs('files');
      else
        $f = $files;
    }

  return $f;
}

/*-------------------------------------------------------------------------------

 セッション配列を得る。

------------------------------------------------------------------------------*/
function &get_session($session = null)
{
  static $s = null;

  if($s === null)
    {
      if($session === null)
        $s = &$_SESSION;
      else
        $s = $session;
    }

  return $s;
}

/*-------------------------------------------------------------------------------

 BASE_URL,SITE_URLのアクセサ

------------------------------------------------------------------------------*/
function get_url($set_url = null,$return_old_value = false)
{
  static $urls = array('get_base_url'     => BASE_URL,
                       'get_site_url'     => SITE_URL);

  $bt = debug_backtrace();
  $func = $bt[1]['function'];

  $old_value = $urls[$func];
  if(is_string($set_url) && !empty($set_url) && preg_match('|^https?://|',$set_url))
    {
      $urls[$func] = $set_url;
    }

  return $return_old_value === true ? $old_value : $urls[$func];
}

function get_base_url($set_url = null,$return_old_value = false)
{
  return get_url($set_url,$return_old_value);
}

function get_site_url($set_url = null,$return_old_value = false)
{
  return get_url($set_url,$return_old_value);
}

function get_self_url()
{
  return parse_url(get_base_url() . $_SERVER['REQUEST_URI'],PHP_URL_PATH);
}


/******************************************************************************

  get Route URL 

******************************************************************************/
function get_route_url($route = '',?array $params = null,$suffix = false)
{
  $rte = Route::GetInstance(ROUTE_BASE);
  if(empty($route))
    $route = $rte->current();

  $base_url = get_base_url(); 
  if($base_url === '/')
    $base_url = '';

  return sprintf('%s%s',$base_url,$rte->getPath($route,$params,$suffix));
}

function get_route_tag($route = '')
{
  $rv = '';
  $rte = Route::GetInstance(ROUTE_BASE);
  if(!$rte->rewritable())
  {
    if(empty($route))
      $route = $rte->current();

    $rv = sprintf('<input type="hidden" name="energize" value="%s" />',$route);
  }

  return $rv;
}

function get_route()
{
  $rte = Route::GetInstance(ROUTE_BASE);
  return $rte->current();
}

function get_request_path($request_uri = null)
{
  if(empty($request_uri))
    $request_uri = $_SERVER['REQUEST_URI'];

  return parse_url($request_uri,PHP_URL_PATH);
}

function get_form_action_path($route = '',?array $params = null)
{
  $rte = Route::GetInstance(ROUTE_BASE);
  if($rte->rewritable())
    return get_route_url($route,$params);

  return get_request_path();
}

function get_csrf_tag($data = null,$tokenname = 'csrf-tokens',$name = 'csrf-token')
{
  return sprintf('<input type="hidden" name="%s" value="%s" />',
                 $name,
                 CsrfTokens::GetInstance($tokenname)->generate($data));
}

function get_csrf_token($data = null,$tokenname = 'csrf-tokens')
{
  return CsrfTokens::GetInstance($tokenname)->generate($data);
}

