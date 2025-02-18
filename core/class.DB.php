<?php
/*******************************************************************************

  Database query builder and invoke ...
    fetch, insert, delete, and update

    - filters
      select-after-table
      select-after-query
      insert-after-query
      update-after-query
      delete-after-query

    - statics 
  // public static function Union(PDOExtension $pdo,array $dbs,$hasAll = false)
  // public static function CreateInstance($pdo,array $options = array())
  // public static function bindValues(PDOStatement $sth,array $values)
  // public static function Count(PDOExtension $pdo,$table,$column = null,$conditions = null,$is_prepare = false)


  //   - Constructor parameters (PDO $pdo)
  // public function __construct(PDOExtension $pdo,array $options = array())

  // protected function _insert()
  // protected function _update()
  // protected function _delete()
  // protected function _select()
  // protected function _joinWithId($table,$column1,$column2,$which = 'INNER')
  // protected function _joinOnSubQuery($subquery,$alias,$condition,$which = 'INNER')
  // protected function _join($table,$condition,$which = 'INNER')
  // private function imp_isNull($column,$is_not = false,$is_quoted = false,$operator = 'AND')
  // protected function prependColumn($column,$is_quoted = false)
  // protected function appendColumn($column,$is_quoted = false)

  // common 
  // public function attachFilter($filter)
  // public function getFilter()
  // public function getQuery()
  // public function query()
  // public function queryAndFetchAll($method = PDO::FETCH_BOTH)
  // public function prepare()

  // sql common
  // public function table($table,$is_quoted = false)  << alias for table
  // public function from($table,$alias = '',$is_quoted = false)
  // public function fromAs(DB $db,$alias) 
  // public function columnsAs(array $columns)
  // public function columns($columns = '*',$is_quoted = false)

  // conditions
  //   $cv => [column1 => value2, ... ] or [column1 => array(operator,value,column and value is quoted?,value_quoted,prefix_logical_op),....]
  // public function wheres(array $cv,$operator = 'AND',$is_placeholder = false,$is_quoted = false)
  // public function where($cond,$operator = 'AND')
  // public function in(array $elments)
  // public function isNotNull($column,$is_quoted = false,$operator = 'AND')
  // public function isNull($column,$is_quoted = false,$operator = 'AND')

  // deleter
  // public function delete()

  // updater
  // public function update()
  // public function sets($columnvalue,$is_column_quoted = false)
  // public function set($column,$value,$is_column_quoted = false)
  // public function updateTable($table,$is_quoted = false)
  //   
  // inserter
  // public function insert()
  // public function values(string|array $values,bool $validate = false)
  // public function into($table,$is_quoted = false)
 
  // selector
  // public function select()

  // pagination
  // public function slice($offset,$num = 0)
  // public function limit($num,$offset)

  // aggrigation
  // public function distinct()
  // public function having($cond,$operator = 'AND')
  // public function groupby($groupby)
  // public function orderby($orderby,$sqlsrv_num = null,$sqlsrv_offset = null)

  // table join
  // public function rightJoin($table,$column1,$column2)
  // public function leftJoin($table,$column1,$column2)
  // public function outerJoin($table,$column1,$column2)
  // public function innerJoin($table,$column1,$column2)
  // public function join($table,$condition,$type = 'INNER')
  // public function joinAs(DB $db,$alias,$condition,$type = 'INNER')
  // public function top($num);

  // public function exec($prcedure_name, ...)
    

    - Usage :
       $pdoex = GetPdoInstance($dsn,$dbuser,$dbpass,$options);
       $db = new DB($pdoex,['sqlserver2008' => true]);


  All Written by K.,Nakagawa.
*******************************************************************************/

class DB
{
  /*-----------------------------------------------------------------------
    statics
  -----------------------------------------------------------------------*/
  public static bool $SQLSERVER_IS_2008 = false;

  private static array $Errors = [];
  public static function SetErrorInfo($mixed) : void
  {
    self::$Errors[] = $mixed;
  }
  public static function ErrorInfo() : array
  {
    return self::$Errors;
  }

  // Factory
  public static function CreateInstance(PDOExtension $pdo,array $options = []) : DB
  {
    return new static($pdo,$options);
  }

