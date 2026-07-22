<?php
/*******************************************************************************

  ユーザー・パスワード認証を利用したアカウント（ストアオブジェクト)

  All Written by K.,Nakagawa.

*******************************************************************************/
class AccountPasswdStore extends Account
{
  protected static function get_user_info(string $username, array $row): array|false
  {
    return array(
      'userID'   => $row['account_id'],
      'username' => $row['account_name'],
      'digest'   => $row['account_digest'],
      'email'    => $row['account_email']
    );
  }

  /*------------------------------------------------------------------------------
    Instance members
  ------------------------------------------------------------------------------*/
  protected AccountStore $store;

  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct(string $username, array $options = [])
  {
    parent::__construct($username);

    if (isset($options['object']) && isset($options['etc']))
    {
      $dsn =  $options['object'];
      $table = $options['etc'];
    }
    else
    {
      throw new Exception(_('DSN or Table name is not defined.'));
    }

    $this->store = AccountStore::GetInstance($dsn, $table, $options['user'] ?? '',$options['password'] ?? '');
  }
  /*------------------------------------------------------------------------------
    certification process
  ------------------------------------------------------------------------------*/
  public function certify(mixed $data, ?array $params = null): bool
  {
    $rv = false;
    if (
      false !== ($row = $this->store->get_values($this->username(), false)) &&
      $row['digest'] === crypt($data, $row['digest'])
    ) $rv = self::get_user_info($this->username(), $row);

    return $rv;
  }
}