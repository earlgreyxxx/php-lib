<?php
/*******************************************************************************

  This class is derivered from PDO(PHP INTERNAL CORE CLASS).
   Aattach database handle(PDO Instance) to Store Object,
     and solution for differences of PDO drivers. 

   Abstract class base implements of PDOExtension.

   ALL WRITTEN BY K,NAKAGAWA.

*******************************************************************************/
abstract class PDOExtension extends PDO
{
  /*------------------------------------------------------------------------------
    Statics
  ------------------------------------------------------------------------------*/
  protected static string $CONDITION = '';
  protected static string $SELECT_LOCK = '';
  protected static string $DBM = '';

  /*------------------------------------------------------------------------------
   object contant:
  ------------------------------------------------------------------------------*/
  const GET_NAME = 1;
  const GET_CREATE = 2;
  const GET_MODIFY = 4;
  const GET_UPDATE = 8;
  const GET_INCREMENT = 16;
  const GET_ROWS = 32;
  const GET_LENGTH = 64;
  const GET_TYPE = 128;

  const PARAM_IS_INT = true;
  const PARAM_IS_STR = true;

  /*------------------------------------------------------------------------------
   class methods : GetInstance.
     parsing $dsn and decide to create instance of PDO deliverred class.
  ------------------------------------------------------------------------------*/
  public static function GetInstance(string $dsn,string $user = '',string $passwd = '',?array $options = null) : static
  {
    $parse = explode(':',$dsn);
    $dbtype = $parse[0] ?? null;
    if(empty($dbtype))
      throw new RuntimeException(_('invalid dsn format'));

    $classname = 'PDO' . ucfirst($dbtype);
    if(!class_exists($classname))
      throw new Exception(sprintf(_("'%s' not defined."),$classname));

    return new $classname($dsn,$user,$passwd,$options);
  }

  /*------------------------------------------------------------------------------
   abstract methods
  ------------------------------------------------------------------------------*/

  // check table exists.
  abstract public function exists(string $table) : bool;

  // enumerate tables of database/schema
  abstract public function getTables(mixed $get_option) : array;

  // enumerate columns of table.
  abstract public function getColumns(string $table) : array;

  // get last update of table.
  abstract public function getLastUpdate(string $table,string $fmt) : string;

  // Transaction methods
  abstract public function begin() : bool;

  // sql exec depends dbms
  abstract public function createTable(string $table,array $columns) : int|false;

  // sql exec depends dbms
  abstract public function createIndex(string $table,string $index,array $columns,bool $unique = false,string $grant = 'alter') : int|false;

  // sql concat columns
  abstract public function concat(array $columns,string $as = '') : string;

  // sql group_concat
  abstract public function groupconcat(string $column) : string;

  // set foreign key constraint: params has key: name,columns,refTable,refColumns,onupdate,ondelete
  abstract public function setForeignKeyConstraint(string $table,array $params) : int|false;

  /*------------------------------------------------------------------------------
   Base implements
  ------------------------------------------------------------------------------*/

  //properties
  protected string $dsn;

  protected function formatString(string|array $lines,array $values,string $delimitor = ' ') : string
  {
    if(!is_array($lines))
      $lines = array($lines);

    foreach($lines as &$line)
    {
      $line = preg_replace_callback(
        '/%(.+?)%/',
        function ($m) use ($values) { return array_key_exists($m[1], $values) ? $values[$m[1]] : ''; },
        $line
      );
    }

    return implode($delimitor,$lines);
  }

  public function __construct(string $dsn,string $username='',string $password='',array $options = [])
  {
    parent::__construct($dsn,$username,$password,$options);
  }

  public function getPrefix() : string
  {
    list($prefix) = explode(':',$this->dsn);
    return $prefix;
  }

  public function get_cond() : string
  {
    return static::$CONDITION;
  }