  // returns PDOStatement instance
  public static function Union(PDOExtension $pdo,array $dbs,bool $hasAll = false,?array $addtions = null) : bool|PDOStatement
  {
    $rv = false;
    $queries = array();
    foreach($dbs as $db)
    {
      if($db instanceof DB)
      {
        $query = trim($db->getQuery());
        if(!empty($query))
          $queries[] = $query;
      }
    }
    if(!empty($queries))
    {
      $all = $hasAll ? 'ALL ' : '';
      $rv = implode(sprintf(' UNION %s',$all),$queries);
      if(!empty($addtions))
        $rv .= sprintf(' %s',implode(' ',$addtions));

      $rv = $pdo->query($rv);
    }

    return $rv;
  }

  public static function bindValues(PDOStatement $sth,array $values) : bool|PDOStatement
  {
    foreach($values as $i => $v)
    {
      if(is_array($v))
        list($v,$type) = $v;
      else if(is_int($v))
        $type = PDO::PARAM_INT;
      else if(is_null($v))
        $type = PDO::PARAM_NULL;
      else
        $type = PDO::PARAM_STR;

      $placeholder = is_int($i) || is_numeric($i) ? ($i + 1) : $i;
      if(false === $sth->bindValue($placeholder,$v,$type))
        return false;
    }
    return $sth;
  }

  // helper for COUNT($column) returns integer or PDOStatement instance when is_prepared was true
  // $conditions is array or string, pass to where method directly
  public static function Count(PDOExtension $pdo,string $table,string $count_column = '*',string|array|null $conditions = null,bool $is_prepared = false) : mixed
  {
    if(!$pdo->exists($table))
      throw new RuntimeException(_('table not exists'));

    $db = self::CreateInstance($pdo);

    $count_column = $count_column === '*' ? 'COUNT(*)' : sprintf('COUNT(%s)',$pdo->quoteColumns($count_column));
    $db->columns($count_column,true)->from($table);
    if(!empty($conditions))
      $db->where($conditions);

    // set select mode
    $db->select();

    if($is_prepared === true)
      return $db->prepare();

    if(false === ($sth = $db->query()))
      throw new RuntimeException(_('Database access failed'));

    $cnt = $sth->fetchColumn();
    $sth->closeCursor();
    $sth = null;

    return $cnt;
  }

  public static function DistinctCount(PDOExtension $pdo,string $table,string $count_column,string|array|null $conditions = null,bool $is_prepared = false) : mixed
  {
    if(!$pdo->exists($table))
      throw new RuntimeException(_('table not exists'));

    $db = self::CreateInstance($pdo);

    if($count_column === '*')
      throw new RuntimeException(_('can not use "*"'));

    $count_column = sprintf('COUNT(DISTINCT %s)',$pdo->quoteColumns($count_column));
    $db->columns($count_column,true)->from($table);
    if(!empty($conditions))
      $db->where($conditions);

    // set select mode
    $db->select();

    if($is_prepared === true)
      return $db->prepare();

    if(false === ($sth = $db->query()))
      throw new RuntimeException(_('Database access failed'));

    $cnt = intval($sth->fetchColumn());
    $sth->closeCursor();
    $sth = null;

    return $cnt;
  }

  // return id where $column is $value
  public static function GetID(PDOExtension $pdo,string $table,string $column,mixed $value) : mixed
  {
    $sth = 
      DB::CreateInstance($pdo)
        ->select()
        ->from($table)
        ->where(sprintf('%s = %s',$column,is_int($value) ? $value : $pdo->quote($value)))
        ->query();

    if($sth === false)
      throw new RuntimeException(_('Database access failed'));

    $rv = $sth->fetchColumn();
    $sth->closeCursor();
    $sth = null;

    return $rv;
  }

  // get iterator
  // ---------------------------------------------------------------------------
  public static function getIterator(PDOStatement $sth,int $fetchType = PDO::FETCH_ASSOC) : Generator
  {
    while(false !== ($row = $sth->fetch($fetchType)))
      yield $row;

    $sth->closeCursor();
    $sth = null;
  }

