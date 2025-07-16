<?php
/*******************************************************************************

  Session class

    - Constructor parameters (key name  => description : default value)

      name   = 'session name',
      params = [ savepath      => セッションファイルの保存先ディレクトリパス : php.ini,
                 lifetime      => 生存期間（秒数）: 0 セッション内,
                 path          => セッション適用URL仮想パス : /
                 domain        => セッション適用ドメイン : 環境変数 HTTP_HOST,
                 secure        => セッション保護 : false,
                 httponly      => セッション保護 : false,
                 samesite      => セッション保護 : 'lax'
                 wait          => セッションの開始を遅延する : false,
                 container     => テスト用のセッションコンテナ(デバッグ用途)

    - Usage :
      $session = new Session('phpsession',  array( 'wait' => true ) );
       or singlton getter
      $session = Session::GetInstance('phpsession',array( 'wait' => true ) );
      $session->start();

  All Written by K.,Nakagawa.
*******************************************************************************/

class Session extends KeyValueCollection
{
  // Object constant
  // --------------------------------------------------------------------------
  const SESSION_COOKIE_LIFETIME = 3600*24*90;


  // Statics
  // --------------------------------------------------------------------------

  //セッションが始まっているか？
  private static $is_start = false;

  // Instances
  // --------------------------------------------------------------------------

  //このセッションを継続するか？
  private $status = true;

  protected function getStatus()
  {
    return $this->status;
  }
  protected function setStatus($status)
  {
    if(is_bool($status))
      $this->status = $status;

    return $this;
  }

  protected $arguments;

  // constructor
  protected function __construct($name,$params = array())
  {
    if(!empty($name))
      session_name($name);

    $this->id(str_uniqid($name . '-'));

    $this->init($params);
  }

  private function init($params)
  {
    if(isset($params['savepath']) && !empty($params['savepath']))
    {
      if(!is_dir($params['savepath']))
        mkdir($params['savepath'],0777,true);

      session_save_path($params['savepath']);
    }

    $this->arguments = [
      'lifetime' => isset($params['lifetime']) ? intval($params['lifetime']) : self::SESSION_COOKIE_LIFETIME,
      'path' => '',
      'domain' => '',
      'secure' => false,
      'httponly' => true,
      'samesite' => 'lax'
    ];

    if(PHP_VERSION_ID < 70300)
    {
      $arguments = array(0,'','',false,false);
      $arguments[0] = isset($params['lifetime']) ? intval($params['lifetime']) : self::SESSION_COOKIE_LIFETIME;

      if(!empty($params['path']))
        $this->arguments['path'] = $arguments[1] = $params['path'];
      if(!empty($params['domain']))
        $this->arguments['domain'] = $arguments[2] = $params['domain'];
      if(isset($params['secure']) && is_bool($params['secure']))
        $this->arguments['secure'] = $arguments[3] = $params['secure'];
      if(isset($params['httponly']) && is_bool($params['httponly']))
        $this->arguments['httponly'] = $arguments[4] = $params['httponly'];

      call_user_func_array('session_set_cookie_params',$arguments);
    }
    else
    {
      if(!empty($params['path']))
        $this->arguments['path'] = $params['path'];
      if(!empty($params['domain']))
        $this->arguments['domain'] = $params['domain'];
      if(isset($params['secure']) && is_bool($params['secure']))
        $this->arguments['secure'] = $params['secure'];
      if(isset($params['httponly']) && is_bool($params['httponly']))
        $this->arguments['httponly'] = $params['httponly'];
      if(isset($params['samesite']) && ($params['samesite'] === 'none' || $params['samesite'] === 'lax' || $params['samesite'] === 'strict'))
        $this->arguments['samesite'] = $params['samesite'];

      call_user_func('session_set_cookie_params',$this->arguments);
    }

    if(!isset($params['wait']) || $params['wait'] !== true)
    {
      if(!isset($params['container']))
        $params['container'] = null;

      $this->start($params['container']);
    }
  }

  public function start($container = null)
  {
    if($this->is() == false)
    {
      session_start();
      $this->is(true);

      $this->set_container(get_session($container));
    }
  }

  protected function is($is_start = null)
  {
    if(is_bool($is_start))
    {
      self::$is_start = $is_start;
      return $this;
    }

    return self::$is_start;
  }

  public function renew($is_delete = true)
  {
    session_regenerate_id($is_delete);
  }

  public function end()
  {
    session_write_close();
    $this->is(false);
  }

  public function update()
  {
    if(true === $this->getStatus())
    {
      $this->renew();

      Cookie::Raw(
        session_name(),
        session_id(),
        time() + $this->arguments['lifetime'],
        $this->arguments['path'],
        $this->arguments['domain'],
        $this->arguments['secure'],
        $this->arguments['httponly'],
        $this->arguments['samesite'] ?? 'lax'
      );
    }
    else
    {
      $this->destroy(false);
    }
    return $this; 
  }

  public function destroy($call_destroy = true)
  {
    if($this->is())
    {
      $this->clear();
      $params = session_get_cookie_params();

      Cookie::Raw(
        session_name(),
        '',
        1,
        $this->arguments['path'],
        $this->arguments['domain'],
        $this->arguments['secure'],
        $this->arguments['httponly'],
        $this->arguments['samesite'] ?? 'lax'
      );

      if($call_destroy === true)
        session_destroy();

      $this->is(false);
    }
  }

  public function block($immediately = false)
  {
    $this->setStatus(false);
    if($immediately === true)
      $this->update();

    return $this;
  }
}
