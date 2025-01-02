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
  public static function Prepare($dsn,$table,$user = '',$passwd = '')
    {
      $pdo = $dsn instanceof PDO ? $dsn : GetPdoInstance($dsn,$user,$passwd);
      return Meta::Prepare($pdo, $table, self::META_FOREIGN_KEYNAME);
    }

  private static $DEFAULT_OPTIONS = array('table' => 'dictionary','gid' => 1, 'user' => '','password' => '');
  private static $HINT = '3322772b7bc1db0c506273d6a5654566';

  /*------------------------------------------------------------------------------
    Instance members
  ------------------------------------------------------------------------------*/

  //container
  private $meta = null;

  private $id;

  //group ID
  private $gid = 1;

  public function __construct($dsn,$options = array())
    {
      if(empty($dsn) || empty($options['table']))
        throw new Exception(_('Dictionary requires DSN string and table name'));

      $options = array_merge(self::$DEFAULT_OPTIONS,$options);
      $this->gid = $options['gid'];

      if($dsn instanceof PDO)
      {
        // if $dsn is PDO intance, $dsn is set to object hash of PDO instance.
        $pdo = $dsn;
        $dsn = spl_object_hash($pdo);
      } 
      else
      {
        $pdo = GetPdoInstance($dsn,$options['user'],$options['password']);
      }

      $this->id(str_uniqid($dsn.'-'));
      $this->meta = new Meta($pdo,$options['table'],$options);
    }

  public function attachFilter($filter)
    {
      return $this->meta->attachFilter($filter);
    }

  public function begin()
  {
    return $this->meta->beginTransaction();
  }

  public function end($is_rollback = false)
  {
    return $is_rollback ? $this->meta->rollBack() : $this->meta->commit();
  }

  //getter
  protected function &get_container()
  {
    return $this->meta;
  }

  //getter & setter
  protected function group($gid = null)
  {
    $rv = $this->gid;
    if(is_int($gid) && $gid > 0)
      $this->gid = $gid;

    return $rv;
  }

  //内部メソッドの実装
  protected function kv_exists($k,$v,$options)
  {
    return 0 < $this->meta->count($this->gid,$k);
  }

  protected function kv_keys($k,$v,$options)
  {
    return $this->meta->keys($this->gid);
  }

  protected function kv_set($k,$v,$options)
  {
    return $this->meta->set($this->gid,$k,$v);
  }

  protected function kv_get($k,$v,$options)
  {
    $is_multi = array_key_exists('multi',$options) && $options['multi'] === true;

    return $this->meta->get($this->gid,$k,$is_multi);
  }

  protected function kv_delete($k,$v,$options)
  {
    return $this->meta->remove($this->gid,$k);
  }

  protected function kv_clear($k,$v,$options)
  {
    return $this->meta->clear($this->gid);
  }
}