  /*-----------------------------------------------------------------------
    Instances
  -----------------------------------------------------------------------*/
  protected PDOExtension $pdo;
  protected array $sql;
  protected Filter $filter;
  protected string $mode = '';
  protected bool $sqlserver2008 = false;

  public function __construct(PDOExtension $pdo,array $options = array())
  {
    $this->pdo = $pdo;
    $this->sql = [];
    if((($existsFilter = array_key_exists('filter',$options)) && false === $this->attachFilter($options['filter'])) || !$existsFilter)
      $this->filter = new Filter();

    if(array_key_exists('sqlserver2008',$options) && $options['sqlserver2008'] === true)
      $this->sqlserver2008 = true;
    else if(is_bool(static::$SQLSERVER_IS_2008))
      $this->sqlserver2008 = static::$SQLSERVER_IS_2008;
  }

  public function quoteColumns(mixed $column) : mixed
  {
    return $this->pdo->quoteColumns($column);
  }
  public function quoteTable(string $table) : string
  {
    return $this->pdo->quoteTable($table);
  }
  public function quote(string $str) : string
  {
    return $this->pdo->quote($str);
  }

  public function getFilter() : Filter
  {
    return $this->filter;
  }

  public function attachFilter(Filter $filter) : Filter|null
  {
    $rv = $this->filter ?? null;
    $this->filter = $filter;

    return $rv;
  }

  //commons
  public function columns(string|array $columns = '*',bool $is_quoted = false) : DB
  {
    if($columns === '*')
    {
      $this->sql['columns'] = $columns;
    }
    else
    {
      if(is_array($columns))
        $columns = implode(',',$is_quoted ? $columns : $this->pdo->quoteColumns($columns));

      $this->sql['columns'] = $columns;
    }

    return $this;
  }

  // $columns =>  [ [column1,alias1],[column2,alias2],.... ] column is not quoted.
  public function columnsAs(array $columns) : DB
  {
    $pdo = $this->pdo;
    $result = array();
    foreach($columns as $column)
      $result[] = is_array($column) && count($column) == 2 ? 
                    sprintf('%s AS %s',$pdo->quoteColumns($column[0]),$column[1]) :
                    $this->pdo->quoteColumns($column[0]);

    $this->sql['columns'] = implode(',',$result);
    return $this;
  }

  protected function appendColumn(string $column,bool $is_quoted = false) : DB
  {
    if(strlen($column) > 0)
    {
      if($is_quoted === false)
        $column = $this->pdo->quoteColumns($column);

      $columns = empty($this->sql['columns']) ? [] : [$this->sql['columns']];
      $columns[] = $column;

      $this->sql['columns'] = implode(',',$columns);
    }

    return $this;
  }
  protected function prependColumn(string $column,bool $is_quoted = false) : DB
  {
    if(strlen($column) > 0)
    {
      if($is_quoted === false)
        $column = $this->pdo->quoteColumns($column);

      $columns = empty($this->sql['columns']) ? [] : [$this->sql['columns']];
      array_unshift($columns,$column);

      $this->sql['columns'] = implode(',',$columns);
    }

    return $this;
  }

  private function _where(string $cond,string $operator = 'AND') : DB
  {
    if(array_key_exists('where',$this->sql) && !empty($this->sql['where']))
      $this->sql['where'] .= sprintf(' %s %s',$operator,$cond);
    else
      $this->sql['where'] = $cond;

    return $this;
  }

  public function where(mixed $cond,string $operator = 'AND') : DB
  {
    if(empty($cond))
      return $this;

    return is_array($cond) ? $this->wheres($cond,$operator) : $this->_where($cond,$operator);
  }

