<?php
/******************************************************************************

  データベースへのアクセスを提供する抽象基本クラス。
  COPYRIGHT. All Written by K.,Nakagawa.

******************************************************************************/
abstract class Store
{
  /*------------------------------------------------------------------------------
    ストアオブジェクトのシングルトン実装
  ------------------------------------------------------------------------------*/
  public static function GetInstance($dsn,$tablename,$user = '',$passwd = '',$options = array())
    {
      static $instances = array();
      $key = $action_param = create_key($dsn,$tablename);

      if(!isset($instances[$key]))
        {
          $action_param = get_called_class();
          $instances[$key] = new static($dsn,$tablename,$user,$passwd,$options);
        }

      do_action('Store::GetInstance',array($action_param));

      return $instances[$key];
    }


  /*------------------------------------------------------------------------------
    Instance members
  ------------------------------------------------------------------------------*/
  protected $id;
  protected $dsn;
  protected $table;
  protected $dbh;
  protected $filters = array();
  protected $session;
  protected $columns;
  protected $queryAction;
  protected $queryFilter;

  private $callbacks = array();

  //コンストラクタ＆デストラクタ
  public function __construct($dsn,$table_name,$user,$passwd,$options = array())
    {
      $this->table = $table_name;
      $this->dsn = $dsn;
      $this->id = create_key($dsn,$this->table);

      if(isset($_SESSION))
        $this->session = &$_SESSION;
      else
        $this->session = array();

      if(!($this->dbh = GetPdoInstance($dsn,$user,$passwd)))
        throw new Exception(_('can not specify instance or can not connect to database.'));

      
      $this->queryFilter = null;
      $this->queryAction = null;
      if((($existsFilter = array_key_exists('filter',$options)) && false === $this->attachQueryFilter($options['filter'])) || !$existsFilter)
        $this->queryFilter = new Filter();
      if((($existsAction = array_key_exists('action',$options)) && false === $this->attachQueryAction($options['action'])) || !$existsAction)
        $this->queryAction = new Action();

      $this->columns = $this->dbh->getColumns($this->table);
    }
  public function __destruct()
    {
      $this->dbh = null;
      unset($this->dbh);
    }

  /*------------------------------------------------------------------------------
    フィルターおよびアクションオブジェクトのアタッチ及びゲッター
  ------------------------------------------------------------------------------*/
  public function getQueryAction()
    {
      return $this->queryAction;
    }
  public function attachQueryAction($action)
    {
      $rv = false;
      if($action instanceof Action)
      {
        $rv = $this->queryAction;
        $this->queryAction = $action;
      }
      return $rv;
    }


  public function getQueryFilter()
    {
      return $this->queryFilter;
    }
  public function attachQueryFilter($filter)
    {
      $rv = false;
      if($filter instanceof Filter)
      {
        $rv = $this->queryFilter;
        $this->queryFilter = $filter;
      }
      return $rv;
    }


  /*------------------------------------------------------------------------------
    プレースホルダ及びバインド処理のデフォルトプロセス
  ------------------------------------------------------------------------------*/
  protected function getPlaceholder($column)
    {
      if(!isset(static::$Placeholders))
        throw new Exception(_('can not find placeholders values.'));

      $placeholder = static::$Placeholders[$column][0];
      return $placeholder;
    }
  protected function bindValues($sth,$columns,$values,$isNamed = false)
    {
      if(!isset(static::$Placeholders))
        throw new Exception(_('can not find placeholders values.'));

      $columns = array_merge($columns);
      $values = array_merge($values);

      // if isNames is true,$columns = ['name:' => 'column name',....] $values = ['name1:' => value ,...]
      // else $columns $columns = [ 'column name','column name'....] $values = [ value, value ,...]
      if($isNamed)
      {
        foreach($columns as $name_ => $column_)
        {
          $value_ = $values[$name_];
          $type_ = static::$Placeholders[$columns[$name_]][1];
          $sth->bindValue($name_,$value_,$type_);
        }
      }
      else
      {
        $len = count($columns);
        for($i = 0;$i < $len;$i++)
        {
          $index_ = $i + 1;
          $value_ = $values[$i];
          $type_ = static::$Placeholders[$columns[$i]][1];
          $sth->bindValue($index_,$value_,$type_);
        }
      }

      return $sth;
    }

