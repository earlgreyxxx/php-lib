<?php
/*******************************************************************************

  This class is derivered from PDO(PHP INTERNAL CORE CLASS).
   Aattach database handle(PDO Instance) to Store Object,
     and solution for differences of PDO drivers. 

  This Abstract Class derivered to SQLServer(PDO-DBLIB & PDO-SQLSRV)

   ALL WRITTEN BY K,NAKAGAWA.

*******************************************************************************/

abstract class PDOSqlserver extends PDOExtension
{
  /*------------------------------------------------------------------------------
    Statics
  ------------------------------------------------------------------------------*/
  protected static string $CONDITION = 'HAVING ';
  protected static string $SELECT_LOCK = '';
  protected static int $QUERY_TIMEOUT = 30;

  /*------------------------------------------------------------------------------
    Implements.
  ------------------------------------------------------------------------------*/
  public function begin() : bool
  {
    return $this->beginTransaction();
  }

  public function exists(string $table) : bool
  {
    $rv = false;
    $sql = sprintf('SELECT COUNT(*) FROM dbo.sysobjects WHERE id = object_id(%s) AND OBJECTPROPERTY( id, %s ) = 1',
      $this->quote($table),
      $this->quote('IsUserTable'));

    if(false !== ($sth = $this->query($sql)))
    {
      if(0 < $sth->fetchColumn())
        $rv = true;

      $sth->closeCursor();
      $sth = null;
    }

    return $rv;
  }

