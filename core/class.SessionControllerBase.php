<?php
/*******************************************************************************

  Base Controller for web application

  All Written by K.,Nakagawa.

*******************************************************************************/
if(!defined('EXPIRE_ID_SIGN'))
  define('EXPIRE_ID_SIGN',1800);

if(!defined('EXPIRE_AVAILABLE'))
{
  $base = 3600 * 8;
  global $SESSION_PARAMS;
  $lifetime = $SESSION_PARAMS['lifetime'];

  define('EXPIRE_AVAILABLE',$lifetime <= $base ? $base : $lifetime);
  unset($base,$lifetime);
}

class SessionControllerBase extends WebControllerBase
{
  protected static $userinfo = null;

  protected function init()
  {
    global $SESSION_PARAMS;
    $session = Session::GetInstance(SESSION_APPNAME,$SESSION_PARAMS);

    $expire = $session->get('expire');
    $current = time();

    $expired = $expire && $current >= $expire;

    static::$userinfo = $session->get('userinfo');
    if(!is_array(static::$userinfo) || $expired)
    {
      $url = sprintf(
        '%sdone=%s',
        get_route_url('/sign',null,true),
        urlencode($_SERVER['REQUEST_URI'])
      );

      if($expired && $current - $expire <= EXPIRE_AVAILABLE)
        $url .= '&expired=1';

      header("location: $url");
      exit;
    }

    if(time() > $session->get('refresh'))
    {
      $expire_sign = $this->getExpireSignTime();
      if($expire_sign == 0)
        $expire_sign = EXPIRE_ID_SIGN;

      $session->update();
      $session->set('refresh', $current + $expire_sign);
    }

    // 初期化
    parent::init();
  }

  private $expire_time_sign = 0;
  protected function setExpireSignTime($second)
  {
    if(!is_int($second))
      throw new RuntimeException(_('invalid argument type'));

    if($second < 0)
      throw new RuntimeException(_('invalid value range'));

    $this->expire_time_sign = $second;
  }
  public function getExpireSignTime()
  {
    return $this->expire_time_sign;
  }

}
