<?php
/*******************************************************************************

  Key - Value 値をデータベースへ保存するテーブル・アクセサ

  Meta クラスから派生する。meta_fkeyはインスタンス毎に固定で使う。

  All Written by K.,Nakagawa.

*******************************************************************************/

class Dictionary extends KeyValueCollection
{
  /*------------------------------------------------------------------------------
    Static members
  ------------------------------------------------------------------------------*/
  const META_FOREIGN_KEYNAME = 'meta_group';

  /* Create tables or initialize table.*/
  public static function Prepare(string|PDOExtension $dsn, string $table, string $user = '', string $passwd = '') : bool
  {
    $pdo = $dsn instanceof PDOExtension ? $dsn : GetPdoInstance($dsn, $user, $passwd);
    return Meta::Prepare($pdo, $table, self::META_FOREIGN_KEYNAME);
  }

  private static array $DEFAULT_OPTIONS = array('table' => 'dictionary', 'gid' => 1, 'user' => '', 'password' => '');
  private static string $HINT = '3322772b7bc1db0c506273d6a5654566';

  /*------------------------------------------------------------------------------
    Instance members
  ------------------------------------------------------------------------------*/

  //container
  private ?Meta $meta = null;

  private string $id;

  //group ID
  private $gid = 1;

  public function __construct(string|PDOExtension $dsn, array $options = [])
  {
    if (empty($dsn) || empty($options['table']))
      throw new Exception(_('Dictionary requires DSN string and table name'));

    $options = array_merge(self::$DEFAULT_OPTIONS, $options);
    $this->gid = $options['gid'];

    if ($dsn instanceof PDO)
    {
      // if $dsn is PDO intance, $dsn is set to object hash of PDO instance.
      $pdo = $dsn;
      $dsn = spl_object_hash($pdo);
    }
    else
    {
      $pdo = GetPdoInstance($dsn, $options['user'], $options['password']);
    }

    $this->id(str_uniqid($dsn . '-'));
    $this->meta = new Meta($pdo, $options['table'], $options);
  }

  public function attachFilter(Filter $filter) : ?Filter 
  {
    return $this->meta->attachFilter($filter);
  }

  public function begin() : bool
  {
    return $this->meta->beginTransaction();
  }

  public function end($is_rollback = false) : bool
  {
    return $is_rollback ? $this->meta->rollBack() : $this->meta->commit();
  }

  //getter
  protected function &get_container() : mixed
  {
    return $this->meta;
  }

  //getter & setter
  protected function group(?int $gid = null) : ?int
  {
    $rv = $this->gid;
    if (is_int($gid) && $gid > 0)
      $this->gid = $gid;

    return $rv;
  }

  //内部メソッドの実装
  protected function kv_exists(int|string $k,mixed $v, array $options) : bool
  {
    return 0 < $this->meta->count($this->gid, $k);
  }

  protected function kv_keys(int|string|null $k, mixed $v, array $options) : array
  {
    return $this->meta->keys($this->gid);
  }

  protected function kv_set(int|string|array $k, mixed $v, array $options) : mixed
  {
    return $this->meta->set($this->gid, $k, $v);
  }

  protected function kv_get(int|string|array $k, mixed $v, array $options) : mixed
  {
    $is_multi = array_key_exists('multi', $options) && $options['multi'] === true;

    return $this->meta->get($this->gid, $k, $is_multi);
  }

  protected function kv_delete(int|string $k, mixed $v, array $options) : mixed
  {
    return $this->meta->remove($this->gid, $k);
  }

  protected function kv_clear(int|string|null $k, mixed $v, array $options) : mixed
  {
    return $this->meta->clear($this->gid);
  }
}