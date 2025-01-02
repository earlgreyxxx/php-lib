<?php
/*******************************************************************************

  This class is derivered from PDO(PHP INTERNAL CORE CLASS).
   Aattach database handle(PDO Instance) to Store Object,
     and solution for differences of PDO drivers. 

  This Class is for PDO - MySQL/MariaDB

   ALL WRITTEN BY K,NAKAGAWA.

*******************************************************************************/
define('MYSQL_STORAGE_ENGINE','InnoDB');

class PDOMysql extends PDOExtension
{
  /*------------------------------------------------------------------------------
    Statics
  ------------------------------------------------------------------------------*/
  protected static $CONDITION = 'having ';
  protected static $SELECT_LOCK = 'for update';
  protected static $DBM = 'mysql';

  /*------------------------------------------------------------------------------
    Instances
  ------------------------------------------------------------------------------*/


  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($dsn,$username='',$password='',$options = array())
  {
    $this->dsn = $dsn;

    if(!isset($options[PDO::ATTR_EMULATE_PREPARES]))
      $options[PDO::ATTR_EMULATE_PREPARES] = false;

    if(version_compare(PHP_VERSION,'5.3.6') < 0)
      $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES utf8';

    parent::__construct($dsn,$username,$password,$options);
  }

  protected function get_dbname()
  {
    if(!preg_match('/dbname=(.+?);/i',$this->dsn,$m))
      throw new Exception(_('dbname is not specified'));

    return $m[1];
  }

  /*------------------------------------------------------------------------------
    Implements
  ------------------------------------------------------------------------------*/
  public function exists($table)
  {
    $rv = false;
    $sql = sprintf('SHOW TABLES LIKE %s',$this->quote($table));

    if(false !== ($sth = $this->query($sql)))
    {
      if(false !== $sth->fetchColumn())
        $rv = true;

      $sth->closeCursor();
      $sth = null;
    }

    return $rv;
  }

  public function getTables($get_option = 0)
  {
    $rv = false;
    $items = array();
    $dbname= $this->get_dbname();

    if($sth = $this->query(sprintf('SELECT * from information_schema.tables WHERE TABLE_SCHEMA = %s', $this->quote($dbname))))
    {
      $rv = array();
      if($get_option)
      {
        $keys = array();
        if($get_option & PDOExtension::GET_NAME)
          $keys[] = 'TABLE_NAME';
        if($get_option & PDOExtension::GET_CREATE)
          $keys[] = 'CREATE_TIME';
        if($get_option & PDOExtension::GET_MODIFY)
          $keys[] = 'UPDATE_TIME';
        if($get_option & PDOExtension::GET_INCREMENT)
          $keys[] = 'AUTO_INCREMENT';
        if($get_option & PDOExtension::GET_ROWS)
          $keys[] = 'TABLE_ROWS';
        if($get_option & PDOExtension::GET_LENGTH)
          $keys[] = 'DATA_LENGTH';
        if($get_option & PDOExtension::GET_TYPE)
          $keys[] = 'TABLE_TYPE';

        $keys_len = count($keys);
        if($keys_len == 1)
        {
          $rv = array();
          $keys = $keys[0];
          $fn = function($row) use ($keys,&$rv) {
            $rv[] = $row[$keys];
          };
        }
        else if($keys_len > 1)
        {
          $fn = function($row) use ($keys,&$rv) {
            $items = array();
            foreach($keys as $key)
              $items[$key] = $row[$key];

            $rv[] = $items;
          };
        }

        while($row = $sth->fetch(PDO::FETCH_ASSOC))
          call_user_func($fn,$row);

        $sth->closeCursor();
      }
      else
      {
        $rv = $sth->fetchAll(PDO::FETCH_ASSOC);
      }
      $sth = null;
    }
    return $rv;
  }

  public function getColumns($table)
  {
    $rv = false;
    $dbname = $this->get_dbname();
    $sql = sprintf('SELECT COLUMN_NAME from information_schema.columns WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION',
      $this->quote($dbname),
      $this->quote($table));

    if(false != ($sth = $this->query($sql)))
    {
      $columns = array();
      while(false != ($row = $sth->fetch(PDO::FETCH_ASSOC)))
        $columns[] = $row['COLUMN_NAME'];

      if(count($columns) > 0)
        $rv = $columns;
    }

    return $rv;
  }

  public function getInfo($table)
  {
    $rv = array('table' => array(),'column' => array());
    $sql = sprintf('SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = N%s',$this->quote($table));
    if(false != ($sth = $this->query($sql)))
    {
      while($row = $sth->fetch(PDO::FETCH_ASSOC))
        $rv['column'][$row['COLUMN_NAME']] = $row;

      $sth->closeCursor();
      $sth = null;
    }
    $sql = sprintf("SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = N%s",$this->quote($table));
    if(false != ($sth = $this->query($sql)))
    {
      $rv['table'] = $sth->fetch(PDO::FETCH_ASSOC);
      $sth->closeCursor();
      $sth = null;
    }

    return $rv;
  }

