<?php
/*****************************************************************************

  ■ メタ・テーブルへのアクセスクラス

  - global filter (class-method-after-(query|table)) 
    meta-prepare-varchar-type

  - instance filter
    count-after-table meta-count-after-query
    get-after-table meta-get-after-query
    get_cond-after-table meta-get_cond-after-query
    gets-after-table meta-gets-after-query
    search-after-table meta-search-after-query
    match-after-table meta-match-after-query
    group_count-after-table meta-group_count-after-query 

  All Written by K.,Nakagawa.

*****************************************************************************/

class Meta implements ArrayAccess
{
  public static function Prepare(PDO $pdoex,$table,$meta_fkey)
  {
    $varchar_type = do_filter('meta-prepare-varchar-type',array('meta_key' => 'VARCHAR(128)','meta_value' => 'VARCHAR(512)'));
    $columns = array('meta_id INTEGER PRIMARY KEY %AUTOINCREMENT%',
      $meta_fkey . ' INTEGER',
      'meta_key ' . $varchar_type['meta_key'],
      'meta_value ' . $varchar_type['meta_value']);

    $indexcolumns = array($meta_fkey,'meta_key');
    $indexname = $meta_fkey.'_index';

    if(false === $pdoex->createTable($table,$columns))
      throw new Exception(_('create table failed...'));
    if(false === $pdoex->createIndex($table,$indexname,$indexcolumns))
      throw new Exception(_('create index failed...'));

    return true;
  }

  //スタティック・メソッドはここまで

  private $pdo;
  private $table;
  private $columns;
  private $filter;
  private $action;
  private $dbm;