  // many conditions given in once time 
  // $cv => [column1 => value2, ... ] or [column1 => array(op,value,quoted,value_quoted,logical_op),....]
  public function wheres(array $cv,string $operator = 'AND',bool $is_quoted = false,bool $is_value_quoted = false) : DB
  {
    $pdo = $this->pdo;
    foreach($cv as $c => $v)
    {
      if(empty($c) && empty($v))
        continue;

      if(is_array($v))
      {
        if(empty($v))
          throw new RuntimeException(_('array must not be empty'));

        $v = array_merge($v);
        if(6 > ($cv = count($v)))
        {
          for($i_=0;$i_<6;$i_++)
            $v[$i_] = $v[$i_] ?? null;
        }
        list($op,$value,$quoted,$value_quoted,$logical_op) = $v;
        if(!isset($value))
          $value = null;
        if(!isset($quoted))
          $quoted = $is_quoted;
        if(empty($value_quoted))
          $value_quoted = $is_value_quoted;
        if(empty($logical_op) || !preg_match('/^(?:AND|OR)$/i',$logical_op))
          $logical_op = $operator;

        if($value_quoted)
        {
          $cond = sprintf(
            '%s %s %s',
            $quoted ? $c : $pdo->quoteColumns($c),
            $op,
            $value
          );
        }
        else
        {
          if(is_int($value) || (is_string($value) && ($value === '?' || $value[0] === ':')))
            $_value_ = $value;
          else
            $_value_ = $value === null ? '' : $pdo->quote($value);

          $cond = sprintf(
            is_int($value) ? '%s %s %d' : '%s %s %s',
            $quoted ? $c : $pdo->quoteColumns($c),
            $op,
            $_value_
          );
          $cond = trim($cond);
        }
      }
      else
      {
        if($is_value_quoted)
        {
          $cond = sprintf(
            '%s = %s',
            $is_quoted ? $c : $pdo->quoteColumns($c),
            $v
          );
        }
        else
        {
          if($v === '?' || (is_string($v) && ($v[0] ?? '') === ':'))
            $cond = sprintf('%s = %s',$is_quoted ? $c : $pdo->quoteColumns($c),$v);
          else
            $cond = sprintf(
              is_int($v) ? '%s = %d' : '%s = %s',
              $is_quoted ? $c : $pdo->quoteColumns($c),
              is_int($v) ? $v : $pdo->quote($v)
            );
        }
      }
      $this->_where($cond,isset($logical_op) ? $logical_op : $operator);
      unset($op,$value,$quoted,$value_quoted,$logical_op);
    }

    return $this;
  }

  // ope IS NULL or IS NOT NULL
  private function imp_isNull(string $column,bool $is_not = false,bool $is_quoted = false,string $operator = 'AND') : DB
  {
    $cond = sprintf(
      '%s IS %sNULL',
      $is_quoted ? $column : $this->pdo->quoteColumns($column),
      $is_not ? 'NOT ' : ''
    );
    return $this->where($cond,$operator);
  }
  public function isNull(string $column,bool $is_quoted = false,string $operator = 'AND') : DB
  {
    return $this->imp_isNull($column,false,$is_quoted,$operator);
  }
  public function isNotNull(string $column,bool $is_quoted = false,string $operator = 'AND') : DB
  {
    return $this->imp_isNull($column,true,$is_quoted,$operator);
  }

  // op IN(...)
  public function in(string $column,array $elements,bool $is_quoted = false) : DB
  {
    $pdo = $this->pdo;
    if(!$is_quoted)
    {
      $column = $pdo->quoteColumns($column);
      foreach($elements as &$el)
      {
        if(!is_int($el))
          $el = $pdo->quote($el);
      }
    }
    return $this->where(sprintf( '%s IN (%s)', $column, implode(',',$elements)));
  }

  //return statement handle
  public function prepare() : PDOStatement|false
  {
    $rv = false;

    if(!$this->mode)
      throw new Exception(_('query mode is not defined'));
    $invoke = '_' . $this->mode;

    if(method_exists($this,$invoke))
    {
      $sql = call_user_func(array($this,$invoke));
      $rv = $this->pdo->prepare(trim($sql));
    }
    return $rv;
  }

  //return statement handle
  public function query() : PDOStatement|false
  {
    $rv = false;

    if(!$this->mode)
      throw new Exception(_('query mode is not defined'));

    $invoke = '_' . $this->mode;
    if(method_exists($this,$invoke))
    {
      $sql = $this->$invoke();
      $rv = $this->pdo->query(trim($sql));
    }
    return $rv;
  }

