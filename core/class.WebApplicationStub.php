<?php
/*******************************************************************************

  ディスパッチャーから派生した、WebApplication クラス
  このクラスを派生させて使用します。

  All Written by K.,Nakagawa.

*******************************************************************************/

abstract class WebApplicationStub extends Dispatch
{
  /*------------------------------------------------------------------------------
    statics here
  ------------------------------------------------------------------------------*/
  private static mixed $app;
  protected static mixed $userinfo = null;

  // header check
  public static function header_callback() : void
  {
    return;
  }

  /*------------------------------------------------------------------------------
    Entry point
  ------------------------------------------------------------------------------*/
  public static function Main(bool $require_session = false,array $inputs = ['r' => null,'f' => null,'p' => null,'g' => null]) : mixed
  {
    $rv = false;

    if ($require_session)
    {
      global $SESSION_PARAMS;
      Session::GetInstance(SESSION_APPNAME, $SESSION_PARAMS);
    }

    //デフォルトヘッダー送出の予約
    if (function_exists('header_register_callback'))
      header_register_callback(__CLASS__ . '::header_callback');
    try {
      if (!static::$app)
        static::$app = new static(
          [
            'input' => [
              $inputs['r'],
              $inputs['f'],
              $inputs['p'],
              $inputs['g']
            ]
          ]
        );

      $rv = static::$app->invoke();
    } catch (Exception $e) {
      $view = ViewBase::CreateInstance();
      $tmpl = $view->getTemplate();
      $message = $e->getMessage();
      $tmpl['server_error'] = $message;

      $view->error500();
    }

    return $rv;
  }

  /*------------------------------------------------------------------------------
    with HTTP Digest Authentication
  ------------------------------------------------------------------------------*/
  public static function HttpDigestMain(bool $require_session = false,string $realm = 'Restricted Area',string $error = '',array $inputs = ['r' => null,'f' => null,'p' => null,'g' => null]) : mixed
  {
    if (!defined('HTPASSWD'))
      throw new Exception(_('HTPASSWD file is not defined.'));

    $httpAuth = new HttpAuthentication(array('realm' => $realm, 'error' => $error, 'file' => HTPASSWD));
    if (false === (static::$userinfo = $httpAuth->verify($_SERVER['PHP_AUTH_DIGEST'], $_SERVER['REQUEST_METHOD'])))
    {
      //通常ここに制御が移ることはない。
      exit("you have to process login. \n");
    }

    return static::Main($require_session, $inputs);
  }

  /*------------------------------------------------------------------------------
     require login authentication with session
  ------------------------------------------------------------------------------*/
  public static function SessionMain($session_name = '',$options = array(),$inputs = ['r' => null,'f' => null,'p' => null,'g' => null]) : mixed
  {
    if (empty($session_name))
      $session_name = SESSION_APPNAME;
    if (empty($options))
    {
      global $SESSION_PARAMS;
      $options = $SESSION_PARAMS;
    }

    $session = Session::GetInstance($session_name, $options);

    $expired = time() > $session->get('expire');
    static::$userinfo = $session->get('userinfo');
    if (!is_array(static::$userinfo) || $expired)
    {
      $url = sprintf(
        '%smode=signin&done=%s',
        get_route_url('/sign', null, true),
        urlencode($_SERVER['REQUEST_URI'])
      );

      header("location: $url");
      exit;
    }

    if (time() > $session->get('refresh'))
    {
      $session->update();
      $session->set('refresh', time() + EXPIRE_ID_SIGN);
    }

    return static::Main(true,$inputs);
  }

  /*------------------------------------------------------------------------------
    Instances here
  ------------------------------------------------------------------------------*/

  // override initializing
  protected function init() : void
    {
      $this->model = $this->createModel();
      $this->view = $this->createView();
      if($this->view !== false && $this->view instanceof ViewBase)
        { 
          // set default header,footer,content...
          $this->view->setHeader(array('header'));
          $this->view->setFooter(array('footer'));
        }
      do_action('init');
    }

  // Model Object
  protected Mixed $model;
  protected function createModel() : mixed
  {
    return null;
  }
  protected function getModel() : mixed
  {
    return $this->model;
  }
  
  // ViewBase oject
  protected ViewBase $view;

  protected function createView(): ViewBase
  {
    return ViewBase::CreateInstance();
  }

  protected function setView(ViewBase $view): ?ViewBase
  {
    $rv = $this->view;
    if (!($view instanceof ViewBase))
      throw new Exception(_('Invalid arguments was given...'));

    $this->view = $view;
    return $rv;
  }

  protected function getView() : ViewBase
  {
    return $this->view;
  }

  // get current page number
  protected function getPage(string $name = 'p') : int
  {
    $r = $this->r;
    $rv = 1;

    if (isset($r[$name]) && is_numeric($r[$name]))
    {
      $rv = intval($r[$name]);
      if ($rv <= 0)
        $rv = 1;
    }
    return $rv;
  }

  // Default request handler
  protected function __default() : void
  {
    $this->getView()->error403();
  }

  /*------------------------------------------------------------------------------
    Logout
  ------------------------------------------------------------------------------*/
  protected function do_logout() : bool
  {
    global $SESSION_PARAMS;
    Session::GetInstance(SESSION_APPNAME, $SESSION_PARAMS)->destroy();

    $this->getView()->getResponse()->redirect(get_base_url() . '/');
    return false;
  }
}