  public function __construct($pdo,$table,array $options = array())
  {
    $this->pdo = $pdo;

    if(empty($table))
      throw new Exception(_('table name was empty.'));
    if(!$pdo->exists($table))
      throw new Exception(sprintf(_('Table not found: %s'),$table));

    $this->table = $table;
    $this->columns = $pdo->getColumns($table);
    $this->dbm = strtolower($pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

    $this->filter = null;
    $this->action = null;
    if((($existsFilter = array_key_exists('filter',$options)) && false === $this->attachFilter($options['filter'])) || !$existsFilter)
      $this->filter = new Filter();
    if((($existsAction = array_key_exists('action',$options)) && false === $this->attachAction($options['action'])) || !$existsAction)
      $this->action = new Action();
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

  protected function getHandle()
  {
    return $this->pdo;
  }

  protected function get_table()
  {
    return $this->table;
  }

  public function count($id,$k = '',$v = '')
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];
    $filter = $this->filter;
    $aftertable = add_space_ifnot_empty($filter->fire('count-after-table',''));
    $afterquery = add_space_ifnot_empty($filter->fire('count-after-query',''));

    $sql = sprintf(
      'SELECT COUNT(%s) FROM %s%s WHERE %s = :metafkey',
      $pdo->quoteColumns($this->columns[0]),
      $pdo->quoteTable($this->table),
      $aftertable,
      $pdo->quoteColumns($column_fkey)
    );

    if(!empty($k))
      $sql .= sprintf( ' AND %s = :metakey',$pdo->quoteColumns('meta_key'));

    if(!empty($v))
      $sql .= sprintf(' AND %s = :metavalue',$pdo->quoteColumns('meta_value'));

    $sql .= $afterquery;

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      $sth->bindValue(':metafkey',$id,PDO::PARAM_INT);
      if(!empty($k))
        $sth->bindValue(':metakey',$k,PDO::PARAM_STR);

      if(!empty($v))
        $sth->bindValue(':metavalue',$v,PDO::PARAM_STR);

      if(false !== $sth->execute())
      {
        $rv = intval($sth->fetchColumn());
        $sth->closeCursor();
        $sth = null;
      }
    }
    return $rv;
  }

  public function beginTransaction($params = null)
  {
    return $this->getHandle()->beginTransaction();
  }

  public function commit()
  {
    return $this->getHandle()->commit();
  }

  public function rollBack()
  {
    return $this->gethandle()->rollBack();
  }

  //外部キー,メタキー名,値,重複可能か？
  public function set($id,$k,$v,$is_multi = false)
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];

    //multisetまはた、値が配列の場合は multisetメソッドへ引き渡す。
    if($is_multi !== false || is_array($v))
      return $this->multiset($id,$k,$v,$is_multi);

    $order = null;

    //重複確認
    $num = $this->count($id,$k,'');
    if($num >= 1)
    {
      if(strlen(strval($v)) == 0 && (!is_int($v) || !is_float($v)))
      {
        $sql = sprintf('DELETE FROM %s WHERE %s = %s AND %s = %s',
          $pdo->quoteTable($this->table),
          $pdo->quoteColumns($column_fkey),
          $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT),
          $pdo->quoteColumns('meta_key'),
          $pdo->getPlaceholder());

        $orders = array('metafkey' => 1,'metakey' => 2);
      }
      else
      {
        $sql = sprintf('UPDATE %s SET %s = %s WHERE %s = %s AND meta_key = %s',
          $pdo->quoteTable($this->table),
          $pdo->quoteColumns('meta_value'),
          $pdo->getPlaceholder(),
          $pdo->quoteColumns($column_fkey),
          $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT),
          $pdo->getPlaceholder());

        $orders = array('metafkey' => 2,'metakey' => 3, 'metavalue' => 1);
      }
    }
    else if($num == 0)
    {
      if(empty($v) && ($v !== 0))
        return false;

      $sql = sprintf('INSERT INTO %s (%s,%s,%s) VALUES(%s,%s,%s)',
        $pdo->quoteTable($this->table),
        $pdo->quoteColumns($column_fkey),
        $pdo->quoteColumns('meta_key'),
        $pdo->quoteColumns('meta_value'),
        $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT),
        $pdo->getPlaceholder(),
        $pdo->getPlaceholder());

      $orders = array('metafkey' => 1,'metakey' => 2, 'metavalue' => 3);
    }

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      $sth->bindValue($orders['metafkey'],intval($id),PDO::PARAM_INT);
      $sth->bindValue($orders['metakey'],$k,PDO::PARAM_STR);
      if(strlen(strval($v)) > 0 || $v === 0)
        $sth->bindValue($orders['metavalue'],$v,PDO::PARAM_STR);

      if(false !== ($rv = $sth->execute()))
        $rv = true;

      $sth = null;
    }

    return $rv;
  }

  //$is_appendがfalseの時、値が配列の場合一旦削除して追加する。
  //trueの時は削除しない。
  public function multiset($id,$k,$v,$is_append = false)
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];

    //値が配列なら一端既存のすべてのキーを削除してから追加する。
    if(is_array($v))
    {
      if(!$is_append)
        $this->remove($id,$k);

      $sql = sprintf('INSERT INTO %s (%s,%s,%s) VALUES(%s,%s,%s)',
        $pdo->quoteTable($this->table),
        $pdo->quoteColumns($column_fkey),
        $pdo->quoteColumns('meta_key'),
        $pdo->quoteColumns('meta_value'),
        $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT),
        $pdo->getPlaceholder(),
        $pdo->getPlaceholder());

      if(false !== ($sth = $pdo->prepare($sql)))
      {
        foreach($v as $value)
        {
          $sth->bindValue(1,intval($id),PDO::PARAM_INT);
          $sth->bindValue(2,$k,PDO::PARAM_STR);
          $sth->bindValue(3,$value,PDO::PARAM_STR);
          if(false === ($rv = $sth->execute()))
            break;
        }

        $sth = null;
      }
    }
    else
    {
      if(empty($v) && (!is_int($v) || !is_float($v)))
      {
        //重複可能の場合は、setメソッドで削除できない。
        $rv = false;
      }
      else
      {
        //既に同じキー、同じ値であれば、成功とみなして true を返す。
        if($this->count($id,$k,$v) > 0)
          return true;

        $sql = sprintf('INSERT INTO %s (%s,%s,%s) VALUES(%s,%s,%s)',
          $pdo->quoteTable($this->table),
          $pdo->quoteColumns($column_fkey),
          $pdo->quoteColumns('meta_key'),
          $pdo->quoteColumns('meta_value'),
          $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT),
          $pdo->getPlaceholder(),
          $pdo->getPlaceholder());

        if(false !== ($sth = $pdo->prepare($sql)))
        {
          $sth->bindValue(1,intval($id),PDO::PARAM_INT);
          $sth->bindValue(2,$k,PDO::PARAM_STR);
          $sth->bindValue(3,$v,PDO::PARAM_STR);
          $rv = $sth->execute();
          $sth = null;
        }
      }
    }

    return $rv;
  }


  //複数の(キー、値)を一括登録(setメソッドのラッパーメソッド)
  public function sets($id,array $kv,$is_multi = false)
  {
    $rv = 0;
    foreach($kv as $k => $v)
    {
      if($this->set($id,$k,$v,$is_multi))
        $rv++;
    }

    return $rv;
  }

  //キーの一括更新。
  public function update($k,$v,$match = null)
  {
    $rv = false;
    $pdo = $this->pdo;
    $ph = $pdo->getPlaceholder();

    $sql = sprintf('UPDATE %s SET %s = %s where %s = %s',
      $this->table,
      $pdo->quoteColumns('meta_value'),
      $ph,
      $pdo->quoteColumns('meta_key'),
      $ph);

    if(!empty($match))
      $sql .= " AND meta_value = $ph";

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      $sth->bindValue(1,$v,PDO::PARAM_STR);
      $sth->bindValue(2,$k,PDO::PARAM_STR);
      if(!empty($match))
        $sth->bindValue(3,$match,PDO::PARAM_STR);

      if(false !== ($sth->execute()))
        $rv = true;//$sth->rowCount();

      $sth = null;
    }

    return $rv;
  }

  //updateメソッドのラッパー
  public function updates(array $kv,$matches = null)
  {
    $rv = array();
    foreach($kv as $k => $v)
      $rv[$k] = $this->update($k,$v,is_array($matches) ? $matches[$k] : $matches);

    return $rv;
  }

  //同じキーが複数ある場合は一番後から追加したものを返す。
  //$is_multi = trueを指定すると、すべて配列にして返す。
  public function get($id,$k,$is_multi = false)
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];
    $filter = $this->filter;
    $aftertable = add_space_ifnot_empty($filter->fire('get-after-table',''));
    $afterquery = add_space_ifnot_empty($filter->fire('get-after-query',''));

    $sql = sprintf(
      'SELECT %s FROM %s%s WHERE %s = %s AND %s = %s%s',
      $pdo->quoteColumns('meta_value'),
      $pdo->quoteTable($this->table),
      $aftertable,
      $pdo->quoteColumns($column_fkey),
      $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT),
      $pdo->quoteColumns('meta_key'),
      $pdo->getPlaceholder(),
      $afterquery
    );

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      $sth->bindValue(1,intval($id),PDO::PARAM_INT);
      $sth->bindValue(2,$k,PDO::PARAM_STR);

      if(false !== $sth->execute())
      {
        $rv = array();
        while(false !== ($value = $sth->fetchColumn()))
        {
          $rv[] = $value;
        }
        if($is_multi == false)
          $rv = array_pop($rv);
      }
    }

    return $rv;
  }

  //条件を指定してキーの値を配列として取得します。
  //$conditionは and で連結されます。$paramsはPDO::executeメソッドに渡されます。
  public function get_cond($k,$condition = '',$params = array())
  {
    $rv = false;
    $pdo = $this->getHandle();

    $filter = $this->filter;
    $aftertable = add_space_ifnot_empty($filter->fire('get_cond-after-table',''));
    $afterquery = add_space_ifnot_empty($filter->fire('get_cond-after-query',''));
    $sql = sprintf(
      'SELECT %s FROM %s%s WHERE %s = %s',
      $pdo->quoteColumns('meta_value'),
      $pdo->quoteTable($this->table),
      $aftertable,
      $pdo->quoteColumns('meta_key'),
      $pdo->getPlaceholder()
    );

    array_unshift($params,$k);

    if(!empty($condition))
      $sql .= (' AND '.$condition);

    $sql .= $afterquery;

    if(false != ($sth = $pdo->prepare($sql)))
    {
      if(false != $sth->execute($params))
      {
        $rv = array();
        while(false !== ($col = $sth->fetchColumn()))
        {
          $rv[] = $col;
        }
        $sth->closeCursor();
        $sth = null;
      }
    }

    return $rv;
  }

  //取得するキーを配列にして渡し、結果をハッシュ配列して返す。
  public function gets($id,array $k = array())
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];
    
    $filter = $this->filter;
    $aftertable = add_space_ifnot_empty($filter->fire('gets-after-table',''));
    $afterquery = add_space_ifnot_empty($filter->fire('gets-after-query',''));

    $ph = $pdo->getPlaceholder();
    $sql = sprintf(
      'SELECT %s,%s FROM %s%s WHERE %s = %s%s%s',
      $pdo->quoteColumns('meta_key'),
      $pdo->quoteColumns('meta_value'),
      $pdo->quoteTable($this->table),
      $aftertable,
      $pdo->quoteColumns($column_fkey),
      $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT),
      empty($k) ? '' : sprintf(' AND %s IN(%s)',$pdo->quoteColumns('meta_key'),implode(',',array_fill(0,count($k),$ph))),
      $afterquery
    );

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      $sth->bindValue(1,intval($id),PDO::PARAM_INT);
      if(!empty($k))
      {
        $len = count($k);
        for($i = 0;$i<$len;$i++)
          $sth->bindValue(2+$i,$k[$i],PDO::PARAM_STR);
      }

      if(false !== $sth->execute())
      {
        $rv = array();
        while(false !== ($row = $sth->fetch(PDO::FETCH_ASSOC)))
        {
          $k_ = $row['meta_key'];
          $v_ = $row['meta_value'];

          if(isset($rv[$k_]))
          {
            if(!is_array($rv[$k_]))
              $rv[$k_] = array($rv[$k_],$v_);
            else
              $rv[$k_][] = $v_;
          }
          else
          {
            $rv[$k_] = $v_;
          }
        }
        $sth->closeCursor();
        $sth = null;
      }
    }
    return $rv;
  }

  public function keys($id)
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];
    
    $sql = sprintf(
      'SELECT %1$s FROM %2$s WHERE %3$s = %4$s GROUP BY %1$s',
      $pdo->quoteColumns('meta_key'),
      $pdo->quoteTable($this->table),
      $pdo->quoteColumns($column_fkey),
      $pdo->getPlaceholder(PDOExtension::PARAM_IS_INT)
    );

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      $sth->bindValue(1,intval($id),PDO::PARAM_INT);
      if(false !== $sth->execute())
      {
        $rv = array();
        while($key = $sth->fetchColumn())
          $rv[] = $key;

        $sth->closeCursor();
        $sth = null;
      }
    }
    return $rv;
  }

  //キー、値に部分検索した行を返す。
  public function search($k,$v = '')
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];
    
    $filter = $this->filter;
    $aftertable = add_space_ifnot_empty($filter->fire('search-after-table',''));
    $afterquery = add_space_ifnot_empty($filter->fire('search-after-query',''));

    $sql = sprintf(
      'SELECT %s AS id,%s,%s FROM %s%s WHERE meta_key = %s%s%s',
      $pdo->quoteColumns($column_fkey),
      $pdo->quoteColumns('meta_key'),
      $pdo->quoteColumns('meta_value'),
      $pdo->quoteTable($this->table),
      $aftertable,
      $pdo->getPlaceholder(),
      empty($v) ? '' : sprintf(' AND %s LIKE %s',$pdo->quoteColumns('meta_value'),$pdo->getPlaceholder()),
      $afterquery
    );

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      if(false !== $sth->execute(empty($v) ? array($k) : array($k,"%$v%")))
      {
        $rv = $sth->fetchAll(PDO::FETCH_ASSOC);
        $sth->closeCursor();
        $sth = null;
      }
    }
    return $rv;
  }

  public function match($k,$v = '')
  {
    $rv = false;
    $pdo = $this->pdo;
    $column_fkey = $this->columns[1];

    $filter = $this->filter;
    $aftertable = add_space_ifnot_empty($filter->fire('match-after-table',''));
    $afterquery = add_space_ifnot_empty($filter->fire('match-after-query',''));

    $sql = sprintf(
      'SELECT %s AS id,%s,%s FROM %s%s WHERE %s = %s%s%s',
      $pdo->quoteColumns($column_fkey),
      $pdo->quoteColumns('meta_key'),
      $pdo->quoteColumns('meta_value'),
      $pdo->quoteTable($this->table),
      $aftertable,
      $pdo->quoteColumns('meta_key'),
      $pdo->getPlaceholder(),
      empty($v) ? '' : sprintf(' AND %s = %s',$pdo->quoteColumns('meta_value'),$pdo->getPlaceholder()),
      $afterquery
    );

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      if(false !== $sth->execute(empty($v) ? array($k) : array($k,$v)))
      {
        $rv = $sth->fetchAll(PDO::FETCH_ASSOC);
        $sth->closeCursor();
        $sth = null;
      }
    }

    return $rv;
  }

  public function remove($id,$keys = '')
  {
    $pdo = $this->pdo;
    $rv = false;

    if(empty($keys))
      return $this->clear($id);

    $params = is_array($keys) ? $keys : array($keys);

    if(count($params) > 1)
    {
      $sql = sprintf('DELETE FROM %s WHERE %s = %d AND %s IN(%s)',
        $pdo->quoteTable($this->table),
        $pdo->quoteColumns($this->columns[1]),
        intval($id),
        $pdo->quoteColumns('meta_key'),
        implode(',',array_fill(0,count($keys),$pdo->getPlaceholder())));
    }
    else
    {
      $sql = sprintf('DELETE FROM %s WHERE %s = %d AND meta_key = %s',
        $pdo->quoteTable($this->table),
        $pdo->quoteColumns($this->columns[1]),
        intval($id),
        $pdo->getPlaceholder());
    }

    if(false !== ($sth = $pdo->prepare($sql)))
    {
      if(false !== ($rv = $sth->execute($params)))
        $rv = $sth->rowCount();
    }

    return $rv;
  }

  public function remove_if($id,$key,$value)
  {
    $pdo = $this->pdo;
    $rv = false;

    if(intval($id) > 0 && !empty($key) && !empty($value))
    {
      $ph = $pdo->getPlaceholder();
      $sql = sprintf('DELETE FROM %s WHERE %s = %d AND %s = %s AND %s = %s',
        $pdo->quoteTable($this->table),
        $this->columns[1],
        $id,
        $pdo->quoteColumns('meta_key'),
        $ph,
        $pdo->quoteColumns('meta_value'),
        $ph);


      if(false !== ($sth = $pdo->prepare($sql)))
      {
        if(false !== ($rv = $sth->execute(array($key,$value))))
          $rv = $sth->rowCount();

        $sth = null;
      }
    }

    return $rv;
  }

  //$idのメタデータを全削除
  public function clear($id)
  {
    $pdo = $this->pdo;
    $sql = sprintf('DELETE FROM %s WHERE %s = %d',
      $pdo->quoteTable($this->table),
      $pdo->quoteColumns($this->columns[1]),
      $id);

    return $pdo->exec($sql);
  }

  //$keyのkeyカラムをvalueカラムで集計した結果を連想配列にして返す。
  public function group_count($key)
  {
    $rv = false;
    if(empty($key) || !is_string($key))
      return $rv;

    $rv = array();
    $pdo = $this->pdo;
    $filter = $this->filter;
    $aftertable = add_space_ifnot_empty($filter->fire('group_count-after-table',''));
    $afterquery = add_space_ifnot_empty($filter->fire('group_count-after-query',''));
    $sql = sprintf(
      'SELECT %1$s AS value,COUNT(%1$s) AS length FROM %2$s%5$s WHERE %3$s = %4$s GROUP BY value%6$s',
      $pdo->quoteColumns('meta_value'),
      $pdo->quoteTable($this->table),
      $pdo->quoteColumns('meta_key'),
      $pdo->getPlaceholder(),
      $aftertable,
      $afterquery
    );

    if(false != ($sth = $pdo->prepare($sql)))
    {
      if(false != $sth->execute(array($key)))
      {
        if(false !== ($rows = $sth->fetchAll(PDO::FETCH_ASSOC)))
        {
          foreach($rows as $row_)
            $rv[$row_['value']] = intval($row_['length']);
        }

        $sth->closeCursor();
      }
      $sth = null;
    }

    return $rv;
  }

  private function checkOffset($offset)
  {
    if(!is_numeric($offset) || intval($offset) <= 0)
      throw new Exception(_('$offset is invalid type'));
  }

  // implements ArrayAccess
  // array access returns iterator.
  #[\ReturnTypeWillChange]
  public function offsetExists($offset)
  {
    $this->checkOffset($offset);
    return 0 < $this->count($offset);
  }
  #[\ReturnTypeWillChange]
  public function offsetGet($offset)
  {
    $this->checkOffset($offset);
    return new ArrayIterator($this->gets($offset));
  }
  #[\ReturnTypeWillChange]
  public function offsetSet($offset,$value)
  {
    throw new Exception(_('Not implement offsetSet method.'));
  }
  #[\ReturnTypeWillChange]
  public function offsetUnset($offset)
  {
    $this->checkOffset($offset);
    $this->clear($offset);
  }
}
