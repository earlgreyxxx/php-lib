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
  protected static $CONDITION = '';
  protected static $SELECT_LOCK = '';
  protected static $DBM = '';

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
  public static function GetInstance($dsn,$user = '',$passwd = '', $options = null)
  {
    list($dbtype) = explode(':',$dsn);
    $classname = 'PDO' . ucfirst($dbtype);
    if(!class_exists($classname))
      throw new Exception(sprintf(_("'%s' not defined."),$classname));

    return new $classname($dsn,$user,$passwd,$options);
  }

  /*------------------------------------------------------------------------------
   abstract methods
  ------------------------------------------------------------------------------*/

  // check table exists.
  abstract public function exists($table);

  // enumerate tables of database/schema
  abstract public function getTables($get_option);

  // enumerate columns of table.
  abstract public function getColumns($table);

  // get last update of table.
  abstract public function getLastUpdate($table,$fmt);

  // Transaction methods
  abstract public function begin();

  // sql exec depends dbms
  abstract public function createTable($table,$columns);

  // sql exec depends dbms
  abstract public function createIndex($table,$index,$columns,$unique = false,$grant = 'alter');

  // sql concat columns
  abstract public function concat($columns);

  // sql group_concat
  abstract public function groupconcat($column);

  // set foreign key constraint: params has key: name,columns,refTable,refColumns,onupdate,ondelete
  abstract public function setForeignKeyConstraint(string $table,array $params);

  /*------------------------------------------------------------------------------
   Base implements
  ------------------------------------------------------------------------------*/

  //properties
  protected $dsn;

  protected function formatString($lines,$values,$delimitor = ' ')
  {
    if(!is_array($lines))
      $lines = array($lines);

    foreach($lines as &$line)
    {
      $line = preg_replace_callback('/%(.+?)%/',
        function($m) use($values)
        {
          return array_key_exists($m[1],$values) ? $values[$m[1]] : '';
        }
      ,$line);
    }

    return implode($delimitor,$lines);
  }

  public function __construct($dsn,$username='',$password='',$options = array())
  {
    parent::__construct($dsn,$username,$password,$options);
  }

  public function getPrefix()
  {
    list($prefix) = explode(':',$this->dsn);
    return $prefix;
  }

  public function get_cond()
  {
    return static::$CONDITION;
  }

  public function get_selectlock()
  {
    return '';
  }
  public function get_dbm()
  {
    return static::$DBM;
  }

  public function columnconcat($table,$column1,$column2,$column3,$column4,$key)
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

  public function like($search_column,$str,$multimode = 'AND',$unicode = false)
  {
    $words = preg_split('/\s+/u',$str);
    $rv = array();

    foreach($words as $word)
    {
      if(!empty($word))
        $rv[] = sprintf('%s LIKE %s',
          $search_column,
          $this->quote('%'.$this->escape($word).'%'));
    }

    return implode(" $multimode ",$rv) . ' escape \'\\\'';
  }

  public function escape($str)
  {
    $rv = str_replace(array('\\','[','%','_'),
      array('\\\\','\[','\%','\_'),
      $str);
    return $rv;
  }

  public function limit($num,$pos,$params = array())
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

  public function options($name,$arg1,$arg2)
  {
    return '';
  }

  protected function quoteX(string $x)
  {
    return sprintf('"%s"',trim($x));
  }

  private function _quotes(string $str,bool $parseDot = true)
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

  public function quoteTable(string $tablename,bool $parseDot = true)
  {
    return $this->_quotes($tablename,$parseDot);
  }

  public function quoteColumns($columns,bool $parseDot = true)
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

  public function getPlaceholder($param_int = false)
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

  public function drops(array $tables)
  {
    $pdo = $this;
    $tables = array_filter($tables,function($el) use($pdo) { return $pdo->exists($el); });
    if(count($tables) <=  0)
      return;

    $sql = 'DROP TABLE ' . implode(',',array_map(function($el) use($pdo) { return $this->quoteTable($el); },$tables));
    echo $sql,PHP_EOL;
    return $this->exec($sql);
  }

  // Trancate table
  public function truncate(string $table)
  {
    if(!$this->exists($table))
      throw new RuntimeException(_('table not found'));

    return $this->exec(sprintf('truncate table %s',$this->quotetable($table)));
  }
}
