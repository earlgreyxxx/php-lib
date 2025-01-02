<?php
/*******************************************************************************

  ユーザー・パスワード認証を利用したアカウント（パスワードファイル）

  All Written by K.,Nakagawa.

*******************************************************************************/
class AccountPasswdFile extends Account
{
  protected static function get_user_info($username,$filepath = '')
    {
      if(empty($filepath))
        $filepath = '/etc/passwd';

      $rv = false;
      $passwd_content = file_get_contents($filepath);
      if(0 < strlen($passwd_content))
        {
          foreach(preg_split('/[\r\n]+/',$passwd_content) as $line)
            {
              if(preg_match('/^\s*$/',$line))
                continue;

              $csv = explode(',',$line);
              if($csv[1] === $username)
                {
                  $rv = array_combine(array('id','name','digest'),$csv);
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

  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($username,array $options = array())
    {
      parent::__construct($username);

      $this->filepath = isset($options['object']) && file_exists($options['object']) ? $options['object'] : '';

      if(empty($this->filepath))
        throw new Exception(_('file is not defined or not exists.'));
    }

  /*------------------------------------------------------------------------------
    Certification Process
  ------------------------------------------------------------------------------*/
  public function certify($data,$params = null)
    {
      $userinfo = false;
      $account = static::get_user_info($this->username(),$this->filepath);

      if($account !== false)
        {
          if($account['digest'] === crypt($data,$account['digest']))
            {
              $userinfo = array('id' => $account['id'],
                                'name' => $account['name']);
            }
        }

      return $userinfo;
    }
}

  
