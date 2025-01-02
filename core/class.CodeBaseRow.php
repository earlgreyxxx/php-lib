<?php
/*******************************************************************************
 *
 *  Base class for a row
 *
 *   All written by Kenji, Nakagawa.
 *   許可なく配布もしくは使用はできません。
 *
******************************************************************************/
class CodeBaseRow extends DatabaseRow
{
  private function _imp_load_from_pk_or_code($id)
  {
    if(!is_numeric($id))
      throw new RuntimeException(_('invalid key was given'));

    $pdo = $this->getHandle();
    $columns = $pdo->getColumns($this->getTable());

    if(is_int($id))
    {
      $condition = sprintf(
        '%s = %d',
        $pdo->quoteColumns($columns[0]),
        $id
      );
    }
    else if(is_string($id))
    {
      $condition = sprintf(
        '%s = %s',
        $pdo->quoteColumns($columns[1]),
        $pdo->quote($id)
      );
    }
    else
    {
      throw new RuntimeException(_('invalid parameter was given'));
    }

    $db = $this->getDB()->where($condition);

    if(false === ($sth = $db->select()->query()))
    {
      DB::SetErrorInfo($pdo->errorInfo());
      DB::SetErrorInfo($db->getQuery());
      throw new RuntimeException(_('Database access failed'));
    }

    if(false === ($row = $sth->fetch(PDO::FETCH_OBJ)))
    {
      $this->row = [];
      $this->exists(false);
      return false;
    }

    $this->row = $row;
    $this->exists(true);

    return true;
  }

  public function load($uniq_key)
  {
    $rv = false;
    $row = $this->get();
    if($row)
      return $row;

    return $this->_imp_load_from_pk_or_code($uniq_key);
  }

  public function __construct(PDOExtension $pdo,$table,$uniqkey = null)
  {
    if($uniqkey === null)
      $uniqkey = array_fill(0,count($pdo->getColumns($table)) - 1,null);

    parent::__construct($pdo,$table,$uniqkey);
  }
}
