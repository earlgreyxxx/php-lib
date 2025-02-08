<?php
/*******************************************************************************

  This class is derivered from PDO(PHP INTERNAL CORE CLASS).
   Aattach database handle(PDO Instance) to Store Object,
     and solution for differences of PDO drivers. 

  This Class is for PDO - SQLite

   ALL WRITTEN BY K,NAKAGAWA.

*******************************************************************************/

class PDOSqlite extends PDOExtension
{
  /*------------------------------------------------------------------------------
    Statics
  ------------------------------------------------------------------------------*/
  protected static $CONDITION = 'WHERE ';
  protected static $DBM = 'sqlite';

  /*------------------------------------------------------------------------------
    Instances
  ------------------------------------------------------------------------------*/
  protected $isInTransaction = false;

  //'EXCLUSIVE'/排他ロック 'IMMEDIATE'/即時ロック 'DEFERRED'/予約ロック
  protected $transactionMode = 'DEFERRED';

  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($dsn,$username='',$password='',$options = array())
  {
    $this->dsn = $dsn;
    parent::__construct($dsn,$username,$password,$options);

    if(version_compare($this->getAttribute(PDO::ATTR_SERVER_VERSION), '3.5.4')<0)
    {
      $this->sqliteCreateAggregate('group_concat',
        function(&$context,$rownumber,$string,$delimiter=',')
        {
          if(isset($string))
          {
            if(isset($context))
              $context .= $delimiter;

            $context .= $string;
          }
          return $context;
        },
        function(&$context,$rownumber)
        {
          return $context;
        });
    }
    if(version_compare($this->getAttribute(PDO::ATTR_SERVER_VERSION),'3.6.0')<0)
    {
      $this->sqliteCreateFunction('replace',
        function($subject,$search,$replace)
        {
          return str_replace($search,$replace,$subject);
        },
        3);
    }

    if(array_key_exists('transactionMode',$options) && !empty($options['transactionMode']))
      $this->transactionMode = $options['transactionMode'];
  }

  /*------------------------------------------------------------------------------
    Implements
  ------------------------------------------------------------------------------*/
  public function exists($table)
  {
    $rv = false;
    if(false === ($sth = $this->prepare('SELECT COUNT(*) FROM sqlite_master WHERE type=\'table\' and name=?')))
      throw new Exception(_('Failed to connect database'));

    $sth->bindValue(1,$table,PDO::PARAM_STR);
    if($sth->execute())
    {
      if(0 < $sth->fetchColumn())
        $rv = true;

      $sth->closeCursor();
    }
    $sth = null;

    return $rv;
  }

  public function getTables($get_option = 0)
  {
    $rv = false;
    if(false === ($sth = $this->query('SELECT * FROM sqlite_master WHERE type=\'table\'')))
      throw new Exception(_('Failed to connect database'));

    if($get_option & PDOExtension::GET_NAME)
    {
      $rv = array();
      while($row = $sth->fetch(PDO::FETCH_ASSOC))
        $rv[] = $row['name'];
    }
    else
    {
      $rv = $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    return $rv;
  }

  public function getColumns($table)
  {
    $rv = false;

    if(false != ($sth = $this->query(sprintf('PRAGMA table_info(%s)',$table))))
    {
      $columns = array();

      while(false != ($row = $sth->fetch(PDO::FETCH_ASSOC)))
        $columns[] = $row['name'];

      if(count($columns) > 0)
        $rv = $columns;

      $sth->closeCursor();
      $sth = null;
    }

    return $rv;
  }

  // not implement
  public function getInfo()
  {
    return array('table' => array(),'column' => array());
  }

  public function getLastUpdate($table,$fmt = 'Y/m/d')
  {
    $rv = false;
    $file = substr($this->dsn,strlen('sqlite')+1);
    if(file_exists($file))
      $rv = date($fmt,filemtime($file));

    return $rv;
  }

  // クロス・プロセスにおけるトランザクション・チェックは未実装
  public function begin()
  {
    return $this->beginTransaction();
  }

  public function beginTransaction() : bool
  {
    $rv = false;

    if('EXCLUSIVE' === $this->transactionMode || 'IMMEDIATE' === $this->transactionMode)
    {
      if(false !== $this->exec(sprintf('BEGIN %s',$this->transactionMode)))
      {
        $rv = true;
        $this->isInTransaction = 1;
      }
      else
      {
        $this->isInTransaction = 0;
      }
    }
    else
    {
      $rv = parent::beginTransaction();
      $this->isInTransaction = false;
    }

    return $rv;
  }

  public function commit() : bool
  {
    if($this->isInTransaction !== false)
    {
      if($rv = $this->exec('COMMIT'))
        $this->isInTransaction = 0;
    }
    else
    {
      $rv = parent::commit();
    }

    return $rv;
  }

  public function rollBack() : bool
  {
    if($this->isInTransaction !== false)
    {
      if($rv = $this->exec('RALLBACK'))
        $this->isInTransaction = 0;
    }
    else
    {
      $rv = parent::rollBack();
    }

    return $rv;
  }

  public function inTransaction() : bool
  {
    return $this->isInTransaction !== false ? $this->isInTransaction : parent::inTransaction();
  }

  //テーブル生成
  public function createTable($table,$columns)
  {
    $values = array('AUTOINCREMENT' => 'AUTOINCREMENT');

    $sql = sprintf('CREATE TABLE %s(%s)',
      $this->quoteTable($table),
      $this->formatString($columns,$values,','));

    return $this->exec($sql);
  }

  //インデックス作成
  public function createIndex($table,$index,$columns,$unique = false,$grant = 'alter')
  {
    $sql = sprintf('CREATE %s %s ON %s(%s)',
      $unique ? 'UNIQUE INDEX' : 'INDEX',
      $index,
      $this->quoteTable($table),
      implode(',',$columns));

    return $this->exec($sql);
  }

  // 外部キー制約
  public function setForeignKeyConstraint(string $table,?array $params = null)
  {
    throw new RuntimeException(_('not implement'));
  }

  public function groupconcat($column)
  {
    return sprintf("';'||group_concat(%s,';')||';'",$column);
  }

  //カラム連結
  public function concat($columns,$as = '')
  {
    $rv = implode(' || ',$this->quoteColumns($columns));
    if(!empty($as))
      $rv = $rv . " AS $as";

    return $rv;
  }

  // drop tables
  public function drops(array $tables)
  {
    $pdo = $this;
    $tables = array_filter($tables,function($el) use($pdo) { return $pdo->exists($el); });
    foreach($tables as $table)
      $this->drop($table);
  }

  // Trancate table
  public function truncate(string $table)
  {
    if(!$this->exists($table))
      throw new RuntimeException(_('table not found'));

    $this->exec(sprintf('DELETE FROM %s',$this->quotetable($table)));
    if(!$this->inTransaction())
      $this->exec('VACUUM');
  }
}

