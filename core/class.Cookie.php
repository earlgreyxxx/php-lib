<?php
/*******************************************************************************

  クッキー・クラス

  一つのクッキーに、シリアライズしたデータをストアします。

  All Written by K.,Nakagawa.

*******************************************************************************/

class Cookie extends KeyValueCollection
{
  //クッキー容量制限値
  static private $COOKIE_LIMIT = 4096;

  public static function GetInstance($cookiename = null,$encrypted = false)
    {
      $rv = false;
      $cookie = get_cookie();
      if(empty($cookiename))
        {
          $rv = array();
          foreach(array_keys($cookie) as $cookiename_)
            $rv[] = new self($cookiename_,array('data' => $cookie[$cookiename_],
                                                'encrypted' => $encrypted));
        }
      else if(array_key_exists($cookiename,$cookie))
        {
          $rv = new self($cookiename,array('data' => $cookie[$cookiename],
                                           'encrypted' => $encrypted));
        }
      else
        {
          $rv = new self($cookiename,array('encrypted' => $encrypted));
        }

      return $rv;
    }

  public static function CreateInstance($cookiename,$params = array())
    {
      $rv = false;
      $cookie = get_cookie();
      if(array_key_exists($cookiename,$cookie))
        $params['data'] = $cookie[$cookiename];

      return new self($cookiename,$params);
    }

  // alias to setcookie function
  public static function Raw($name,$value = "",$expire = 0,$path = "",$domain = "",$secure = false,$httponly = false,$samesite = 'strict')
    {
      if(PHP_VERSION_ID < 70300)
        return setcookie($name,$value,$expire,$path,$domain,$secure,$httponly);
      else
        return setcookie(
          $name,
          $value,
          [
            'expires' => $expire,
            'path' => $path,
            'domain' => $domain,
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => $samesite
          ]
        );
    }

  /*------------------------------------------------------------------------------
    Instance Members
  ------------------------------------------------------------------------------*/
  protected $name = null;
  protected $expire = 0;
  protected $path = '/';
  protected $domain = '';
  protected $secure = false;
  protected $http_only = false;
  protected $encrypted = false;
  protected $samesite = 'Strict';

  /*------------------------------------------------------------------------------
   constructor ： $name => cookie name,
                  $params => array(data      => serialized data for initialization,
                                   encrypted => string
                                   expire    => int ,
                                   path      => string,
                                   domain    => string,
                                   secure    => bool,
                                   http_only => bool,
                                   samesite => 'Lax' or 'Strict' or 'None')
                         
  ------------------------------------------------------------------------------*/
  public function __construct($name = '', $params = array())
  {
    //クッキー名が指定されていない場合はファイルの更新時刻と現在時刻から勝手に決める
    if(empty($name))
      $name = sha1(filemtime(__FILE__).time());

    $this->id(str_uniqid($name.'-'));

    $this->name = $name;

    if(isset($params['encrypted']) && strlen($params['encrypted']) > 0)
      $this->encrypted = $params['encrypted'];

    if(isset($params['data']) && is_string($params['data']) && !empty($params['data']))
    {
      $data = $params['data'];
      if($this->encrypted !== false)
        $data = str_decrypt($params['data'],$this->encrypted);

      if(false !== ($data = unserialize(str_replace('\"','"',$data))))
        $this->set($data);
    }

    $ar = array();
    foreach(array('expire','domain','secure','http_only','samesite') as $arg)
    {
      if(isset($params[$arg]))
        $this->{$arg} = $params[$arg];
    }
    if(isset($params['path']))
      $this->path = $params['path'];
    else if(defined('BASE_URL')) 
      $this->path = parse_url(BASE_URL,PHP_URL_PATH);
  }

  public function attr($expire = 0,$path = '',$domain = '',$secure = false,$http_only = false,$samesite = 'Strict')
    {
      $this->expire    = $expire;
      $this->path      = $path;
      $this->domain    = $domain;
      $this->secure    = $secure;
      $this->http_only = $http_only;
      $this->samesite  = $samesite;

      return $this;
    }

  public function bake()
    {
      $serialized = serialize($this->get_container());
      if($this->encrypted !== false)
        $serialized = str_encrypt($serialized,$this->encrypted);

      if(strlen($serialized) > self::$COOKIE_LIMIT)
        return false;

      return self::Raw($this->name,
                       $serialized,
                       $this->expire,
                       $this->path,
                       $this->domain,
                       $this->secure,
                       $this->http_only,
                       $this->samesite);
    }

  public function __call($name, $arguments)
    {
      $properties = get_object_vars($this);
      if(isset($properties[$name]))
      {
        if(!empty($arguments[0]))
        {
          $this->$name = $arguments[0];
          return $arguments[0];
        }
        else
        {
          return $properties[$name];
        }
      }

      throw new Exception(_('call unknown method.'));
    }
}