  public function exec() : int|false
  {
    $rv = false;

    if(!$this->mode)
      throw new Exception(_('exec mode is not defined'));

    $invoke = '_' . $this->mode;
    if(method_exists($this,$invoke))
    {
      $sql = $this->$invoke();
      $rv = $this->pdo->exec(trim($sql));
    }

    return $rv;
  }

  public function queryAndFetchAll(int $method = PDO::FETCH_BOTH) : array|false
  {
    $rv = false;
    $pdo = $this->pdo;
    if(false !== ($sth = $this->query()))
      $rv = $sth->fetchAll($method);

    return $rv;
  }

  public function getQuery() : bool|string
  {
    $rv = false;
    if(!$this->mode)
      throw new Exception(_('query mode is not defined'));

    $invoke = '_' . $this->mode;
    if(method_exists($this,$invoke))
    {
      $sql = $this->$invoke();
      $rv = trim($sql);
    }

    return $rv;
  }

  // Select
  // ------------------------------------------------------------------
  protected function _join(string $table,mixed $condition,string $which = 'INNER') : DB
  {
    $pdo = $this->pdo;
    if(!array_key_exists('join',$this->sql) || empty($this->sql['join']))
      $this->sql['join'] = array();

    $tables = preg_split('/\s+/',$table,2);
    if(count($tables) == 2)
    {
      list($tablename,$alias) = $tables;
    }
    else
    {
      $tablename = $tables[0];
      $alias = null;
    }
    $tablename = $pdo->quoteTable($tablename);
    if(!empty($alias))
      $alias = ' ' . $pdo->quoteTable($alias);

    if(is_callable($condition))
    {
      $condition = call_user_func($condition);
    }
    else if(is_array($condition))
    {
      $cv = $condition;
      $conditions = array();
      foreach($cv as $c => $v)
      {
        if(is_array($v))
          foreach($v as $v_)
            $conditions[] = is_callable($v_) ? call_user_func($v_,$c) : sprintf('%s = %s',$c,$v_);
        else
          $conditions[] = is_callable($v) ? call_user_func($v,$c) : sprintf('%s = %s',$c,$v);
      }

      $condition = implode(' AND ',$conditions);
    }

    $this->sql['join'][] = 
      sprintf(
        '%s JOIN %s%s ON %s',
        $which,
        $tablename,
        $alias,
        $condition
      );

    return $this;
  }

  protected function _joinOnSubQuery(string $subquery,string $alias,string $condition,string $which = 'INNER') : DB
  {
    $pdo = $this->pdo;
    if(!array_key_exists('join',$this->sql) || empty($this->sql['join']))
      $this->sql['join'] = array();

    $this->sql['join'][] = sprintf(
      '%s JOIN (%s) AS %s ON %s',
      $which,
      $subquery,
      $alias,
      $condition
    );
    return $this;
  }

  protected function _joinWithId(string $table,string $column1,string $column2,string $which = 'INNER') : DB
  {
    $pdo = $this->pdo;
    $condition = sprintf(
      '%s = %s',
      $pdo->quoteColumns($column1),
      $pdo->quoteColumns($column2)
    );

    return $this->_join($table,$condition,$which);
  }

  public function select(?string $columns = null,bool $is_quoted = false) : DB
  {
    $this->mode = 'select';
    if(!is_null($columns) && !empty($columns))
      return $this->columns($columns,$is_quoted);

    return $this;
  }

