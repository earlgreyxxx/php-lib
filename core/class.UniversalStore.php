<?php
/******************************************************************************

  ■データを保存する一般的なテーブルへのアクセスを提供する抽象クラスです。

  ※テーブルの最初のカラムはプライマリー・キーと定義

  ○ 抽出フィルター : gets/length で使用されるフィルター
   and     => where句
   orderby => order by句
   order   => ソートカラムを指定
   dir     => 降順(desc)か昇順(asc)か？
   exclude => 該当カラムを除外

******************************************************************************/

abstract class UniversalStore extends Store
{
  /*------------------------------------------------------------------------------
    Statics
  ------------------------------------------------------------------------------*/

  /*
    *arguments
    $pdoex    : [PDOExtension] Object derivered from PDO
    $table    : [string]create table name
    $columns  : [array] column definition
    $indexes  : [array] create index (column1 ASC,column2 DESC | UNIQUE)
  ******************************************************/
  public static function CreateTable($pdoex,$table,?array $columns,?array $indexes = null)
    {
      if(false === $pdoex->createTable($table,$columns))
        throw new Exception(_('create table failed...'));

      if(is_array($indexes) && count($indexes) > 0)
        {
          foreach($indexes as $index)
            {
              list($columnnames,$unique) = explode('|',$index);
              $columnnames = trim($columnnames);
              $unique = trim($unique);

              $indexcolumns = explode(',',$columnnames);
              $indexname = 'idx_' . strtolower(preg_replace('/[,\s]+/','_',$columnnames));

              $pdoex->createIndex($table,$indexname,$indexcolumns,$unique ? true : false);
            }
        }

      $table_meta = $table . '_meta';
      $fkey = 'meta_' . strtolower(str_ireplace('store','',get_called_class()));

      Meta::Prepare($pdoex,$table_meta,$fkey);
    }

  /*------------------------------------------------------------------------------
    Instances
  ------------------------------------------------------------------------------*/
  protected $meta;
  protected $table_meta;
  protected $meta_keys = array();
  protected $meta_fkey;

  /*----------------------------------------------------------------------
    form_to_post/post_to_formメソッドは派生先でオーバーライド必須。
  ----------------------------------------------------------------------*/
  protected abstract function form_to_post($form = null);
  protected abstract function post_to_form($post,$prefix = '');

  /*--------------------------------------------------------------------------------------
    以下は必須実装ではない
    PDOでのDBMSへのアクセスでプレースホルダを使用する場合以下をオーバーライド。

    --$column に応じたプレースホルダ文字列を返します。
    protected function getPlaceholder($column);

    --ステートメントハンドル及び、カラム配列/値配列を受け取り、
    --バインド処理(PDOStatement::bindValue)を行います。
    --受取ったステートメントハンドルをそのまま返します。
    protected function bindValues($sth,$columns,$values);

    --また標準実装(static::GetPlaceholderBase,static::BindValuesを使用する場合は、
    --以下の静的変数を定義すること。
    protected static $Placeholders = array(カラム名 => array('?',PDO::PARAM_INT),...
  --------------------------------------------------------------------------------------*/

  //コンストラクタ
  public function __construct($dsn,$table_name,$user='',$passwd='',$options = array())
    {
      //基本クラスのコンストラクタをコール
      parent::__construct($dsn,$table_name,$user,$passwd,$options);

      //メタテーブル定義
      $this->table_meta = $table_name . '_meta';

      //メタデータ
      $this->meta_fkey = $this->get_meta_fkey();
      $this->meta = new Meta($this->dbh,$this->table_meta);
    }

  //非公開メンバ

  // meta_fkey 名を取得する。
  protected function get_meta_fkey()
    {
      if(false === ($meta_columns = $this->dbh->getColumns($this->table_meta)))
        return 'meta_' . strtolower(str_ireplace('store','',get_class($this)));

      return $meta_columns[1];
    }

  //登録処理
  protected function add($post,$options = array())
    {
      $rv = false;
      $defaultOptions = array('columns' => null);

      $pdo = $this->dbh;
      $meta = $this->meta;
      $columns = $this->columns;
      $options = array_merge($defaultOptions,$options);

      $this->beginTransaction();

      array_shift($columns);
      $params = array();

      foreach($columns as $column)
        $params[] = $post[$column];

      if(false != ($rv = $this->_add($params)))
        {
          $rv = $last_id = $pdo->lastInsertId();

          foreach($this->meta_keys as $key)
            {
              if(!empty($post[$key]))
                $meta->set($last_id,$key,$post[$key]);
            }

          $this->commit();
        }
      else
        {
          $this->rollBack();
        }

      return $rv;
    }

