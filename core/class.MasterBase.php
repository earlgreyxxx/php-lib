<?php
/*******************************************************************************

  Master table management base class... fetch and store
    assume that table has column ( id,name,value0,value1,value2,..... )

    - Constructor parameters ($dsn,$serializeType)

    - Usage :
       $master = new MasterBase(GetPdoInstance('sqlite:./zipcode.sqlite'),'zipcode');

    - filters
    master-select-after-table master-select-after-query

  All Written by K.,Nakagawa.
*******************************************************************************/

class MasterBase
{
  // Static members
  public static function Prepare($pdo,$tablename,array $column_definition)
  {
    return $pdo->createTable($tablename,$column_definition);
  }

  public static function GetInstance($tablename,$dsn,$user = '',$password = '', array $options = array())
  {
    static $instances = array();
    if($dsn instanceof PDOExtension)
    {
      $pdo = $dsn;
      $key = spl_object_hash($dsn) . '-' . $tablename;
    }
    else
    {
      $pdo = GetPdoInstance($dsn,$user,$password,$options);
      $key = $dsn . '-' . $tablename;
    }

    if(array_key_exists($key,$instances))
      $instance = $instances[$key];
    else
      $instance = $instances[$key] = new static($pdo,$tablename);

    return $instance;
  } 

  // Instance members
  protected $dbh;
  protected $columns;
  protected $table;
  protected $filter;
  protected $action;

  protected function getHandle()
  {
    return $this->dbh;
  }

  protected function getTable()
  {
    return $this->table;
  }

  protected function getColumns()
  {
    return $this->columns;
  }

  // $columns : comma separated column name to select
  protected function selector($columns = '*')
  {
    $pdo = $this->getHandle();
    if(empty($columns))
      $columns = '*';

    if($columns !== '*')
    {
      if(is_string($columns))
        $columns = str_split(',',$columns);

      $columns = implode(',',$pdo->quoteColumns($columns));
    }
    $filter = $this->filter;
    $afterTable = add_space_ifnot_empty($filter->fire('select-after-table',''));
    $afterQuery = add_space_ifnot_empty($filter->fire('select-after-query',''));

    $sql = sprintf(
      'SELECT %s FROM %s%s%s',
      $columns,
      $pdo->quoteTable($this->getTable()),
      $afterTable,
      $afterQuery
    );
    if(false === ($sth = $pdo->query($sql)))
      throw new Exception('Can not access database');

    $rv = $sth->fetchAll(PDO::FETCH_ASSOC);
    $sth->closeCursor();
    $sth = null;

    $this->action->fire('select-done');
    return $rv;
  }

  protected function forceInserter(array $cv)
  { 
    $pdo = $this->getHandle();
    if(!method_exists($pdo,'setInsertId'))
      throw new Exception(_('require PDOExtension::setInsertId'));

    $pdo->setInsertId('on');
    $rv = $this->inserter($cv);
    $pdo->setInsertId('off');

    return $rv;
  }

  // returns insert id
  protected function inserter(array $cv)
  {
    $pdo = $this->getHandle();
    $columns = array();
    $values = array();
    foreach($this->getColumns() as $column)
    {
      if(isset($cv[$column]))
      {
        $columns[] = $pdo->quoteColumns($column);
        $values[] = $cv[$column];
      }
    }
    $placeholders = array_fill(0,count($columns),'?');

    $sql = sprintf('INSERT INTO %s (%s) VALUES(%s)',
                    $pdo->quoteTable($this->getTable()),
                    implode(',',$columns),
                    implode(',',$placeholders));

    if(false === ($sth = $pdo->prepare($sql)))
      throw new Exception(_('DB error'));

    $len = count($columns);
    for($i=0;$i<$len;$i++)
    {
      $type = PDO::PARAM_STR;
      if(is_int($values[$i]))
        $type = PDO::PARAM_INT;
      else if(is_null($values[$i]))
        $type = PDO::PARAM_NULL;

      $sth->bindValue($i+1,$values[$i],$type);
    }

    $rv = $sth->execute();
    $this->action->fire('insert-done');

    if($rv)
    {
      $rv = $pdo->lastInsertId();
    }
    else
    {
      $err = $sth->errorInfo();
      throw new RuntimeException($err[1].':'.$err[2]);
    }

    $sth = null;
    return $rv;
  }

