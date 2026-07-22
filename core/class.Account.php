<?php
/*******************************************************************************

  アカウント管理のベースクラス

  All Written by K.,Nakagawa.

*******************************************************************************/
abstract class Account implements Certification
{
  public static function GetInstance(string $username, ?array $options = null): static
  {
    static $instaces = [];
    if (!isset($instances[$username]))
      $instances[$username] = new static($username, $options);

    return $instances[$username];
  }

  /*------------------------------------------------------------------------------
    Instance members
  ------------------------------------------------------------------------------*/
  private string $username;

  // constructor
  public function __construct(string $username, ?array $options = null)
  {
    $this->username = $username;
  }

  protected function username() : string
  {
    return $this->username;
  }
}