  protected function _select() : string
  {
    if(!array_key_exists('from',$this->sql) || empty($this->sql['from']))
      throw new Exception(_('table was empty'));

    $statement = array('SELECT');
    if(array_key_exists('top',$this->sql))
      $statement[] = $this->sql['top'];

    if(array_key_exists('distinct',$this->sql))
      $statement[] = $this->sql['distinct'];

    $statement[] = !array_key_exists('columns',$this->sql) || empty($this->sql['columns']) ? '*' : $this->sql['columns'];
    if(empty($this->sql['from']))
      throw new Exception(_('select requires from tables'));
    $statement[] = 'FROM ' . implode(',',$this->sql['from']);

    $statement[] = $this->filter->fire('select-after-table','');

    if(array_key_exists('join',$this->sql) && !empty($this->sql['join']))
      $statement[] = implode(' ',$this->sql['join']);
    if(array_key_exists('where',$this->sql) && !empty($this->sql['where']))
      $statement[] = 'WHERE ' . $this->sql['where'];
    if(array_key_exists('groupby',$this->sql) && !empty($this->sql['groupby']))
      $statement[] = 'GROUP BY '. $this->sql['groupby'];
    if(array_key_exists('having',$this->sql) && !empty($this->sql['having']))
      $statement[] = 'HAVING ' . $this->sql['having'];
    if(array_key_exists('orderby',$this->sql) && !empty($this->sql['orderby']))
    {
      $orderby = 'ORDER BY ' . implode(',',$this->sql['orderby']);
      if(isset($this->sql['fetch-row']))
        $orderby .= $this->sql['fetch-row'];
        
      $statement[] = $orderby;
    }

    if(array_key_exists('limit',$this->sql))
      $statement[] = $this->pdo->limit( $this->sql['limit']['num'], $this->sql['limit']['offset']);

    $statement[] = $this->filter->fire('select-after-query','');

    return implode(' ',$statement);
  }

  public function from(string $table,string $alias = '',bool $is_quoted = false) : DB
  {
    if(!array_key_exists('from',$this->sql))
      $this->sql['from'] = array();

    $from = $is_quoted === false ? $this->pdo->quoteTable($table) : $table;
    if(!empty($alias))
      $from .= sprintf(' %s',$alias);

    $this->sql['from'][] = $from;
    return $this;
  }

  public function fromAs(DB $db,string $alias) : DB
  {
    $query = $db->select()->getQuery();
    if(empty($query))
      throw new Exception(_('DB::select() must not be empty'));
    if(empty($alias))
      throw new Exception(_('$alias is must not be empty'));

    return $this->from(sprintf('(%s)',trim($query)),$alias,true);
  }

  public function joinAs(DB $db,string $alias,string $condition,string $type = 'INNER') : DB
  {
    return $this->_joinOnSubQuery($db->getQuery(),$alias,$condition,$type);
  }

  public function join(string $table,mixed $condition,string $type = 'INNER') : DB
  {
    return $this->_join($table,$condition,$type);
  }
  public function innerJoin(string $table,string $column1,string $column2) : DB
  {
    return $this->_joinWithId($table,$column1,$column2,'INNER');
  }

  public function outerJoin(string $table,string $column1,string $column2) : DB
  {
    return $this->_joinWithId($table,$column1,$column2,'OUTER');
  }

  public function leftJoin(string $table,string $column1,string $column2) : DB
  {
    return $this->_joinWithId($table,$column1,$column2,'LEFT');
  }
  public function rightJoin(string $table,string $column1,string $column2) : DB
  {
    return $this->_joinWithId($table,$column1,$column2,'RIGHT');
  }

  public function orderby(string $orderby,?int $sqlsrv_num = 0,?int $sqlsrv_offset = 0) : DB
  {
    $pdo = $this->pdo;
    if(!array_key_exists('orderby',$this->sql) || !is_array($this->sql['orderby']))
      $this->sql['orderby'] = [];

    if($sqlsrv_offset > 0 && $sqlsrv_num > 0)
      $this->sql['fetch-row'] = sprintf(' OFFSET %d ROWS FETCH NEXT %d ROWS ONLY',$sqlsrv_offset,$sqlsrv_num);
    else if($sqlsrv_num > 0 && empty($sqlsrv_offset))
      $this->sql['fetch-row'] = sprintf(' OFFSET 0 ROWS FETCH NEXT %d ROWS ONLY',$sqlsrv_num);

    $this->sql['orderby'][] = $orderby;
    return $this;
  }

  public function groupby(string $groupby) : DB
  {
    $this->sql['groupby'] = $groupby;
    return $this;
  }

  public function having(string $cond,string $operator = 'AND') : DB
  {
    if(array_key_exists('having',$this->sql) && !empty($this->sql['having']))
      $this->sql['having'] .= sprintf(' %s %s',$operator,$cond);
    else
      $this->sql['having'] = $cond;

    return $this;
  }

  public function distinct() : DB
  {
    $this->sql['distinct'] = 'DISTINCT';
    return $this;
  }