  //レコードを追加
  protected function _add($values,$columns = null)
    {
      $rv = false;
      $pdo = $this->dbh;
      if($columns === null || empty($columns))
        {
          $columns = $this->columns;
          array_shift($columns);
        }

      if(is_callable(array($this,'getPlaceholder')))
        {
          $placeholder = array();
          foreach($columns as $column)
            $placeholder[] = $this->getPlaceholder($column);
        }
      else
        {
          $placeholder = array_fill(0,count($values),'?');
        }

      $sql = sprintf('INSERT INTO %s(%s) VALUES(%s)',
                     $pdo->quoteTable($this->table),
                     implode(',',$pdo->quoteColumns($columns)),
                     implode(',',$placeholder));

      if(false !== ($sth = $pdo->prepare($sql)))
        {
          if(is_callable(array($this,'bindValues')))
            {
              $this->bindValues($sth,$columns,$values);
              $rv = $sth->execute();
            }
          else
            {
              $rv = $sth->execute($values);
            }

          if($rv !== false)
            $rv = $sth->rowCount();

          $this->invoke('add',array('prepare-sql',$sql,$values));
        }

      return $rv;
    }

  //特定のレコードを更新
  protected function _update($column_id,$id,$columnvalues)
    {
      $pdo = $this->dbh;

      $sets = array();
      $columns = array();
      $values = array();

      if($can_getplaceholder = is_callable(array($this,'getPlaceholder')))
        {
          foreach($columnvalues as $c_ => $v_)
            {
              $sets[] = sprintf('%s = %s',
                                $pdo->quoteColumns($c_),
                                $this->getPlaceholder($c_));
              $columns[] = $c_;
              $values[] = $v_;
            }
        }
      else
        {
          foreach($columnvalues as $c_ => $v_)
            {
              $sets[] = sprintf('%s = ?',$pdo->quoteColumns($c_));
              $columns[] = $c_;
              $values[] = $v_;
            }
        }

      $sql = sprintf('UPDATE %s SET %s WHERE %s = %s',
                     $pdo->quoteTable($this->table),
                     implode(',',$sets),
                     $pdo->quoteColumns($column_id),
                     $can_getplaceholder ? $this->getPlaceholder($column_id) : '?');

      $columns[] = $column_id;
      $values[] = $id;

      $rv = false;
      if(false !== ($sth = $pdo->prepare($sql)))
        {
          if(is_callable(array($this,'bindValues')))
            {
              $this->bindValues($sth,$columns,$values);
              $rv = $sth->execute();
            }
          else
            {
              $rv = $sth->execute($values);
            }

          $this->invoke('modify',array('prepare-sql',$sql,$values));
        }

      return $rv;
    }

  //特定のレコードを削除
  protected function _remove($column_id,$id)
    {
      $pdo = $this->dbh;

      $sql = sprintf('DELETE FROM %s WHERE %s = %d',
                     $pdo->quoteTable($this->table),
                     $pdo->quoteColumns($column_id),
                     $id);

      $rv = $pdo->exec($sql);
      $this->invoke('remove',array('exec-sql',$sql));

      return $rv;
    }

  //ステートメントオブジェクトを返す。
  //$query : 文字列,取得したいカラム名の配列
  protected function fetch($query = '',$columns = array())
    {
      $rv = false;
      $pdo = $this->dbh;

      if(!is_array($columns))
        $columns = preg_split('/[,\s]+/',$columns);

      $pdo = $this->dbh;

      $filter = $this->getQueryFilter();
      $aftertable = add_space_ifnot_empty($filter->fire('fetch-after-table',''));
      $afterquery = add_space_ifnot_empty($filter->fire('fetch-after-query',''));

      $sql = sprintf(
        'SELECT %s FROM %s%s',
        empty($columns) ? '*' : implode(',',$pdo->quoteColumns($columns)),
        $pdo->quoteTable($this->table),
        $aftertable
      );

      if(!empty($query))
        $sql .= ' '.$query;

      $query .= $afterquery;

      $rv = $pdo->query($sql);
      $this->invoke('fetch',array('query-sql',$sql));

      return $rv;
    }

