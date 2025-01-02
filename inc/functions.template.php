<?php
/*******************************************************************************

  テンプレートに関する定義

  All Written by K.,Nakagawa.

*******************************************************************************/
require_once __DIR__.'/functions.accessor.php';

function base_url()
{
  echo get_base_url();
}
function site_url()
{
  echo get_site_url();
}
function lib_url()
{
  echo LIB_URL; 
}
function site_lib_url()
{
  echo SITE_LIB_URL;
} 

function route_url($route = '',?array $params = null,$suffix = false)
{
  echo get_route_url($route,$params,$suffix);
}

function route_tag($route = '',$eol = PHP_EOL)
{
  $output = get_route_tag($route);
  if(!empty($output))
    echo $output,$eol;
}

function request_path($request_uri = null)
{
  echo get_request_path($request_uri);
}

function form_action_path($route = '',?array $params = null)
{
  echo get_form_action_path($route,$params);
}

function csrf_tag($data = null,$tokenname = 'csrf-tokens',$name = 'csrf-token',$eol = PHP_EOL)
{
  $output = get_csrf_tag($data,$tokenname,$name);
  if(!empty($output))
    echo $output,$eol; 
}

function csrf_token($data = null,$tokenname = 'csrf-tokens')
{
  echo get_csrf_token($data,$tokenname);
}
/*------------------------------------------------------------------------------
  各アクションを実行する。
------------------------------------------------------------------------------*/
function my_title()
{
  do_action('title');
}
function my_head()
{
  do_action('head');
}
function my_header()
{
  do_action('header');
}

function my_footer()
{
  do_action('footer');
}