  // SQLServer only
  public function top(int|string $num) : DB
  {
    $dbtype = $this->pdo->getPrefix();
    if($dbtype !== 'sqlsrv' && $dbtype !== 'dblib')
      throw new RuntimeException(_('this method is only SQLServer'));

    if(is_string($num) && !is_numeric($num))
      throw new RuntimeException(_('arugment 1st must be numeric'));

    $this->sql['top'] = sprintf('TOP (%d)',$num);
    return $this;
  }

  public function limit(int $num,int $offset) : DB
  {
    if($num > 0)
      $this->sql['limit'] = ['num' => $num,'offset' => $offset];

    return $this;
  }

  public function slice(int $offset,int $num = 0) : mixed
  {
    if(++$offset <= 0)
      throw new Exception(_('offset is greater than 0'));

    $dbtype = $this->pdo->getPrefix();
    if($this->sqlserver2008 === true && ($dbtype === 'sqlsrv' || $dbtype === 'dblib'))
    {
      if(array_key_exists('limit',$this->sql))
        unset($this->sql['limit']);

      $cond = $num == 0 ? sprintf('RNT.RN >= %d',$offset) : sprintf('RNT.RN BETWEEN %d AND %d',$offset,$offset + $num - 1);
      $pdo = $this->pdo;
      if(empty($this->sql['orderby']))
        throw new Exception(_('call orderby() first.'));

      //remove orderby temporarily.
      $orderby = $this->sql['orderby'];
      unset($this->sql['orderby']);
      
      $rownumber = sprintf('ROW_NUMBER() OVER(ORDER BY %s) AS RN',implode(',',$orderby));
      if(array_key_exists('distinct',$this->sql))
      {
        $dbSub =
          static::CreateInstance($pdo)
            ->fromAs($this,'a')
            ->columns([$rownumber,sprintf('%s.*',$pdo->quoteColumns('a'))],true)
            ->select();

        $rv = 
          static::CreateInstance($pdo)
            ->select()
            ->fromAs($dbSub,'RNT')
            ->where($cond)
            ->query();
      }
      else
      {
        $this->prependColumn($rownumber,true);
        $rv = 
          static::CreateInstance($pdo)
            ->fromAs($this,'RNT')
            ->where($cond)
            ->select()
            ->query();
      }

      //restore orderby
      $this->sql['orderby'] = $orderby;
    }
    else
    {
      $this->limit($num,$offset);
      $rv = $this->select()->query();
    }

    return $rv;
  }

  // Insert
  // ----------------------------------------------------------------------
  public function insert() : DB
  {
    $this->mode = 'insert';
    return $this;
  }

  protected function _insert() : string
  {
    if(!array_key_exists('into',$this->sql) || empty($this->sql['into']))
      throw new Exception(_('table was empty'));

    $statement = array('INSERT');
    $statement[] = sprintf('INTO %s',$this->sql['into']);
    if(!empty($this->sql['columns']))
      $statement[] = sprintf('(%s)',$this->sql['columns']);

    $values = $this->sql['values'];
    if(is_string($values))
    {
      $statement[] = $values;
    }
    else if(is_array($values))
    {
      if (!count($values))
        throw new Exception(_('VALUE is required'));

      $statement[] = 'VALUES';
      $temp = array();
      foreach($this->sql['values'] as $value)
      {
        if(is_array($value))
          $temp[] = sprintf('(%s)',implode(',',$value));
        else if(is_string($value))
          $temp[] = $value;
      }
      $statement[] = implode(',', $temp);
    }

    $statement[] = $this->filter->fire('insert-after-query','');

    return implode(' ',$statement);
  }

  public function into(string $table,bool $is_quoted = false) : DB
  {
    $this->sql['into'] = $is_quoted === false ? $this->pdo->quoteTable($table) : $table;
    return $this;
  }