  /*------------------------------------------------------------------------------
    更新処理
    ignoreMeta   : trueの時、$post[key]が存在しない場合、値を更新しないようにします。
    multisetMeta : メタテーブルへの格納を重複可能なキーとして扱います。
    columns      : 更新するカラム名を配列にして渡します。通常はすべてのカラムを更新します。
    excludes      : 更新の際、更新を除外するカラム名を配列にして渡します。

    columnsとexculues 両方指定することはできません。
    両方指定すると更新せずデータをそのまま返します。
   ------------------------------------------------------------------------------*/
  protected function update($id,$post,array $options = array())
    {
      $defaultOptions = array('ignoreMeta' => false,
                              'multisetMeta' => false,
                              'columns' => null,
                              'excludes' => null);
      $pdo = $this->dbh;
      $meta = $this->meta;
      $columns = $this->columns;

      $primary_key = array_shift($columns);
      $sets = array();

      $options = array_merge($defaultOptions,$options);

      if(!empty($options['columns']) && !empty($options['excludes']))
        return $post;

      if(is_array($options['columns']) && (count($options['columns']) > 0))
        {
          $columns = $options['columns'];
        }
      else if(is_array($options['excludes']) && (count($options['excludes']) > 0))
        {
          foreach($options['excludes'] as $column)
            {
              if(false !== ($index = array_search($column,$columns)))
                unset($columns[$index]);
            }
        }
      unset($column);

      foreach($columns as $column)
        {
          if(array_key_exists($column,$post))
            $sets[$column] = $post[$column];
        }

      $this->beginTransaction();

      //親クラスの_updateをコール
      if(false == $this->_update($primary_key,$id,$sets))
        {
          $this->rollBack();
          return $post;
        }

      foreach($this->meta_keys as $key)
        {
          if(!isset($post[$key]))
            {
              if($options['ignoreMeta'])
                continue;

              $post[$key] = '';
            }

          $meta->set($id,$key,$post[$key],$options['multisetMeta']);
        }

      $this->commit();

      return true;
    }

  //削除
  protected function remove($id,$options = array())
    {
      $rv = false;
      $pdo = $this->dbh;
      $meta = $this->meta;
      $id = intval($id);
      $primary_key = $this->columns[0];

      //削除・トランザクション開始
      $this->beginTransaction();

      //基本クラスの削除をコール
      if(false != ($rv = $this->_remove($primary_key,$id)))
        {
          $meta->clear($id);

          //削除・トランザクション終了
          $this->commit();
        }
      else
        {
          $this->rollBack();
        }

      return $rv;
    }

  protected function get($queries = '',$return_statement_handle = false)
    {
      if(!is_array($queries) && !empty($queries))
        $queries = array($queries);

      $pdo = $this->dbh;

      $columns = $this->columns;
      $columns_len = count($columns);
      $primary_key = $this->columns[0];

      if(array_key_exists('exclude',$this->filters) && is_array($this->filters['exclude']) && !empty($this->filters['exclude']))
        {
          $tempo = array($columns[0]);
          for($i=1;$i<$columns_len;$i++)
            {
              if(false === array_search($columns[$i],$this->filters['exclude']))
                $tempo[] = $columns[$i];
            }
          $columns = $tempo;
        }

      $columns = $pdo->quoteColumns($columns);
      foreach($this->meta_keys as $key)
        {
          $columns[] = $pdo->columnconcat($this->table_meta,
                                          'meta_key',
                                          'meta_value',
                                          $this->meta_fkey,
                                          $primary_key,
                                          $key);
        }

      $aftertable = add_space_ifnot_empty(do_filter('storeuniversal-get-after-table',''));
      $afterquery = add_space_ifnot_empty(do_filter('storeuniversal-get-after-query',''));

      $sql = sprintf('SELECT DISTINCT %s FROM %s%s LEFT JOIN %s ON %s = %s',
                     implode(',',$columns),
                     $pdo->quoteTable($this->table),
                     $aftertable,
                     $pdo->quoteTable($this->table_meta),
                     $pdo->quoteColumns($primary_key),
                     $pdo->quoteColumns($this->meta_fkey));

      $queries[] = $afterquery;

      if(is_array($queries))
        $sql .= (' ' . implode(' ',$queries));

      $sth = $pdo->query($sql);
      $this->invoke('get',array('query-sql',$sql));

      if($return_statement_handle)
        return $sth;

      return !empty($sth) ? $sth->fetchAll(PDO::FETCH_ASSOC) : array();
    }

