<?php
/*******************************************************************************

  ダイジェスト認証

  ex.)
  $httpAuth = new HttpAuthentication(array('realm' => 'Members Area'));
  $result = $httpAuth->verify()
  認証に成功すると、ユーザー名が返ります。
  認証に失敗すると、キャンセルしない限り認証ダイアログを出します。

  All Written by K.,Nakagawa.

*******************************************************************************/

class HttpAuthentication
{
  /*------------------------------------------------------------------------------
    静的メンバ
  ------------------------------------------------------------------------------*/
  protected static string $DigestAlgorithm = 'sha256';

  public static function Digest(string $text) : string
  {
    return md5($text);
  }

  public static function DigestEx(string $text) : string
  {
    return hash(static::$DigestAlgorithm,$text);
  }

  public static function GetDigistAlgorithm() : string
  {
    return static::$DigestAlgorithm;
  }

  //ファクトリ
  public static function Invoke(string $passwdpath,string $realm) : string|false
  {
    $httpAuth = new self([
      'file'  => $passwdpath,
      'realm' => $realm
    ]);

    return $httpAuth->verify();
  }

  // parse http auth headers
  public static function parse(string $auth_digest, string $request_method) : array
  {
    // データが失われている場合への対応
    $needed_parts = ['nonce' => 1, 'nc' => 1, 'cnonce' => 1, 'qop' => 1, 'username' => 1, 'uri' => 1, 'response' => 1];
    $data = [];
    $keys = implode('|', array_keys($needed_parts));

    preg_match_all('@(' . $keys . ')=(?:([\'"])([^\2]+?)\2|([^\s,]+))@', $auth_digest, $matches, PREG_SET_ORDER);

    foreach ($matches as $m)
    {
      $data[$m[1]] = $m[3] ? $m[3] : $m[4];
      unset($needed_parts[$m[1]]);
    }

    $rv = [];
    if (empty($needed_parts))
    {
      $rv[] = $data['username'];
      unset($data['username']);
      $data['method'] = $request_method;

      $rv[] = $data;
    }

    return  $rv;
  }

  /*------------------------------------------------------------------------------
    ここからインスタンスメンバ
  ------------------------------------------------------------------------------*/
  private string $realm;
  private string $user;
  private string|array $error;
  private string $file;
  private string $request_method = 'POST';

  //constructor
  // $params ('realm' => realm ,'error' => template name of error)
  public function __construct($params = [])
  {
    $this->realm = !empty($params['realm']) ? $params['realm'] : 'Restricted Area';
    $this->error = !empty($params['error']) ? $params['error'] : ['error/401'];
    $this->file  = !empty($params['file']) ? $params['file'] : '/etc/htpasswd';
  }

  //certificate user/password.
  public function verify(string $auth_digest = '', string $request_method = ''): string|false
  {
    $rv = false;
    if (empty($auth_digest))
      $auth_digest = $_SERVER['PHP_AUTH_DIGEST'] ?? '';
    if (empty($request_method))
      $request_method = $_SERVER['REQUEST_METHOD'] ?? '';

    if (!empty($auth_digest) && !empty($request_method))
    {
      list($username, $data) = self::parse($auth_digest, $request_method);
      $options = ['object' => $this->file];

      if ($username && $data && AccountHtdigest::GetInstance($username, $options)->certify($data, $this->realm))
        $rv = $this->user = $username;
    }

    if (!$rv)
      $this->header401();

    return $rv;
  }

  // return authenticatated user name.
  public function username() : string
  {
    return $this->user;
  }

  // output header 401.
  private function header401()
  {
    $www_auth = sprintf(
      'Digest realm="%s",qop="auth, auth-int",nonce="%s",opaque="%s"',
      $this->realm,
      static::DigestEx(random_bytes(2048)),
      static::Digest($this->realm)
    );

    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: ' . $www_auth);
    header('Content-type: text/html');

    if ($this->error)
    {
      if (is_array($this->error))
      {
        if (count($this->error) < 2)
          $this->error[] = '';

        list($name, $suffix) = $this->error;
        TemplateBase::GetInstance(TEMPLATE_DIR)->apply($name, $suffix);
      }
      else
      {
        echo $this->error;
      }
    }

    exit;
  }
}