  //セッションから現在ユーザーを得る。
  protected function current_user()
    {
      return (isset($this->session['uid']) && !empty($this->session['uid'])) ? $this->session['uid'] : '';
    }

  //総数を表示
  //$query : 文字列
  public function size($query = '')
    {
      $pdo = $this->dbh;

      $filter = $this->getQueryFilter();
      $aftertable = add_space_ifnot_empty($filter->fire('size-after-table',''));
      $afterquery = add_space_ifnot_empty($filter->fire('size-after-query',''));

      $sql = sprintf(
        'SELECT COUNT(*) FROM %s%s',
        $pdo->quoteTable($this->table),
        $aftertable
      );

      if(!empty($query))
        $sql .= " $query";

      $sql .= $afterquery;

      $sth = $this->dbh->query($sql);
      $this->invoke('size',array('query-sql',$sql));

      if($sth !== false)
        return $sth->fetchColumn();

      return false;
    }

  // テーブル結合
  public function innerJoin($table,$fk,$pk = '',$select_columns = '*',$query = '')
    {
      return $this->join('inner',$table,$fk,$pk,$select_columns,$query);
    }
  public function outerJoin($table,$fk,$pk = '',$select_columns = '*',$query = '')
    {
      return $this->join('outer',$table,$fk,$pk,$select_columns,$query);
    }

  protected function join($type,$table,$fk,$pk,$select_columns,$query)
    {
      $pdo = $this->dbh;

      if(!is_array($select_columns))
        $select_columns = preg_split('/[\s,;\/\-]/',$select_columns);

      $sql = sprintf('SELECT %s FROM %s %s JOIN %s ON %s = %s',
                     implode(',',$pdo->quoteColumns($select_columns)),
                     $pdo->quoteTable($this->table),
                     $type,
                     $pdo->quoteTable($table),
                     $pdo->quoteColumns($fk),
                     $pdo->quoteColumns(empty($pk) ? $this->columns[0] : $pk));

      if(!empty($query))
        $sql .= (' ' . $query);

      $this->invoke('join',array('query-sql',$sql));

      return $pdo->query($sql);
    }

  //すでに存在したら古いものを返す。
  //$queryにnullを渡すと現在のフィルターを返す。
  public function set_filter($type,$query = null)
    {
      $rv = '';

      if($query === null)
        {
          $rv = $this->filters[$type];
        }
      else
        {
          if(!empty($this->filters[$type]))
            $rv = $this->filters[$type];

          $this->filters[$type] = $query;
        }

      return $rv;
    }

  //直近のエラーを取得
  public function lastErrorInfo($element = 2)
    {
      $rv = false;

      $errinfo = $this->dbh->errorInfo();
      if(empty($errinfo[0]))
        return $rv;

      if(!is_int($element) || $element > 2 || $element < 0)
        {
          $rv = $errinfo;
        }
      else
        {
          if(isset($errinfo[$element]))
            $rv = $errinfo[$element];
        }

      return $rv;
    }

  //直近のエラー(メッセージ)を返す。
  public function lastErrorString()
    {
      if(false === ($rv = $this->lastErrorInfo()))
        $rv = '';

      return $rv;
    }

  //個別カラムの検索・抽出
  //第一引数が配列なら配列を返す
  public function row($primary_value,$columns,$tr = false)
    {
      $rv = false;
      $pdo = $this->dbh;
      $primary_key = $this->columns[0];
      $return_type = 0; //配列で返す。

      if(!is_array($primary_value))
        {
          if(is_string($primary_value))
            {
              $primary_value = preg_split('/[,\s]+/',$primary_value);
              if(count($primary_value) == 1)
                $return_type = 1;
            }
          else if(is_int($primary_value))
            {
              $primary_value = array($primary_value);
              $return_type = 1;
            }
        }

      foreach($primary_value as &$v_)
        {
          if(!is_int($v_))
            $v_ = intval($v_);
        }

      if(empty($primary_value))
        return false;

      $len = count($primary_value);
      $query = sprintf($len > 1 ? 'where %s in(%s)' : 'where %s = %d',
                       $primary_key,
                       $len > 1 ? implode(',',$primary_value) : $primary_value[0]);

      if(false !== ($sth = $this->fetch($query,$columns,$tr) ))
        {
          if(false !== ($rv = $sth->fetchAll(PDO::FETCH_ASSOC)))
            {
              if(0 == count($rv))
                {
                  $rv = null;
                }
              else
                {
                  if($return_type == 1)
                    $rv = $rv[0];
                }
            }
          $sth->closeCursor();
          $sth = null;
        }
      return $rv;
    }

