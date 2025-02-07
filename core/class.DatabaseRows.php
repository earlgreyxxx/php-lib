<?php
/*******************************************************************************
 *
 *  Base class for database rows
 *
 *   All written by Kenji, Nakagawa.
 *   許可なく配布もしくは使用はできません。
 *
******************************************************************************/

class DatabaseRows extends DatabaseTable implements Iterator,ArrayAccess,Countable
{
  // Statics
  // -----------------------------------------------------------------------
  public static function CreateWithDSN($table,$dsn,$user = '',$passwd = '',array $options = [])
  {
    $pdoex = GetPdoInstance($dsn,$user,$passwd,$options);
    return new self($pdoex,$table);
  }

  // Instances
  // -----------------------------------------------------------------------

  // constructor
  // settings = ['row-class' => row class factory closure, 'condition' => select condition ]
  public function __construct($pdo,$table,array $settings = [])
  {
    parent::__construct($pdo,$table);

    if(isset($settings['row-class']))
      $this->setRowClass($settings['row-class']);

    if(isset($settings['condition']))
      $this->setCondition($settings['condition']);

    $this->setIdColumn($settings['id-column'] ?? null);

    $this->initialize();
  }

  function __destruct()
  {
    if(isset($this->sth) && ($this->sth instanceof PDOStatement))
    {
      $this->sth->closeCursor();
      $this->sth = null;
    }
  }

  protected function initialize()
  {
    $this->setDB(
      DB::CreateInstance($this->getHandle())->select()->from($this->getTable())
    );
  }

  private $condition;
  public function getCondition()
  {
    return $this->condition;
  }
  public function setCondition($condition)
  {
    $this->condition = $condition;
    return $this;
  }

  private $rowClass = null;
  protected function getRowClass()
  {
    if(!$this->rowClass)
      $this->rowClass = function($param) {};

    return $this->rowClass;
  }
  protected function setRowClass(callable $makeInstance)
  {
    $this->rowClass = $makeInstance;
    return $this;
  }

  private $sth;
  protected function getStatementHandle()
  {
    return $this->sth === null ? false : $this->sth;
  }
  protected function setStatementHandle(PDOStatement $sth)
  {
    $this->sth = $sth;
    return $this;
  }
  protected function clearStatementHandle()
  {
    if($this->sth)
    {
      $this->sth->closeCursor();
      $this->sth = null;
    }

    return $this;
  }

  //
  protected $row;
  private $returnStdObject = true;
  public function setReturnStdObject()
  {
    $this->returnStdObject = true;
    return $this;
  }
  public function setReturnRowObject()
  {
    $this->returnStdObject = false;
    return $this;
  }

  // select => generator
  protected function getEnumerator($conditions = null,int $limit = 0,int $offset = 0,?array $orderbies = null,?array $columns = null,$quotedColumn = false) : Generator
  {
    return $this->createGenerator(true,$conditions, $limit,$offset,$orderbies,$columns,$quotedColumn);
  }

  protected function getGenerator($conditions = null,int $limit = 0,int $offset = 0,?array $orderbies = null,?array $columns = null,$quotedColumn = false) : Generator
  {
    return $this->createGenerator(false,$conditions, $limit,$offset,$orderbies,$columns,$quotedColumn);
  }

  private function createGenerator(bool $useDB,mixed $conditions,int $limit,int $offset,?array $orderbies,?array $columns,bool $quotedColumn) : Generator
  {
    $pdoex = $this->getHandle();
    $db = $this->getDB($useDB);
    if(!empty($columns))
      $db->columns($columns,$quotedColumn);

    if($limit > 0)
      $db->limit($limit,$offset);

    if(!empty($orderbies))
      foreach($orderbies as $orderby)
        $db->orderby($orderby);

    if(!empty($conditions))
    {
      if(is_string($conditions))
        $conditions = [$conditions];
      if(!is_array($conditions))
        throw new RuntimeException(_('argument type error'));

      foreach($conditions as $condition)
      {
        if (!empty($condition))
          $db->where($condition);
      }
    }

    if(false === ($sth = $db->query()))
    {
      DB::SetErrorInfo($pdoex->errorInfo());
      DB::SetErrorInfo($db->getQuery());
      throw new RuntimeException(_('Database access failed'));
    }

    $type = $this->returnStdObject ? PDO::FETCH_OBJ : PDO::FETCH_ASSOC;
    while(false !== ($rv = $sth->fetch($type)))
      yield $rv;

    $sth->closeCursor();
    $sth = null;
    $db = null;
  }

