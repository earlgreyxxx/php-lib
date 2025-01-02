<?php
/*******************************************************************************

  ログイン・ディスパッチャー(基本クラス)

  copyright k.,nakagawa

*******************************************************************************/

if(!defined('EXPIRE_ID_SIGN'))
  define('EXPIRE_ID_SIGN',1800);

abstract class SignBase extends WebApplicationStub
{
  protected static $app;

  /*------------------------------------------------------------------------------
    Instances here
  ------------------------------------------------------------------------------*/
  protected function init()
  {
    parent::init();
    $view = $this->getView();

    $view->setHeader(false);
    $view->setFooter(false);
  }

  protected function do_signin()
  {
    $cookie = Cookie::GetInstance('signin');

    $tmpl = $this->getView()->getTemplate();
    $tmpl['uid'] = $cookie['uid'];
    if(!empty($this->r['done']))
      $tmpl['done'] = $this->r['done'];
    do_action('signin');
    return true;
  }

  // mode=post
  protected function do_signin_certificate()
  {
    $session = Session::GetInstance(SESSION_APPNAME);

    if(false == CsrfTokens::GetInstance('csrf-tokens')->verify($this->p['csrf-token']))
      throw new Exception(_('invalid post'));

    $username = str_quotes($this->r['username']);
    $password = str_quotes($this->r['pass']);
    $done = $this->r['done'];
    if(empty($done))
      $done = get_base_url();

    $params = array();
    $redirect_url = false;
    $account = $this->getModel()->getAccount($username);

    $cookie = new Cookie('signin',['path' => SESSION_PATH]);

    if(false !== ($userinfo = $account->certify($password)))
    {
      $session->set(
        array(
          'userinfo' => $userinfo,
          'expire' => time() + (defined('SESSION_LIFETIME') && SESSION_LIFETIME > 0 ? SESSION_LIFETIME : EXPIRE_ID_SIGN),
          'refresh' => time() + EXPIRE_ID_SIGN
        )
      );
      $cookie['null'] = 1;
      $cookie->expire(1);
      $cookie->bake();
      do_action('signcert_success');
    }
    else
    {
      $params['done'] = $done;
      $params['mode'] = 'signin';
      $params['result'] = 'fail';
      $done = get_route_url('/sign',null,true);

      $cookie['uid'] = $username;
      $cookie->bake();

      do_action('signcert_fail');
    }
    $this->getView()->getResponse()->redirect($done,$params,'');
    return false;
  }

  protected function do_signout()
  {
    do_action('signout');

    Session::GetInstance(SESSION_APPNAME)->destroy();

    header(sprintf('location: %s/',get_base_url()));
    return false;
  }
}
