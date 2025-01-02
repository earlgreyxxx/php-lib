<?php
/******************************************************************************

  ■メタデータを使用しない、単一テーブルを扱う抽象クラスです。

  ※テーブルの最初のカラムはプライマリー・キーと定義
  ※メタデータを使用する場合は UniversalStore(universal.php)を利用すること。

  ○ 抽出フィルター : gets/length で使用されるフィルター
   and => where句
   orderby => order by句
   order => ソートカラムを指定
   dir   => 降順(desc)か昇順(asc)か？

  All Written by K.,Nakagawa.

******************************************************************************/
abstract class SimpleStore extends Store
{
  /*------------------------------------------------------------------------------
    Statics
  ------------------------------------------------------------------------------*/

  /*
    *arguments
    $pdoex    : [PDOExtension] Object derivered from PDO
    $table    : [string]create table name
    $columns  : [array] column definition
    $indexes  : [array] create index (column1,column2 UNIQUE ASC|DESC)
  ******************************************************/
  public static function CreateTable($pdoex,$table,array $columns,array $indexes = array())
    {
      if(false === $pdoex->createTable($table,$columns))
        throw new Exception(_('create table failed...'));

      if(count($indexes) > 0)
        {
          foreach($indexes as $index)
            {
              $index_ar = explode('|',$index);
              if(count($index_ar) < 2)
                $index_ar[] = '';

              list($columnnames,$unique) = $index_ar;
              $columnnames = trim($columnnames);
              $unique = trim($unique);

              $indexcolumns = explode(',',$columnnames);
              $indexname = 'idx_' . strtolower(preg_replace('/[,\s]+/','_',$columnnames));

              $pdoex->createIndex($table,$indexname,$indexcolumns,$unique ? true : false);
            }
        }
    }

  /*------------------------------------------------------------------------------
    Instances
  ------------------------------------------------------------------------------*/
  //コンストラクタ
  public function __construct($dsn,$table_name,$user='',$passwd='',$options = array())
    {
      //基本クラスのコンストラクタをコール
      parent::__construct($dsn,$table_name,$user,$passwd,$options);
    }

  //非公開メンバ

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

  /*----------------------------------------------------------------------
    登録処理  
  ----------------------------------------------------------------------*/
  protected function add($post,$options = array())
    {
      $rv = false;
      $defaultOptions = array();

      $pdo = $this->dbh;
      $columns = $this->columns;

      if(count($defaultOptions) > 0)
        $options = array_merge($defaultOptions,$options);

      array_shift($columns);
      $params = array();

      foreach($columns as $column)
        $params[] = $post[$column];

      if(false != ($rv = $this->_add($params)))
        {
          $rv = $pdo->lastInsertId();
        }

      return $rv;
    }

  /*----------------------------------------------------------------------
   更新処理

    $options の各キー
    columns => 更新するカラム名を配列にして渡します。通常はすべてのカラムを更新します。
    excludes => 更新の際、更新を除外するカラム名を配列にして渡します。

    columnsとexculues 両方指定することはできません。
    両方指定すると更新せずデータをそのまま返します。
  ----------------------------------------------------------------------*/
  protected function update($id,$post,array $options = array())
    {
      $defaultOptions = array('columns' => null,'excludes' => null);
      $pdo = $this->dbh;
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

      //親クラスの_updateをコール
      if(false == $this->_update($primary_key,$id,$sets))
        {
          return $post;
        }

      return true;
    }

  /*----------------------------------------------------------------------
    削除処理
  ----------------------------------------------------------------------*/
  protected function remove($id,$options = array())
    {
      $rv = false;
      $pdo = $this->dbh;
      if(!is_int($id))
        $id = intval($id);

      $primary_key = $this->columns[0];

      //基本クラスの削除をコール
      if(false === ($rv = $this->_remove($primary_key,$id)))
        {
          return $rv;
        }

      return $rv;
    }

  /*----------------------------------------------------------------------
    ゲッター
    $queries                 => 条件文を指定
    $return_statement_handle => ステートメントハンドルが欲しい場合は true
  ----------------------------------------------------------------------*/
  protected function get($queries = '',$return_statement_handle = false)
    {
      $rv = false;
      $pdo = $this->dbh;

      if(!is_array($queries) && !empty($queries))
        $queries = array($queries);

      $columns = array();

      if(is_array($this->filters['exclude'] ?? '') && !empty($this->filters['exclude'] ?? ''))
        {
          $columns = $this->columns;
          $columns_len = count($columns);

          $tempo = array($columns[0]);
          for($i=1;$i<$columns_len;$i++)
            {
              if(false === array_search($columns[$i],$this->filters['exclude']))
                $tempo[] = $columns[$i];
            }
          $columns = $tempo;
        }

      if(is_array($queries))
        $queries = implode(' ',$queries);

      $sth = $this->fetch($queries,$columns);
      if($sth !== false)
        {
          if($return_statement_handle === true)
            {
              $rv = $sth;
            }
          else
            {
              $rv = $sth->fetchAll(PDO::FETCH_ASSOC);

              //クリーンアップ
              $sth->closeCursor();
              $sth = null;
            }
        }
      else
        {
          $rv = array();
        }

      return  $rv;
    }

  protected function count()
    {
      $filter = empty($this->filters['and']) ? '' : 'where ' . $this->filters['and'];

      return $this->size($filter);
    }

  /**********************************************************************
    ここから公開メンバ
   **********************************************************************/

  public function length()
    {
      return $this->count();
    }

  //POST処理。失敗した時、入力データを返す。成功したらTRUEを返す。
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

  public function delete($id,$options = [])
    {
      $rv = $this->remove(intval($id),$options);
      return $rv > 0 ? true : false;
    }

  //引数：1ページあたりの件数,何ページ目?
  //$numが負であれば全件取得のステートメントハンドルが返る
  public function gets($num,$page = 1)
    {
      $pdo = $this->dbh;

      $offset = ($page - 1)*$num;
      if($offset < 0)
        $offset = 0;

      $queries = array();

      $filter = '';

      if(!empty($this->filters['and']))
        $filter = 'where '.$this->filters['and'];

      if(!empty($filter))
        $queries[] = $filter;

      if(!empty($this->filters['orderby']))
        {
          //orderbyフィルターがある場合はそのまま渡す。
          $cond = 'ORDER BY ' . $this->filters['orderby'];
        }
      else
        {
          //orderbyフィルターが無い場合はorder/dirの各フィルターを適用する。
          $default_order_column = $this->columns[0];
          $cond = sprintf('ORDER BY %s %s',
                          $pdo->quoteColumns(empty($this->filters['order']) ? $default_order_column : $this->filters['order']),
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

  public function getsTR($num,$page = 1)
    {
      $this->gets($num,$page,true);
    }

  //FORM要素内のVALUE属性値に埋めるための値が格納されたハッシュ配列を返す。
  public function get_values($id = 0,$conv = true)
    {
      $r = &get_request();
      $forms = array();
      $primary_key = $this->columns[0];
      $pdo = $this->dbh;

      if(empty($id))
        $id = intval($r['id']);

      if(is_int($id) || $id > 0)
        {
          $queries = array(sprintf('WHERE %s = %d',$pdo->quoteColumns($primary_key),$id));

          $posts = $this->get($queries,false);
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

  public function get_valuesTR($id = 0,$conv = true)
    {
      return $this->get_values($id,$conv);
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

  public function processTR($fn,$query = '',$columns = array())
    {
      $this->process($fn,$query,$columns,true);
    }
}