  protected function updater($id,array $cv)
  {
    $pdo = $this->getHandle();
    $sets = array();
    foreach(array_keys($cv) as $column)
      $sets[] = sprintf('%s = ?',$pdo->quoteColumns($column));

    $filter = $this->filter;
    $afterTable = add_space_ifnot_empty($filter->fire('update-after-table',''));
    $afterQuery = add_space_ifnot_empty($filter->fire('update-after-query',''));

    $tableColumns = $this->getColumns();
    $sql = sprintf(
      'UPDATE %s%s SET %s WHERE %s = ?%s',
      $pdo->quoteTable($this->getTable()),
      $afterTable,
      implode(',',$sets),
      $pdo->quoteColumns($this->getColumns()[0]),
      $afterQuery
    );
    if(false === ($sth = $pdo->prepare($sql)))
      throw new Exception('Failed to UPDATE');

    $columns = array_keys($cv);
    $count = count($columns);
    for($i=0;$i<$count;$i++)
    {
      $type = PDO::PARAM_STR;
      if(is_int($cv[$columns[$i]]))
        $type = PDO::PARAM_INT;
      else if(is_null($cv[$columns[$i]]))
        $type = PDO::PARAM_NULL;

      $sth->bindValue($i+1,$cv[$columns[$i]],$type);
    }
    $sth->bindValue($i+1,$id,PDO::PARAM_INT);

    if(false === ($rv = $sth->execute()))
    {
      $err = $sth->errorInfo();
      throw new RuntimeException($err[1].':'.$err[2]);
    }

    $sth = null;
    $this->action->fire('update-done');

    return $rv;
  }

  protected function deleter($id)
  {
    $pdo = $this->getHandle();
    $tableColumns = $this->getColumns();
    $sql = sprintf(
      'DELETE FROM %s WHERE %s = ?',
      $pdo->quoteTable($this->getTable()),
      $pdo->quoteColumns($tableColumns[0])
    );
    if(false === ($sth = $pdo->prepare($sql)))
    {
      $err = $sth->errorInfo();
      throw new RuntimeException($err[1].':'.$err[2]);
    }

    $sth->bindValue(1,$id,PDO::PARAM_INT);
    if(false === ($rv = $sth->execute()))
    {
      $err = $sth->errorInfo();
      throw new RuntimeException($err[1].':'.$err[2]);
    }

    $sth = null;

    $this->action->fire('delete-done');
    return $rv;
  }

  public function __construct(PDO $pdo,$tablename,array $options =  array())
  {
    $this->dbh = $pdo;
    if(empty($tablename))
      throw new Exception(_('table name was empty.'));

    if(!$pdo->exists($tablename))
      throw new Exception(sprintf(_('Table not found: %s'),$tablename));
    
    $this->columns = $pdo->getColumns($tablename);
    $this->table = $tablename;
    $this->filter = null;
    $this->action = null;
    if((($existsFilter = array_key_exists('filter',$options)) && false === $this->attachFilter($options['filter'])) || !$existsFilter)
      $this->filter = new Filter();
    if((($existsAction = array_key_exists('action',$options)) && false === $this->attachAction($options['action'])) || !$existsAction)
      $this->action = new Action();

    if(method_exists($this,'init'))
      $this->init();
  }

  public function __destruct()
  {
    $this->dbh = null;
  }

  public function begin()
  {
    $this->getHandle()->beginTransaction();
  }

  public function commit()
  {
    $this->getHandle()->commit();
  }

  public function rollBack()
  {
    $this->getHandle()->rollBack();
  }

  public function attachFilter($filter)
  {
    $rv = false;
    if($filter instanceof Filter)
    {
      $rv = $this->filter;
      $this->filter = $filter;
    }
    return $rv;
  }
  public function attachAction($action)
  {
    $rv = false;
    if($filter instanceof Action)
    {
      $rv = $this->action;
      $this->action = $action;
    }
    return $rv;
  }
}