  public function getTables(mixed $get_option = 0) : array
  {
    $rv = false;
    $fn = null;
    $sql = <<<__SQL_STATEMENT__
      SELECT 
      (sys_schema.name+'.'+sys_objects.name) as name,
      create_date,
      modify_date,
      type_desc 
      FROM [sys].[objects] sys_objects
      INNER JOIN [sys].[schemas] sys_schema on sys_schema.schema_id = sys_objects.schema_id
      WHERE type IN(N'U',N'V')
__SQL_STATEMENT__;

    if($sth = $this->query($sql))
    {
      $rv = [];
      if($get_option > 0)
      {
        $keys = [];
        if($get_option & PDOExtension::GET_NAME)
          $keys[] = 'name';
        if($get_option & PDOExtension::GET_CREATE)
          $keys[] = 'create_date';
        if($get_option & PDOExtension::GET_MODIFY)
          $keys[] = 'modify_date';
        if($get_option & PDOExtension::GET_TYPE)
          $keys[] = 'type_desc';

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
        else
        {
          throw new RuntimeException(_('invalid keys'));
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

  public function getViews() : array
  {
    $sql = 'SELECT TABLE_SCHEMA,TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS';

    $rv = [];
    if(false != ($sth = $this->query($sql)))
    {
      $views = $sth->fetchAll(PDO::FETCH_NUM);
      foreach($views as $view)
      {
        list($schema,$viewname) = $view;
        $rv[] = sprintf('%s.%s',$schema,$viewname);
      }
    }

    return $rv;
  }

  public function getColumns(string $table) : array
  {
    $rv = false;
    $obj = explode('.',$table);
    switch(count($obj))
    {
    case 1:
      $table = $obj[0];
      $sql = sprintf('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s ORDER BY ORDINAL_POSITION',$this->quote($table));
      break;
    case 2:
      list($schema,$table) = $obj;
      $sql = sprintf('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION',$this->quote($schema),$this->quote($table));
      break;
    case 3:
      list($database,$schema,$table) = $obj;
      $sql = sprintf('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_CATALOG = %s AND TABLE_SCHEMA = %s AND TABLE_NAME = %s ORDER BY ORDINAL_POSITION',$this->quote($database),$this->quote($schema),$this->quote($table));
      break;
    default:
      throw new Exception(_('argument $table is invalid'));
    }

    if(false != ($sth = $this->query($sql)))
    {
      $columns = array();
      while($name = $sth->fetchColumn())
        $columns[] = $name;

      if(count($columns) > 0)
        $rv = $columns;
    }

    return $rv;
  }

  public function getInfo(string $table) : array
  {
    $rv = array('table' => array(),'column' => array());
    $sql = sprintf('SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = %s',$this->quote($table));
    if(false != ($sth = $this->query($sql)))
    {
      while($row = $sth->fetch(PDO::FETCH_ASSOC))
        $rv['column'][$row['COLUMN_NAME']] = $row;

      $sth->closeCursor();
      $sth = null;
    }
    $sql = sprintf("SELECT * FROM (SELECT name,create_date,modify_date FROM sys.objects WHERE type IN(N'U',N'V')) as sys_objects INNER JOIN INFORMATION_SCHEMA.TABLES ON  INFORMATION_SCHEMA.TABLES.TABLE_NAME = sys_objects.name WHERE INFORMATION_SCHEMA.TABLES.TABLE_NAME = %s",$this->quote($table));
    if(false != ($sth = $this->query($sql)))
    {
      $rv['table'] = $sth->fetch(PDO::FETCH_ASSOC);
      $sth->closeCursor();
      $sth = null;
    }

    return $rv;
  }

  public function getLastUpdate(string $table,string $fmt = 'Y/m/d') : string
  {
    $rv = false;
    if(!empty($table))
    {
      $sql = sprintf('SELECT modify_date FROM sys.objects WHERE type_desc=%s and object_id = object_id(%s)',
        $this->quote('USER_TABLE'),
        $this->quote($table));

      if(false !== ($sth = $this->query($sql)))
      {
        if($rv = $sth->fetchColumn())
          $rv = date($fmt,strtotime($rv));

        $sth->closeCursor();
        $sth = null;
      }
    }
    return $rv;
  }

  //テーブル生成
  public function createTable(string $table,array $columns) : int|false
  {
    $values = [ 'AUTOINCREMENT' => 'IDENTITY(1,1)' ];

    $sql = sprintf(
      'CREATE TABLE %s(%s)',
      $this->quoteTable($table),
      $this->formatString($columns,$values,','.RN)
    );

    return $this->exec($sql);
  }

  //インデックス作成
  public function createIndex(string $table,string $index,array $columns,bool $unique = false,string $grant = 'alter') : int|false
  {
    $sql = sprintf(
      'CREATE %s %s ON %s(%s)',
      $unique ? 'UNIQUE INDEX' : 'INDEX',
      $index,
      $table,
      implode(',',$columns)
    );

    return $this->exec($sql);
  }

  // 外部キー制約
  public function setForeignKeyConstraint(string $table,?array $params = null) : int|false
  {
    $constraint = (object)$params;
    if(empty($constraint->name))
      $constraint->name = sprintf('FK_%s_%s',$table,implode('_',$constraint->columns));

    $sql = sprintf(
      'ALTER TABLE %s WITH CHECK ADD CONSTRAINT %s FOREIGN KEY (%s) REFERENCES %s(%s)',
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

    $sql .= ';';
    $sql .= sprintf(
      'ALTER TABLE %s CHECK CONSTRAINT %s',
      $this->quoteTable($table),
      $this->quoteColumns($constraint->name)
    );
    $sql .= ';';

    return $this->exec($sql);
  }

  //未実装
  public function get_selectlock() : string
  {
    return static::$SELECT_LOCK;
  }

  public function columnconcat(string $table,string $column1,string $column2,string $column3,string $column4,string $key) : string
  {
    $rv = sprintf('(SELECT CASE WHEN COUNT(%2$s) > 1 THEN CONCAT(\';\',(SELECT %2$s + \';\' FROM %3$s WHERE %4$s = %5$s AND %1$s = %6$s for xml path(\'\'))) ELSE (SELECT %2$s FROM %3$s WHERE %4$s = %5$s AND %1$s = %6$s) END FROM %3$s WHERE %4$s = %5$s AND %1$s = %6$s) AS %7$s',
      $this->quoteColumns($column1),
      $this->quoteColumns($column2),
      $this->quoteTable($table),
      $this->quoteColumns($column3),
      $this->quoteColumns($column4),
      $this->quote($key),
      $key);

    return $rv;
  }

  //未実装
  public function groupconcat(string $column) : string
  {
    throw new Exception(_('SQLServer can not understand group_concat'));
    return '';
  }

  private function concat2008(array $columns,string $as = '') : string
  {
    $rv = count($columns) > 1 ? sprintf('(%s)',implode(' + ',$this->quoteColumns($columns))) : $this->quoteColumns($columns[0]);
    if(!empty($as))
      $rv = $rv . " AS $as";

    return $rv;
  }

  private function concat2012(array $columns,string $as = '') : string
  {
    $rv = count($columns) > 1 ? sprintf('CONCAT(%s)',implode(',',$this->quoteColumns($columns))) : $this->quoteColumns($columns[0]);
    if(!empty($as))
      $rv = $rv . " AS $as";

    return $rv;
  }

  public function concat(array $columns,string $as = '',bool $is2008 = false) : string
  {
    $methodname = $is2008 === true ? 'concat2008' : 'concat2012';
    return call_user_func([$this,$methodname],$columns,$as);
  }

  //only SQLServer 2012 or heigher version.
  public function limit(int $num,int $pos,array $params = []) : string
  {
    if($pos > 0 && $num > 0)
      $rv = sprintf('OFFSET %d ROWS FETCH NEXT %d ROWS ONLY',$pos,$num);
    else if($num > 0 && empty($pos))
      $rv = sprintf('OFFSET 0 ROWS FETCH NEXT %d ROWS ONLY',$num);
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

  public function quoteX(string $x) : string
  {
    return sprintf('[%s]',$x);
  }

  protected function quoteN(string $n,int $hint = PDO::PARAM_STR) : string
  {
    return 'N'.parent::quote($n,$hint);
  }

  // for PHP8 ～
  public function quote(string $string,int $type = PDO::PARAM_STR) : string|false
  {
    return $this->quoteN($string,$type);
  }

  public function procedure(string $name,array $arguments,bool $arguments_are_quoted = false) : string
  {
    if(!$arguments_are_quoted)
    {
      foreach($arguments as &$v)
      {
        if(is_string($v) && $v !== '?' && $v[0] !== ':')
          $v = $this->quote($v);
      }
    }
    return sprintf('EXEC %s %s',$name,implode(',',$arguments));
  }

  public function like(string $search_column,string $str,string $multimode = 'and',bool $unicode = false) : string
  {
    $words = preg_split('/\s+/u',$str);
    $rv = array();

    $collation = '';
    if($unicode === true)
      $collation = 'COLLATE Japanese_BIN';
    else if(is_string($unicode) && strlen($unicode) > 0)
      $collation = 'COLLATE ' . $unicode;

    foreach($words as $word)
    {
      if(strlen($word) > 0)
      {
        $rv[] = sprintf('%s %s LIKE %s',
          $search_column,
          $collation,
          $this->quote('%'.$this->escape($word).'%'));
      }
    }

    return implode(" $multimode ",$rv);
  }

  public function escape(string $str) : string
  {
    return str_replace(
      array('[','%','_'),
      array('[[]','[%]','[_]'),
      $str
    );
  }
}