  public function values(string|array|DB $values,bool $validate = false) : DB
  {
    $isDB = $values instanceof DB;
    if(array_key_exists('values',$this->sql) && is_string($this->sql['values']) && !empty($this->sql['values']))
      throw new RuntimeException(_('can not overwrite DB query'));

    if(!array_key_exists('values',$this->sql) && !$isDB)
      $this->sql['values'] = [];

    if($isDB)
      return $this->queryValues($values);

    if(!empty($values))
    {
      if($validate && is_array($values))
      {
        $pdo = $this->pdo;
        foreach($values as &$v)
        {
          if(is_string($v))
            $v = $pdo->quote($v);
          else if(is_null($v))
            $v = 'null';
          else if(is_bool($v))
            $v = intval($v);
        }
      }

      $this->sql['values'][] = $values;
    }

    return $this;
  }

  // insert values are sql select query.
  protected function queryValues(string|DB $values) : DB
  {
    $this->sql['values'] = ($values instanceof DB) ? DB::getQuery() : $values;
    return $this;
  }

  // Update
  // ----------------------------------------------------------------------
  public function update() : DB
  {
    $this->mode = 'update';
    return $this;
  }

  protected function _update() : string
  {
    $statement = array('UPDATE');
    if(!array_key_exists('table',$this->sql))
      throw new Exception(_('update requires table parameter'));

    $statement[] = $this->sql['table'];

    $cv = array();
    foreach($this->sql['set'] as $c_ => $v_)
      $cv[] = sprintf('%s = %s',$c_,$v_);

    $statement[] = 'SET ' . implode(',',$cv);
    if(array_key_exists('where',$this->sql) && !empty($this->sql['where']))
      $statement[] = 'WHERE ' . $this->sql['where'];

    $statement[] = $this->filter->fire('update-after-query','');

    return implode(' ',$statement);
  }

  public function table(string $table,bool $is_quoted = false) : DB
  {
    return $this->updateTable($table,$is_quoted);
  }
  public function updateTable(string $table,bool $is_quoted = false) : DB
  {
    $this->sql['table'] = $is_quoted === false ? $this->pdo->quoteTable($table) : $table;
    return $this;
  }

  public function set(string $column,mixed $value,bool $is_column_quoted = false) : DB
  {
    if(!array_key_exists('set',$this->sql) || !is_array($this->sql['set']))
      $this->sql['set'] = [];

    if($is_column_quoted === false)
      $column = $this->pdo->quoteColumns($column);

    $this->sql['set'][$column] = $value;
    return $this;
  }

  public function sets(array $cv,bool $is_column_quoted = false) : DB
  {
    foreach($cv as $c_ => $v_)
      $this->set($c_,$v_,$is_column_quoted);

    return $this;
  }

  // Delete
  // ----------------------------------------------------------------------
  public function delete() : DB
  {
    $this->mode = 'delete';
    return $this;
  }

  protected function _delete() : string
  {
    if(!array_key_exists('from',$this->sql) || empty($this->sql['from']))
      throw new Exception(_('table was empty'));

    $statement = array('DELETE');
    $statement[] = 'FROM ' . $this->sql['from'][0];
    if(array_key_exists('where',$this->sql) && !empty($this->sql['where']))
      $statement[] = 'WHERE ' . $this->sql['where'];

    $statement[] = $this->filter->fire('delete-after-query','');

    return implode(' ',$statement);
  }

  // Stored procedure
  // ----------------------------------------------------------------------------
  public function procedure(string $procedure_name, mixed ...$vars) : DB
  {
    if(!array_key_exists('procedure',$this->sql) || !($this->sql['set'] instanceof stdClass))
      $this->sql['procedure'] = new stdClass;

    $this->sql['procedure']->name = $procedure_name;
    $this->sql['procedure']->arguments = $vars;
    
    $this->mode = 'procedure';
    return $this;
  }
  
  protected  function _procedure() : mixed
  {
    $pdo = $this->pdo;
    $methodname = 'procedure';
    if(!array_key_exists($methodname,$this->sql) || empty($this->sql[$methodname]))
      throw new Exception(_('procedure was empty'));

    if(!method_exists($pdo,$methodname))
      throw new RuntimeException(_('procedure method not implement yet'));
    
    return $pdo->$methodname($this->sql['procedure']->name,$this->sql['procedure']->arguments);
  }

  // get status SQLSERVER2008
  // ---------------------------------------------------------------------------
  public function isSQLServer2008() : bool
  {
    return $this->sqlserver2008;
  }
}