  protected function count($query = '')
    {
      $pdo = $this->dbh;

      $columns = $this->columns;
      $columns_len = count($columns);
      $primary_key = $columns[0];

      if(array_key_exists('exclude',$this->filters) && is_array($this->filters['exclude']) && !empty($this->filters['exclude']))
        {
          $tempo = array($columns[0]);
          for($i=1;$i<$columns_len;$i++)
            {
              if(false === array_search($columns[$i],$this->filters['exclude']))
                $tempo[] = $columns[$i];
            }
          $columns = $tempo;
        }

      $columns = $pdo->quoteColumns($columns);
      foreach($this->meta_keys as $key)
        $columns[] = $pdo->columnconcat($this->table_meta,
                                        'meta_key',
                                        'meta_value',
                                        $this->meta_fkey,
                                        $primary_key,
                                        $key);

      $aftertable = add_space_ifnot_empty(do_filter('storeuniversal-count-after-table',''));
      $afterquery = add_space_ifnot_empty(do_filter('storeuniversal-count-after-query',''));

      $sql = sprintf("SELECT DISTINCT %s FROM %s%s LEFT JOIN %s ON %s = %s",
                     implode(',',$columns),
                     $pdo->quoteTable($this->table),
                     $aftertable,
                     $pdo->quoteTable($this->table_meta),
                     $pdo->quoteColumns($primary_key),
                     $pdo->quoteColumns($this->meta_fkey));

      $sql .= (empty($query) ? '' : ' '.$query);
      $sql .= $afterquery;

      $sql = sprintf('SELECT COUNT(*) AS count FROM (%s) AS vt',$sql);

      $rv = false;
      if(false !== ($sth = $pdo->query($sql)))
        {
          $rv = $sth->fetchColumn();
          $sth->closeCursor();
          $sth = null;
        }

      return $rv !== false ? intval($rv) : $rv;
    }

  /**********************************************************************
    ここから公開メンバ
   **********************************************************************/

  //メタデータへのアクセスのためのオブジェクトを返す。
  public function get_meta()
    {
      return $this->meta;
    }

  public function length()
    {
      $filter = empty($this->filters['and']) ? '' : $this->filters['and'];
      $op = $this->dbh->get_cond();

      $query = '';

      if(!empty($filter))
        $query .= $op . $filter;

      return $this->count($query);
    }

  //POST処理。成功したらプライマリキーを返す。
  public function post($form = null,$options = [])
    {
      if($form == null)
        $form = &get_post();

      $post = $this->form_to_post($form);
      return $this->add($post,$options);
    }

  public function modify($id,$form = null,$options = [])
    {
      if($form == null)
        $form = &get_post();

      $post = $this->form_to_post($form);
      return $this->update(intval($id),$post,$options);
    }

  public function delete($id,$options = array())
    {
      $rv = $this->remove(intval($id),$options);
      return $rv > 0 ? true : false;
    }

  //引数：1ページあたりの件数,何ページ目?
  //$numが負であれば全件取得でステートメントハンドルが返る。
  public function gets($num,$page = 1)
    {
      $offset = ($page - 1)*$num;
      if($offset < 0)
        $offset = 0;

      $queries = array();

      $filter = '';

      if(!empty($this->filters['and']))
        $filter = $this->filters['and'];

      if(!empty($filter))
        $queries[] = sprintf('%s %s',
                             $this->dbh->get_cond(),
                             $filter);

      if(!empty($this->filters['orderby']))
        {
          //orderbyフィルターがある場合はそのまま渡す。
          $cond = 'order by ' . $this->filters['orderby'];
        }
      else
        {
          //orderbyフィルターが無い場合はorder/dirの各フィルターを適用する。
          $default_order_column = $this->columns[0];
          $cond = sprintf('ORDER BY %s %s',
                          empty($this->filters['order']) ? $default_order_column : $this->filters['order'],
                          empty($this->filters['dir']) ? 'DESC' : $this->filters['dir']);

          if(!empty($this->filters['order']) && $this->filters['order'] !==  $default_order_column)
            $cond .= ",$default_order_column desc";
        }
      $queries[] = $cond;

      if($num > 0)
        {
          $res = $this->dbh->limit($num,$offset,array('src' => &$queries));
          if(!empty($res))
            $queries[] =  $res;
        }

      return $this->get($queries,$num < 0);
    }


  //FORM要素内のVALUE属性値に埋めるための値が格納されたハッシュ配列を返す。
  public function get_values($id = '',$conv = true)
    {
      $r = &get_request();
      $forms = array();
      $primary_key = $this->columns[0];
      $pdo = $this->dbh;

      if(empty($id))
        $id = htmlspecialchars($r['id']);

      if(is_int($id))
        {
          $queries = array(sprintf('WHERE %s = %d',$pdo->quoteColumns($primary_key),$id));

          $posts = $this->get($queries);
          if(!is_array($posts))
            return false;

          $post = $posts[0];
          if(is_array($post))
            {
              if($conv === true)
                $forms =  $this->post_to_form($post);
              else
                return $post;
            }
        }
      else
        {
          return false;
        }

      return $forms;
    }

  //$fn        : 各レコードを処理する関数
  //[$query]   : 条件などのSQL文
  //[$columns] : 取得したいカラム
  public function process($fn,$query = '',$columns = array())
    {
      if(!empty($query) && !is_array($query))
        $query = array($query);

      if(false !== ($sth = $this->get($query,true)))
        {
          while(false !== ($row = $sth->fetch(PDO::FETCH_ASSOC)))
            {
              if(false === call_user_func($fn,$row))
                break;
            }

          $sth->closeCursor();
          $sth = null;
        }
    }
}