  // returns generator of all rows
  public function rows()
  {
    return $this->getGenerator();
  }

  /***************************************************************
   *
   * implement ArrayAccess interface
   *
   * abstract public offsetExists ( mixed $offset ) : bool
   * abstract public offsetGet ( mixed $offset ) : mixed
   * abstract public offsetSet ( mixed $offset , mixed $value ) : void
   * abstract public offsetUnset ( mixed $offset ) : void
  ***************************************************************/
  #[\ReturnTypeWillChange]
  public function offsetExists($offset)
  {
    $pdo = $this->getHandle();
    $condition = sprintf(
      '%s = %s',
      $pdo->quoteColumns($this->getIdColumn()),
      is_int($offset) ? $offset : $pdo->quote($offset)
    );
    return 0 < DB::Count($pdo,$this->getTable(),'*',$condtion);
  }

  #[\ReturnTypeWillChange]
  public function offsetGet($offset)
  {
    $pdo = $this->getHandle();
    $rv = false;

    $condition = sprintf(
      '%s = %s',
      $pdo->quoteColumns($this->getIdColumn()),
      is_int($offset) ? $offset : $pdo->quote($offset)
    );

    $sth = DB::CreateInstance($pdo)->select()->from($this->getTable())->where($condition)->query();
    $rv = $sth->fetch(PDO::FETCH_OBJ);
    $sth->closeCursor();
    $sth = null;

    if(!$this->returnStdObject)
    {
      $makeInstance = $this->getRowClass();
      $rv = call_user_func($makeInstance,$rv);
    }

    return $rv;
  }
  
  #[\ReturnTypeWillChange]
  public function offsetSet($offset,$value)
  {
    throw new RuntimeException(_('can not set to this object'));
  }

  #[\ReturnTypeWillChange]
  public function offsetUnset($offset)
  {
    $row = $this->offsetGet($offset);
    $row->delete();
  }

  /***************************************************************
   *
   * implement Iterator interface
   *
   * abstract public current ( void ) : mixed
   * abstract public key ( void ) : scalar
   * abstract public next ( void ) : void
   * abstract public rewind ( void ) : void
   * abstract public valid ( void ) : bool
  ***************************************************************/
  public function rewind() : void
  {
    $db = $this->getDB();
    if($condition = $this->getCondition())
      $db->where($condition);

    $sth = $db->query();
    $this->setStatementHandle($sth);

    $this->row = $sth->fetch(PDO::FETCH_OBJ);
  }
  public function valid() : bool
  {
    $rv = $this->row ? true : false;
    if($rv === false)
      $this->clearStatementHandle();

    return $rv;
  }

  #[\ReturnTypeWillChange]
  public function current()
  {
    if($this->returnStdObject)
      return $this->row;
    
    $makeInstance = $this->getRowClass();
    return call_user_func($makeInstance,$this->row);
  }

  #[\ReturnTypeWillChange]
  public function key()
  {
    $idColumn = $this->getIdColumn();
    return $this->row->$idColumn;
  }

  public function next() : void
  {
    $this->row = false === ($sth = $this->getStatementHandle()) ? null : $sth->fetch(PDO::FETCH_OBJ);
  }

  /***************************************************************
   *
   * implement Countable interface
   *
   * abstract public count ( void ) : int
  ***************************************************************/
  public function count() : int
  {
    $pdo = $this->getHandle();
    $condition = $this->getCondition();
    $db = 
      $this->getDB()
        ->columns(sprintf('COUNT(%s)',$pdo->quoteColumns($this->getIdColumn())),true)
        ->where($condition);

    if(false === ($sth = $db->query()))
    {
      DB::SetErrorInfo($pdo->errorInfo());
      DB::SetErrorInfo($db->getQuery());
      throw new RuntimeException(_('Database access failed'));
    }

    if(false === ($rv = $sth->fetchColumn()))
    {
      DB::SetErrorInfo($sth->errorInfo());
      throw new RuntimeException(_('Database access failed'));
    }

    $sth->closeCursor();
    $sth = null;

    return $rv;
  }
}
