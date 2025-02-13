<?php
/*******************************************************************************
 *
 *  Base class for a row
 *
 *   All written by Kenji, Nakagawa.
 *   許可なく配布もしくは使用はできません。
 *
******************************************************************************/

class DatabaseRow extends DatabaseTable
{
  // Instances
  // -----------------------------------------------------------------------
  // constructor
  public function __construct(PDOExtension $pdo,$table,$init = null,$init_is_hash = false)
  {
    parent::__construct($pdo,$table);
    $this->setIdColumn();

    $this->setDB(
      DB::CreateInstance($this->getHandle())->select()->from($this->getTable())
    );

    if(is_array($init) || $init instanceof stdClass)
      $this->set($init,$init_is_hash);
    else if(!empty($init))
      $this->initialize($init);
    else
      $this->createEmptyInstance();
  }

  protected function createEmptyInstance() : void
  {
    $columns = $this->getColumns();
    array_shift($columns);

    $obj = new StdClass;
    foreach($columns as $column)
      $obj->$column = null;

    $this->set($obj);
  }

  protected function initialize($init) : void
  {
    $this->load($init);
  }

  // table row
  protected $row;
  public function get()
  {
    return $this->row;
  }

  public function set($obj,$array_is_hash = false)
  {
    if(!is_array($obj) && !($obj instanceof stdClass))
      throw new RuntimeException(_('parameter 1st is invalid type'));

    if(is_array($obj))
    {
      $pdo = $this->getHandle();
      $columns = $this->getColumns();

      if($array_is_hash === true)
      {
        foreach($columns as $column)
          if(!array_key_exists($column,$obj))
            $obj[$column] = null;
      }
      else
      {
        if(count($columns) == count($obj))
        {
          $obj = array_combine($columns,$obj);
        }
        else if(count($columns) - count($obj) == 1)
        {
          $obj = array_combine(array_slice($columns,1),$obj);
          $obj[$columns[0]] = null;
        }
        else
        {
          throw new RuntimeException(_('parameter 1st is invalid type'));
        }
      }

      $obj = (object)$obj;
    }
    else if($obj instanceof stdClass)
    {
      if(false === ($obj = $this->validation($obj)))
        throw new RuntimeException(_('parameter 1st is invalid value'));
    }

    $this->row = $obj;
    return $this;
  }

  protected $exists = false;

  public function exists($set = null)
  {
    if(is_bool($set))
    {
      $this->exists = $set;
      return $this;
    }

    return $this->exists;
  }

  public function chkExists($isSet = false) : bool
  {
    $table = $this->getTable();
    $obj = $this->get();
    $idColumn = $this->getIdColumn();
    $rv = false;
    if(isset($obj->{$idColumn}))
    {
      $pdo = $this->getHandle();
      $rv = 0 < DB::Count($pdo,$table,$idColumn,[$idColumn => $obj->{$idColumn}]);
    }

    if($isSet === true)
      $this->exists($rv);

    return $rv;
  }

  protected function validation(stdClass $obj)
  {
    $pdo = $this->getHandle();
    $table = $this->getTable();
    $rv = false;

    $columns = $this->getColumns();
    array_shift($columns);

    foreach($columns as $column)
    {
      if(!property_exists($obj,$column))
        return $rv;
    }
    return $obj;
  }

  protected function insert() : string|false
  {
    $pdo = $this->getHandle();
    $table = $this->getTable();
    $columns = $this->getColumns(); 
    array_shift($columns);
    $values = array();
    foreach($columns as $i => $column)
      $values[$i] = $this->{$column};
    $placeholders = array_fill(0,count($values),'?');

    $db = 
      DB::CreateInstance($pdo)
        ->into($table)
        ->columns($columns)
        ->values($placeholders)
        ->insert();

    $rv = false;
    if(false !== ($sth = $db->prepare()) && DB::bindValues($sth,$values))
      $rv = $sth->execute();

    if($rv === false)
    {
      DB::SetErrorInfo($sth->errorInfo());
      DB::SetErrorInfo($db->getQuery());
      throw new RuntimeException(_('Database access failed'));
    }

    return $pdo->lastInsertId();
  }

  protected function update(?array $columns = null) : bool
  {
    $pdo = $this->getHandle();
    $table = $this->getTable();

    $def_columns = $this->getColumns();
    $idname = $this->getIdColumn();

    if(empty($this->{$idname}) || !$this->exists())
      throw new RuntimeException(_('update requires ID value and exist record.'));

    $isEmpty = false;
    if(empty($columns))
    {
      $isEmpty = true;
      $columns = $def_columns;
    }

    $values = [];
    $column_placeholder = [];
    foreach($columns as $column)
    {
      // check column name
      if($isEmpty && false === array_search($column,$def_columns))
        throw new RuntimeException(_('specified column is not defined in table'));

      $values[] = $this->{$column};
      $column_placeholder[$column] = '?';
    }
    $db =
      DB::CreateInstance($pdo)
        ->update()
        ->table($table)
        ->sets($column_placeholder)
        ->where([$idname => $this->{$idname}]);

    $rv = false;
    if(false !== ($sth = $db->prepare()) && DB::bindValues($sth,$values))
      $rv = $sth->execute();

    if($rv === false)
    {
      DB::setErrorInfo(print_r($sth->errorInfo(),true));
      DB::setErrorInfo($db->getQuery());
      throw new RuntimeException(_('Database access failed'));
    }
    
    return $rv;
  }

