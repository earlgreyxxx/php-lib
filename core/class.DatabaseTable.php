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
  public function __construct(PDOExtension $pdo,string $table)
  {
    $this->setHandle($pdo);
    $this->setTable($table);
  }

  public function beginTransaction() : bool
  {
    return $this->getHandle()->beginTransaction();
  }

  public function commit() : bool
  {
    return $this->getHandle()->commit();
  }

  public function rollBack() : bool
  {
    return $this->getHandle()->rollBack();
  }

  private PDOExtension $pdoex;
  protected function getHandle() : PDOExtension
  {
    return $this->pdoex;
  }
  protected function setHandle(PDOExtension $pdoex) : void
  {
    $this->pdoex = $pdoex;
  }

  private string $table;
  protected function getTable() : string
  {
    return $this->table;
  }
  protected function setTable(string $table) : void
  {
    $pdo = $this->pdoex;
    if(empty($table) || !$pdo->exists($table))
      throw new RuntimeException(_('can not detect table'));

    $this->table = $table;
  }

  private DB $db;
  protected function getDB(bool $useDB = false) : DB
  {
    if($useDB)
      return clone $this->db;
    else
      return DB::CreateInstance($this->getHandle())->select()->from($this->getTable());
  }
  protected function setDB(DB $db) : static
  {
    $this->db = $db;
    return $this;
  }
  
  private ?array $columns = null;
  protected function getColumns(bool $shift = false) : ?array
  {
    if(empty($this->columns))
      $this->columns  = $this->getHandle()->getColumns($this->getTable());

    $columns = $this->columns;

    if($shift)
      array_shift($columns);

    return $columns;
  }

  private string $idColumn;
  protected function getIdColumn() : string
  {
    return $this->idColumn;
  }

  protected function setIdColumn(?string $column = null) : static
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