  public function getLastUpdate($table,$fmt = 'Y/m/d')
  {
    $rv = false;
    if(!empty($table) && preg_match('/dbname=(.+?);/i',$this->dsn,$m))
    {
      $dbname = $m[1];
      $sql = sprintf('SELECT CREATE_TIME, UPDATE_TIME from information_schema.tables WHERE TABLE_SCHEMA = %s and TABLE_NAME = %s',
        $this->quote($dbname),
        $this->quote($table));

      if(false !== ($sth = $this->query($sql)))
      {
        if($row = $sth->fetch(PDO::FETCH_ASSOC))
        {
          $create_time = $row['CREATE_TIME'];
          $update_time = $row['UPDATE_TIME'];

          $rv = date($fmt,strtotime($update_time ? $update_time : $create_time));
        }

        $sth->closeCursor();
      }
    }
    return $rv;
  }

  public function begin()
  {
    return $this->beginTransaction();
  }

  //テーブル生成
  public function createTable($table,$columns)
  {
    $values = array( 'AUTOINCREMENT' => 'AUTO_INCREMENT' );

    $sql = sprintf('CREATE TABLE %s(%s) ENGINE=%s DEFAULT CHARSET=utf8',
      $this->quoteTable($table),
      $this->formatString($columns,$values,','),
      MYSQL_STORAGE_ENGINE);

    return $this->exec($sql);
  }

  //インデックス作成
  public function createIndex($table,$index,$columns,$unique = false,$grant = 'alter')
  {
    $sql = array('index' => sprintf('CREATE %1$s %2$s ON %3$s(%4$s)',
      $unique ? 'UNIQUE INDEX' : 'INDEX',
      $index,
      $this->quoteTable($table),
      implode(',',$columns)),
    'alter' => sprintf('ALTER TABLE %3$s ADD %1$s %2$s(%4$s)',
    $unique ? 'UNIQUE' : 'INDEX',
    $index,
    $this->quoteTable($table),
    implode(',',$columns)));

    return $this->exec($sql[$grant]);
  }

  // 外部キー制約
  public function setForeignKeyConstraint(string $table,array $params)
  {
    $constraint = (object)$params;
    if(empty($constraint->name))
      $constraint->name = sprintf('FK_%s_%s',$table,implode('_',$constraint->columns));

    $sql = sprintf(
      'ALTER TABLE %s ADD FOREIGN KEY %s(%s) REFERENCES %s(%s)',
      $this->quoteTable($table),
      $this->quoteColumns($constraint->name),
      implode(',',$this->quoteColumns($constraint->columns)),
      $this->quoteTable($constraint->refTable),
      implode(',',$this->quoteColumns($constraint->refColumns)),
    );

    if(!empty($constraint->ondelete))
      $sql .= ' ON DELETE '. $constraint->ondelete;

    if(!empty($constraint->onupdate))
      $sql .= ' ON UPDATE '. $constraint->onupdate;

    return $this->exec($sql);
  }

  public function get_selectlock()
  {
    return self::$SELECT_LOCK;
  }

  public function groupconcat($column)
  {
    return sprintf('CONCAT(";",GROUP_CONCAT(%s separator ";"),";")',$this->quoteColumns($column));
  }

  public function concat($columns,$as = '')
  {
    $rv = count($columns) > 1 ? sprintf('CONCAT(%s)',implode(',',$this->quoteColumns($columns))) : $columns[0];
    if(!empty($as))
      $rv = $rv . " AS $as";

    return $rv;
  }

  public function like($column,$str,$multimode = 'AND',$unicode = false)
  {
    $words = preg_split('/\s+/u',$str);
    $rv = '';
    $rva = array();

    $fmt = $unicode === true ? 'CONVERT(%s using utf8) COLLATE utf8_unicode_ci LIKE %s' : '%s LIKE %s';

    foreach($words as $word)
    {
      if(!empty($word))
      {
        $rva[] = sprintf($fmt,
          $this->quoteColumns($column),
          $this->quote('%'.$this->escape($word).'%'));
      }
    }

    if(!empty($rva))
      $rv = implode(" $multimode ",$rva) . ' escape \'\\\\\'';

    return $rv;
  }

  protected function quoteX($x)
  {
    return sprintf('`%s`',$x);
  }

  public function procedure($name,$arguments,$arguments_are_quoted = false)
  {
    if(!$arguments_are_quoted)
    {
      foreach($arguments as &$v)
      {
        if(is_string($v) && $v !== '?' && $v[0] !== ':')
          $v = $this->quote($v);
      }
    }
    return sprintf('CALL %s(%s)',$name,implode(',',$arguments));
  }

  // override
  public function quoteTable(string $tablename,bool $parseDot = false)
  {
    return parent::quoteTable($tablename,$parseDot);
  }
}