  public function get_selectlock() : string
  {
    return '';
  }
  public function get_dbm() : string
  {
    return static::$DBM;
  }

  public function columnconcat(string $table,string $column1,string $column2,string $column3,string $column4,string $key)
  {
    return sprintf('(SELECT CASE WHEN COUNT(%2$s) > 1 THEN %3$s ELSE %2$s END FROM %4$s WHERE %5$s = %6$s AND meta_key = %7$s) AS %8$s',
      $this->quoteColumns($column1),
      $this->quoteColumns($column2),
      $this->groupconcat($column2),
      $this->quoteTable($table),
      $this->quoteColumns($column3),
      $this->quoteColumns($column4),
      $this->quote($key),
      $key);
  }

  public function like(string $search_column,string $str,string $multimode = 'AND',bool $unicode = false)
  {
    $words = preg_split('/\s+/u',$str);
    $rv = [];

    foreach($words as $word)
    {
      if(!empty($word))
        $rv[] = sprintf('%s LIKE %s',
          $search_column,
          $this->quote('%'.$this->escape($word).'%'));
    }

    return implode(" $multimode ",$rv) . ' escape \'\\\'';
  }

  public function escape(string $str) : string
  {
    return str_replace(
      ['\\','[','%','_'],
      ['\\\\','\[','\%','\_'],
      $str);
  }

  public function limit(int $num,int $pos,array $params = []) : string
  {
    if($pos > 0 && $num > 0)
      $rv = sprintf('LIMIT %d,%d',$pos,$num);
    else if($num > 0 && empty($pos))
      $rv = sprintf('LIMIT %d',$num);
    else
      $rv = '';

    if(!empty($rv))
    {
      if(isset($params['before']))
        $rv = $params['before'] . $rv;

      if(isset($params['after']))
        $rv = $rv . $params['after'];
    }
    return $rv;
  }

  public function options(string $name,mixed $arg1,mixed $arg2) : string
  {
    return '';
  }

  protected function quoteX(string $x) : string
  {
    return sprintf('"%s"',trim($x));
  }

  private function _quotes(string $str,bool $parseDot = true) : string
  {
    $rv = '';
    if($parseDot)
    {
      $ar = explode('.',$str);
      foreach($ar as &$el)
        $el = $this->quoteX($el);

      $rv = implode('.',$ar);
    }
    else
    {
      $rv = $this->quoteX($str);
    }

    return $rv;
  }

  public function quoteTable(string $tablename,bool $parseDot = true) : string
  {
    return $this->_quotes($tablename,$parseDot);
  }

  public function quoteColumns(string|array $columns,bool $parseDot = true) : string|array
  {
    if(is_array($columns))
    {
      foreach($columns as &$column)
      {
        if($column !== '*')
          $column = $this->_quotes($column,$parseDot);
      }
    }
    else if(is_string($columns))
    {
      if($columns !== '*')
        $columns = $this->_quotes($columns,$parseDot);
    }

    return $columns;
  }

  public function getPlaceholder(bool $param_int = false) : string
  {
    return '?';
  }

  // Drop table
  public function drop(string $table) : bool
  {
    if(!$this->exists($table))
      throw new RuntimeException(_('table not found'));

    return $this->exec(sprintf('DROP TABLE %s',$this->quoteTable($table)));
  }

  public function drops(array $tables) : int|false
  {
    $pdo = $this;
    $tables = array_filter($tables,function($el) use($pdo) { return $pdo->exists($el); });
    if(count($tables) <=  0)
      return false;

    $sql = 'DROP TABLE ' . implode(',',array_map(function($el) use($pdo) { return $this->quoteTable($el); },$tables));
    return $this->exec($sql);
  }

  // Trancate table
  public function truncate(string $table) : int|false
  {
    if(!$this->exists($table))
      throw new RuntimeException(_('table not found'));

    return $this->exec(sprintf('TRUNCATE TABLE %s',$this->quotetable($table)));
  }
}
