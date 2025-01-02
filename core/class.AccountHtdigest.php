<?php
/*******************************************************************************

  HTTPダイジェスト認証ベースのアカウント管理

  All Written by K.,Nakagawa.

*******************************************************************************/
class AccountHtdigest extends Account
{
  protected static function get_user_info($username,$realm,$filepath = '')
    {
      if(empty($filepath))
        $filepath = '/etc/htpasswd';

      $rv = false;
      $passwd_content = file_get_contents($filepath);
      if(0 < strlen($passwd_content))
        {
          foreach(preg_split('/[\r\n]+/',$passwd_content) as $line)
            {
              if(preg_match('/^\s*$/',$line))
                continue;

              $csv = explode(':',$line);
              if($csv[0] === $username && $csv[1] === $realm)
                {
                  $rv = array_combine(array('username','realm','digest'),$csv);
                  break;
                }
            }
        }
      return $rv;
    }

  /*------------------------------------------------------------------------------
    Instance members
  ------------------------------------------------------------------------------*/
  protected $filepath;

  //Internal methods.
  private function digest($realm = 'Restricted Area')
    {
      $rv = false;
      $userinfo = false;
      $account = static::get_user_info($this->username(),$realm,$this->filepath);

      if(false !== $account)
        $rv = $account['digest'];

      return $rv;
    }

  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($username,array $options = array())
    {
      parent::__construct($username);

      $this->filepath = isset($options['object']) && file_exists($options['object']) ? $options['object'] : '';
    }

  /*------------------------------------------------------------------------------
    'HTTP DIGEST AUTHENTICATION'
    $data : parsed data of $_SERVER['PHP_AUTH_DIGEST'] and $_SERVER['REQUEST_METHOD']
              with using HttpAuthentication::parse() method.
  ------------------------------------------------------------------------------*/
  public function certify($data = null,$params = 'Restricted Area')
    {
      $rv = false;
      if(false !== ($A1 = $this->digest($params)))
        {
          $A2 = HttpAuthentication::Digest($data['method'].':'.$data['uri']);
          $A3 = $A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2;

          $rv = $data['response'] === HttpAuthentication::Digest($A3);
        }
      return $rv;
    }
}

