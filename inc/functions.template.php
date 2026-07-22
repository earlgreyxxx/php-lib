<?php
/*******************************************************************************

  テンプレートに関する定義

  All Written by K.,Nakagawa.

*******************************************************************************/
require_once __DIR__.'/functions.accessor.php';

function base_url() : void
{
  echo get_base_url();
}
function site_url() : void
{
  echo get_site_url();
}
function lib_url() : void
{
  echo LIB_URL; 
}
function site_lib_url() : void
{
  echo SITE_LIB_URL;
} 

function route_url(?string $route = '',?array $params = null,$suffix = false) : void
{
  echo get_route_url($route,$params,$suffix);
}

function route_tag(string $route = '',string $eol = PHP_EOL) : void
{
  $output = get_route_tag($route);
  if(!empty($output))
    echo $output,$eol;
}

function request_path(?string $request_uri = null) : void
{
  echo get_request_path($request_uri);
}

function form_action_path(string $route = '',?array $params = null) : void
{
  echo get_form_action_path($route,$params);
}

function csrf_tag(mixed $data = null,string $tokenname = 'csrf-tokens',string $name = 'csrf-token',string $eol = PHP_EOL) : void
{
  $output = get_csrf_tag($data,$tokenname,$name);
  if(!empty($output))
    echo $output,$eol; 
}

function csrf_token(mixed $data = null,string $tokenname = 'csrf-tokens') : void
{
  echo get_csrf_token($data,$tokenname);
}
/*------------------------------------------------------------------------------
  各アクションを実行する。
------------------------------------------------------------------------------*/
function my_title() : void
{
  do_action('title');
}
function my_head() : void
{
  do_action('head');
}
function my_header() : void
{
  do_action('header');
}

function my_footer() : void
{
  do_action('footer');
}
