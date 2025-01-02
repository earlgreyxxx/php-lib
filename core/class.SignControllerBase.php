<?php
/*******************************************************************************

  Sign in controller deriverred from WebControllerBase

  All Written by K.,Nakagawa.

*******************************************************************************/
if(!defined('TEMPLATE_SIGN'))
  define('TEMPLATE_SIGN','authentication/sign');
if(!defined('TITLE_SIGN'))
  define('TITLE_SIGN','require sign in');
if(!defined('FAILED_SIGN'))
  define('FAILED_SIGN','failed certification.');

abstract class SignControllerBase extends WebControllerBase
{
  abstract protected function postSignout();
  abstract public function verify();

  protected function init()
  {
    global $SESSION_PARAMS;
    Session::GetInstance(SESSION_APPNAME,$SESSION_PARAMS);

    parent::init();
    $view = $this->getView();
    $view->setHeader(false);
    $view->setFooter(false);
  }

  public function signin()
  {
    $cookie = Cookie::GetInstance('signin');

    $tmpl = $this->getView()->getTemplate();
    $tmpl['uid'] = $cookie['uid'];
    if(!empty($this->r['done']))
      $tmpl['done'] = $this->r['done'];
    do_action('signin');
    return true;
  }

  public function signout()
  {
    do_action('signout');

    Session::GetInstance(SESSION_APPNAME)->destroy();

    return $this->postSignout();
  }

}
