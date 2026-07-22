<?php /**************************************************************************

  Manage CsrfTokens class

    - Constructor parameters (token name )

      name   = used by session.

    - Usage :
      $tokens = new CsrfTokens('tokens')
       or singlton getter
      $tokens = CsrfTokens::GetInstance('tokens');

  All Written by K.,Nakagawa.
*******************************************************************************/

class CsrfTokens
{
  // static members
  public static function GetInstance(string $name = 'csrf-tokens',array $options = array()) : static
  {
    static $pool = array();

    if(!array_key_exists($name,$pool))
      $pool[$name] = new self($name);

    return $pool[$name];
  }

  // Instance memebers
  protected string $name;
  protected int $expire = 10800;

  public function __construct(string $name,?array $options = null)
  {
    if(empty($name) || !is_string($name))
      $name = 'csrf-tokens';

    if(!empty($options))
    {
      if(isset($options['expire']) && is_int($options['expire']) && $options['expire'] > 0)
        $this->expire = $options['expire'];
    }

    $session = &get_session();
    $this->name = $name;
    if(!array_key_exists($name,$session))
      $session[$name] = array();
  }

  public function __destruct()
  {
    $this->cleanup();
  }

  public function setExpire(int $expire) : int
  {
    $rv = $this->expire;
    $this->expire = $expire;

    return $rv;
  }

  public function generate(mixed $data = null) : string
  {
    $session = &get_session();
    $token = sha1(random_bytes(32));
    $session[$this->name][$token] = array(
      'at' => time(),
      'data' => $data
    );

    return $token;
  }

  public function publish(mixed $data = null) : string
  {
    $token = $this->generate($data);

    $cookie = Cookie::GetInstance($this->name);
    $cookie->set('csrf-token',$token);
    $cookie->attr(time() + $this->expire,parse_url(BASE_URL,PHP_URL_PATH));
    $cookie->bake();

    return $token;
  }

  // if $delete is true and $token is valid, call delete method.
  public function verify(?string $token = null,mixed $match = null,bool $delete = true) : bool
  {
    $session = get_session();
    if(empty($token))
      $token = $this->fetch();

    $rv = false;
    if(array_key_exists($token,$session[$this->name]))
    {
      $tokenData = $session[$this->name][$token];
      $rv = ($this->expire >= time() - $tokenData['at']) && ($tokenData['data'] === $match);
    }

    if($rv && $delete)
      $this->delete($token);

    return $rv;
  }

  public function delete(string $token) : void
  {
    $session = &get_session();
    if(array_key_exists($token,$session[$this->name]))
    {
      unset($session[$this->name][$token]);
      $cookie = Cookie::GetInstance($this->name);
      $cookie->set('csrf-token',1);
      $cookie->attr(time() - 100000,parse_url(BASE_URL,PHP_URL_PATH));
      $cookie->bake();
    }
  }

  public function fetch() : string
  {
    return Cookie::GetInstance($this->name)->get('csrf-token');
  } 

  public function cleanup() : void
  {
    $session = &get_session();
    if(!array_key_exists($this->name,$session))
      return;

    $tokens = array_keys($session[$this->name]);

    foreach($tokens as $token)
    {
      if($this->expire < time() - $session[$this->name][$token]['at'])
        unset($session[$this->name][$token]);
    }
  }
}