  public function rowTR($primary_value,$columns)
    {
      return $this->row($primary_value,$columns,true);
    }

  //イベントハンドラを設定
  public function on($name,$callback)
    {
      if(is_callable($callback))
        {
          if(!isset($this->callbacks[$name]))
            $this->callbacks[$name] = array();

          if(false === array_search($callback,$this->callbacks[$name]))
            $this->callbacks[$name][] = $callback;
        }
    }

  //イベントハンドラを解除
  public function off($name,$callback = null)
    {
      if(isset($this->callbacks[$name]))
        {
          if($callback)
            {
              if(false !== ($key = array_search($callback,$this->callbacks[$name])))
                {
                  $this->callbacks[$name][$key] = null;
                  unset($this->callbacks[$name][$key]);
                }
            }
          else
            {
              //すべて削除
              $this->callbacks[$name] = null;
              unset($this->callbacks[$name]);
            }
        }
    }

  protected function invoke($name,$params = array())
    {
      $rv = false;

      if(isset($this->callbacks[$name]))
        {
          $rv = array();
          foreach($this->callbacks[$name] as $i => $callback)
            $rv[] = call_user_func_array($callback,$params);
        }

      return $rv;
    }

  //$mode    : 複数検索時の演算 'or' / 'and'
  //$word    : 検索語(スペース区切り)
  //$search_column : 検索するカラムor関数など
  //$pos     : OFFSET
  //$unicode : UNICODE検索拡張 デフォルト:しない
  //return   : ステートメントハンドル or false
  public function search($mode,$word,$search_column,$num = 10,$pos = 0,$unicode = false)
    {
      $pdo = $this->dbh;

      $columns = $this->columns;
      //$columns[] = $pdo->concat($search_columns,'search');

      $cond = sprintf('WHERE %s%s',
                      $pdo->like($search_column,$word,$mode,$unicode),
                      $pdo->limit($num,$pos,array('before' => ' ','orderby' => $search_column)));

      $rv = false;
      if(false !== ($sth = $this->fetch($cond,$columns)))
        {
          $rv = array();
          while(false !== ($row = $sth->fetch(PDO::FETCH_ASSOC)))
            {
              $rv[] = $row;
            }
          $sth->closeCursor();
          $sth = null;
        }

      $this->invoke('search',array('query-condition',$cond));
      return $rv;
    }

  public function append($values,$columns = false)
    {
      if(false !== ($rv = $this->_add($values,$columns)))
        {
          $rv = $this->dbh->lastInsertId();
        }

      return $rv;
    }

  /*------------------------------------------------------------------------------
    PDOExtension Wrapper
  ------------------------------------------------------------------------------*/
  public function query($sql)
    {
      return $this->dbh->query($sql);
    }

  public function quote($str)
    {
      return $this->dbh->quote($str);
    }
  public function quoteTable($tablename)
    {
      return $this->dbh->quoteTable($tablename);
    }
  public function quoteColumns($columns)
    {
      return $this->dbh->quoteColumns($columns);
    }

  /*------------------------------------------------------------------------------
    トランザクション ラッパー
  ------------------------------------------------------------------------------*/
  public function beginTransaction()
    {
      return $this->dbh->begin();
    }

  public function commit()
    {
      return $this->dbh->commit();
    }
  public function rollBack()
    {
      return $this->dbh->rollBack();
    }

  public function begin()
    {
      return $this->beginTransaction();
    }

  private function end($commit = true)
    {
      return $b ? $this->dbh->commit() : $this->dbh->rollBack();
    }

  public function inTransaction()
    {
      return $this->dbh->inTransaction();
    }

  //最終更新日を得る
  public function getLastUpdate($fmt = 'Y/m/d H:i:s')
    {
      return $this->dbh->getLastUpdate($this->table,$fmt);
    }
}

