<?php
/*******************************************************************************
 *
 *  Base class for database table
 *
 *   All written by Kenji, Nakagawa.
 *   許可なく配布もしくは使用はできません。
 *
******************************************************************************/

abstract class DatabaseTable
{
  // Instances
  // -----------------------------------------------------------------------
  public function __construct(PDOExtension $pdo,$table)
  {
    $this->setHandle($pdo);
    $this->setTable($table);
  }

  public function beginTransaction()
  {
    return $this->getHandle()->beginTransaction();
  }

  public function commit()
  {
    return $this->getHandle()->commit();
  }

  public function rollBack()
  {
    return $this->getHandle()->rollBack();
  }

  private $pdoex;
  protected function getHandle()
  {
    return $this->pdoex;
  }
  protected function setHandle(PDOExtension $pdoex)
  {
    $this->pdoex = $pdoex;
  }

  private $table;
  protected function getTable()
  {
    return $this->table;
  }
  protected function setTable($table)
  {
    $pdo = $this->pdoex;
    if(empty($table) || !$pdo->exists($table))
      throw new RuntimeException(_('can not detect table'));

    $this->table = $table;
  }

  private $db;
  protected function getDB(bool $useDB = false)
  {
    if($useDB)
      return clone $this->db;
    else
      return DB::CreateInstance($this->getHandle())->select()->from($this->getTable());
  }
  protected function setDB(DB $db)
  {
    $this->db = $db;
    return $this;
  }
  
  private $columns = null;
  protected function getColumns($shift = false)
  {
    $columns = $this->columns;
    if(empty($columns))
    {
      $columns  = $this->getHandle()->getColumns($this->getTable());
      $this->columns = $columns;
    }

    if($shift)
      array_shift($columns);

    return $columns;
  }

  private $idColumn;
  protected function getIdColumn()
  {
    return $this->idColumn;
  }
  protected function setIdColumn($column = null)
  {
    $columns = $this->getHandle()->getColumns($this->getTable());
    if(empty($column))
    {
      $this->idColumn = array_shift($columns);
    }
    else
    {
      if(false === array_search($column,$columns))
        throw new RuntimeException(_('column is not exists'));

      $this->idColumn = $column;
    }

    return $this;
  }
}
