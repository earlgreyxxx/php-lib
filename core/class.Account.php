<?php
/*******************************************************************************

  アカウント管理のベースクラス

  All Written by K.,Nakagawa.

*******************************************************************************/
abstract class Account implements Certification
{
  public static function GetInstance($username,$options = null)
    {
      static $instaces = array();
      if(!isset($instances[$username]))
        $instances[$username] = new static($username,$options);

      return $instances[$username];
    }

  /*------------------------------------------------------------------------------
    Instance members
  ------------------------------------------------------------------------------*/
  private $username;

  // constructor
  public function __construct($username)
    {
      $this->username = $username;
    }

  protected function username()
    {
      return $this->username;
    }
}