  protected function _imp_load_from_pk(int $id) : bool
  {
    return $this->_imp_load_from_uniq($this->getIdColumn(),$id);
  }

  protected function _imp_load_from_uniq(string $uniq_columnname,mixed $value) : bool
  {
    $pdo = $this->getHandle();
    
    if(is_int($value))
    {
      $fmt = '%s = %d';
      $columnvalue = $value;
    }
    else if(is_string($value))
    {
      $fmt = '%s = %s';
      $columnvalue = $pdo->quote($value);
    }
    else
    {
      throw new RuntimeException(_('invalid argument type'));
    }

    $db = $this->getDB()->where(sprintf($fmt,$pdo->quoteColumns($uniq_columnname),$columnvalue));

    if(false === ($sth = $db->select()->query()))
    {
      DB::SetErrorInfo($pdo->errorInfo());
      DB::SetErrorInfo($db->getQuery());
      throw new RuntimeException(_('Database access failed'));
    }

    $rv = false === ($row = $sth->fetch(PDO::FETCH_OBJ)) ? false : true;
    
    $sth->closeCursor();

    if(!$rv)
      throw new RuntimeException(_('specified value is not exists'));

    $this->row = $row;
    $this->exists($rv);

    return $rv;
  }

  protected function _imp_load_with_conditions(array $cv) : bool
  {
    $pdo = $this->getHandle();
    $db = $this->getDB();
    
    foreach($cv as $columnname => $value)
    {
      if(is_int($value))
      {
        $fmt = '%s = %d';
        $columnvalue = $value;
      }
      else if(is_string($value))
      {
        $fmt = '%s = %s';
        $columnvalue = $pdo->quote($value);
      }
      else
      {
        throw new RuntimeException(_('invalid argument type'));
      }

      $db->where(
        sprintf(
          $fmt,
          $pdo->quoteColumns($columnname),$columnvalue
        )
      );
    }

    if(false === ($sth = $db->select()->query()))
    {
      DB::SetErrorInfo($pdo->errorInfo());
      DB::SetErrorInfo($db->getQuery());
      throw new RuntimeException(_('Database access failed'));
    }

    $rv = false === ($row = $sth->fetch(PDO::FETCH_OBJ)) ? false : true;
    
    $sth->closeCursor();

    if(!$rv)
      throw new RuntimeException(_('specified value is not exists'));

    $this->row = $row;
    $this->exists($rv);

    return $rv;
  }

  // fetch from db
  public function fetch(array $conditions) : bool
  {
    return $this->_imp_load_with_conditions($conditions);
  }

  // load from db
  // if already exists $this->row , return it.
  public function load($uniq_column_value) : bool
  {
    if($this->exists())
      throw new RuntimeException(_('already loaded'));
    
    return $this->_imp_load_from_pk($uniq_column_value);
  }

  // update to db
  public function save(?array $columns = null) : int|bool
  {
    $id = $this->getIdColumn();
    if($columns === null)
      $columns = array_keys($this->columnChanged);

    if(empty($this->row->$id))
    {
      if(false !== ($rv = $this->insert()))
      {
        $this->row->$id = $rv;
        $this->exists(true);
      }
    }
    else
    { 
      $rv = $this->update($columns);
    }

    // clear
    $this->columnChanged = [];

    return $rv;
  }

  public function delete() : bool
  {
    $pdo = $this->getHandle();
    $table = $this->getTable();
    $columns = $this->getColumns();
    $idname = $this->getIdColumn();

    if(empty($this->{$idname}) || !$this->exists())
      throw new RuntimeException(_('delete requires ID value and exist record.'));

    $sth =
      DB::CreateInstance($pdo)
        ->delete()
        ->from($table)
        ->where([$idname => '?'])
        ->prepare();

    $rv = false;
    if($sth !== false && $sth->bindValue(1,$this->{$idname},PDO::PARAM_INT))
      $rv = $sth->execute();

    if($rv === true)
    {
      $this->exists(false);
      $this->{$idname} = null;
      $this->columnChanged = [];
    }
    
    return $rv;
  }

  public function fill(string $uniq_column,mixed $value) : bool
  {
    if(empty($uniq_column) || empty($value))
      return false;

    return $this->_imp_load_from_uniq($uniq_column,$value);
  }

  public function toFormData() : array|false
  {
    $obj = $this->get();
    if(false !== $obj)
      $obj = (array)$obj;

    return $obj;
  }

  public function toJson($callback = '') : string
  {
    $obj = $this->get();
    $rv = json_encode((array)$obj,JSON_PRETTY_PRINT);
    return empty($callback) ? $rv : sprintf('%s(%s);',$callback,$rv); 
  }

  // implemet __get/__set/__isset/__unset
  public function __get($name) : mixed
  {
    $obj = $this->get();
    return $obj->{$name};
  }

  protected $columnChanged = [];
  public function __set($name,$value) : void
  {
    $obj = $this->get();
    if(property_exists($obj,$name))
    {
      $obj->{$name} = $value;

      if(array_key_exists($name,$this->columnChanged))
        $this->columnChanged[$name]++;
      else
        $this->columnChanged[$name] = 1;
    }
    else
    {
      DB::SetErrorInfo($name);
      throw new RuntimeException(_('undefined property in stdObject'));
    }
  }

  public function __isset($name) : bool
  {
    $obj = $this->get();
    return property_exists($obj,$name) && $obj->{$name} !== NULL;
  }

  public function __unset($name) : void
  {
    throw new RuntimeException(_('can not unset property'));
  }

}
