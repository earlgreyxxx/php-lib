<?php
/*******************************************************************************

  Accessor functions.

  All Written by K.,Nakagawa.

*******************************************************************************/

/*-------------------------------------------------------------------------------

 入力などのスーパーグローバル変数を得るラッパー関数。

------------------------------------------------------------------------------*/
function &get_inputs(string $type = '') : array|false
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
function &get_request(?array $request = null) : array
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
function &get_post(?array $posts = null) : array
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

function &get_get(?array $gets = null) : array
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

function &get_cookie(?array $cookies = null) : array
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
function &get_files(?array $files = null) : array
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
function &get_session(?array $session = null) : array
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
function get_url(?string $set_url = null,bool $return_old_value = false) : string
{
  static $urls = array('get_base_url'     => BASE_URL,
                       'get_site_url'     => SITE_URL);

  $bt = debug_backtrace();
  $func = $bt[1]['function'];

  $old_value = $urls[$func];
  if (!empty($set_url) && preg_match('|^https?://|', $set_url))
    $urls[$func] = $set_url;

  return $return_old_value ? $old_value : $urls[$func];
}

function get_base_url(?string $set_url = null,bool $return_old_value = false) : string
{
  return get_url($set_url,$return_old_value);
}

function get_site_url(?string $set_url = null,bool $return_old_value = false) : string
{
  return get_url($set_url,$return_old_value);
}

function get_self_url() : array|string|int|false|null
{
  return parse_url(get_base_url() . $_SERVER['REQUEST_URI'],PHP_URL_PATH);
}


/******************************************************************************

  get Route URL 

******************************************************************************/
function get_route_url($route = '',?array $params = null,$suffix = false) : string
{
  $rte = Route::GetInstance(ROUTE_BASE);
  if(empty($route))
    $route = $rte->current();

  $base_url = get_base_url(); 
  if($base_url === '/')
    $base_url = '';

  return sprintf('%s%s',$base_url,$rte->getPath($route,$params,$suffix));
}

function get_route_tag(string $route = '') : string
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

function get_route() : string
{
  $rte = Route::GetInstance(ROUTE_BASE);
  return $rte->current();
}

function get_request_path(?string $request_uri = null) : string
{
  if(empty($request_uri))
    $request_uri = $_SERVER['REQUEST_URI'];

  return parse_url($request_uri,PHP_URL_PATH);
}

function get_form_action_path(string $route = '',?array $params = null) : string
{
  $rte = Route::GetInstance(ROUTE_BASE);
  if($rte->rewritable())
    return get_route_url($route,$params);

  return get_request_path();
}

function get_csrf_tag(mixed $data = null,string $tokenname = 'csrf-tokens',string $name = 'csrf-token') : string
{
  return sprintf('<input type="hidden" name="%s" value="%s" />',
                 $name,
                 CsrfTokens::GetInstance($tokenname)->generate($data));
}

function get_csrf_token(mixed $data = null,string $tokenname = 'csrf-tokens') : string
{
  return CsrfTokens::GetInstance($tokenname)->generate($data);
}