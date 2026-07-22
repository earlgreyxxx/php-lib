<?php /**************************************************************************

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
  public static function CreateTable(PDOExtension $pdoex, string $table, array $columns, array $indexes = array()): void
  {
    if (false === $pdoex->createTable($table, $columns))
      throw new Exception(_('create table failed...'));

    if (count($indexes) > 0)
    {
      foreach ($indexes as $index)
      {
        $index_ar = explode('|', $index);
        if (count($index_ar) < 2)
          $index_ar[] = '';

        list($columnnames, $unique) = $index_ar;
        $columnnames = trim($columnnames);
        $unique = trim($unique);

        $indexcolumns = explode(',', $columnnames);
        $indexname = 'idx_' . strtolower(preg_replace('/[,\s]+/', '_', $columnnames));

        $pdoex->createIndex($table, $indexname, $indexcolumns, $unique ? true : false);
      }
    }
  }

  /*------------------------------------------------------------------------------
    Instances
  ------------------------------------------------------------------------------*/
  //コンストラクタ
  public function __construct(string $dsn, string $table_name, string $user = '', string $passwd = '', array $options = [])
  {
    //基本クラスのコンストラクタをコール
    parent::__construct($dsn, $table_name, $user, $passwd, $options);
  }

  //非公開メンバ

  /*----------------------------------------------------------------------
    form_to_post/post_to_formメソッドは派生先でオーバーライド必須。
  ----------------------------------------------------------------------*/
  protected abstract function form_to_post(?array $form = null);
  protected abstract function post_to_form(array $post,string $prefix = '');

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
  protected function add(array $post,array $options = []) : string|false
  {
    $rv = false;
    $defaultOptions = array();

    $pdo = $this->dbh;
    $columns = $this->columns;

    if (count($defaultOptions) > 0)
      $options = array_merge($defaultOptions, $options);

    array_shift($columns);
    $params = [];

    foreach ($columns as $column)
      $params[] = $post[$column];

    if (false != ($rv = $this->_add($params)))
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
  protected function update(int|string $id,array $post,array $options = []) : array|true
  {
    $defaultOptions = [
      'columns' => null,
      'excludes' => null
    ];
    $columns = $this->columns;

    $pkey = array_shift($columns);
    $sets = [];

    $options = array_merge($defaultOptions, $options);

    if (!empty($options['columns']) && !empty($options['excludes']))
      return $post;

    if (is_array($options['columns']) && (count($options['columns']) > 0))
      $columns = $options['columns'];
    else if (is_array($options['excludes']) && (count($options['excludes']) > 0))
      $columns = array_filter($columns,fn($col) => false === array_search($col, $columns));

    foreach ($columns as $column)
    {
      if (array_key_exists($column, $post))
        $sets[$column] = $post[$column];
    }

    //親クラスの_updateをコール
    if (false === $this->_update($pkey, $id, $sets))
      return $post;

    return true;
  }

  /*----------------------------------------------------------------------
    削除処理
  ----------------------------------------------------------------------*/
  protected function remove(int|string $id,array $options = []) : int|false
  {
    $rv = false;
    $pdo = $this->dbh;
    if (!is_int($id))
      $id = intval($id);

    $pkey = array_shift($this->columns);

    //基本クラスの削除をコール
    return $this->_remove($pkey, $id);
  }

  /*----------------------------------------------------------------------
    ゲッター
    $queries                 => 条件文を指定
    $return_statement_handle => ステートメントハンドルが欲しい場合は true
  ----------------------------------------------------------------------*/
  protected function get(string|array $queries = '',bool $return_statement_handle = false) : array|PDOStatement|false
  {
    $rv = [];

    if (!is_array($queries) && !empty($queries))
      $queries = [$queries];

    $columns = [];
    if (is_array($this->filters['exclude'] ?? '') && !empty($this->filters['exclude'] ?? ''))
    {
      $columns = $this->columns;
      $columns_len = count($columns);

      array_shift($columns);
      $columns = array_filter($columns,fn($col) => false === array_search($col, $this->filters['exclude']));
    }

    if (is_array($queries))
      $queries = implode(' ', $queries);

    $sth = $this->fetch($queries, $columns);
    if ($sth && $return_statement_handle === true)
      return $sth;

    if(false === ($rv = $sth->fetchAll(PDO::FETCH_ASSOC)))
      $rv = [];

    $sth->closeCursor();
    $sth = null;

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

  public function length() : int|false
  {
    return $this->count();
  }

  //POST処理。失敗した時、入力データを返す。成功したらTRUEを返す。
  public function post(?array $form = null, array $options = []) : int|false
  {
    if ($form == null)
      $form = &get_post();

    $post = $this->form_to_post($form);
    return $this->add($post, $options);
  }

  public function modify(int|string $id, ?array $form = null, array $options = []) : array|true
  {
    if ($form === null)
      $form = &get_post();

    $post = $this->form_to_post($form);
    return $this->update(intval($id), $post, $options);
  }

  public function delete(int|string $id,array $options = []) : bool
  {
    return boolval($this->remove(intval($id), $options));
  }

  //引数：1ページあたりの件数,何ページ目?
  //$numが負であれば全件取得のステートメントハンドルが返る
  public function gets(int $num,int $page = 1) : array|PDOStatement|false
  {
    $pdo = $this->dbh;

    $offset = ($page - 1) * $num;
    if ($offset < 0)
      $offset = 0;

    $queries = [];
    $filter = '';

    if (!empty($this->filters['and']))
      $filter = 'WHERE ' . $this->filters['and'];

    if (!empty($filter))
      $queries[] = $filter;

    if (!empty($this->filters['orderby']))
    {
      //orderbyフィルターがある場合はそのまま渡す。
      $cond = 'ORDER BY ' . $this->filters['orderby'];
    }
    else
    {
      //orderbyフィルターが無い場合はorder/dirの各フィルターを適用する。
      $default_order_column = $this->columns[0];
      $cond = sprintf(
        'ORDER BY %s %s',
        $pdo->quoteColumns(empty($this->filters['order']) ? $default_order_column : $this->filters['order']),
        empty($this->filters['dir']) ? 'DESC' : $this->filters['dir']
      );

      if (!empty($this->filters['order']) && $this->filters['order'] !==  $default_order_column)
        $cond .= ",$default_order_column desc";
    }

    $queries[] = $cond;

    if ($num > 0)
    {
      $res = $this->dbh->limit($num, $offset, array('src' => &$queries));
      if (!empty($res))
        $queries[] =  $res;
    }

    return $this->get($queries, $num < 0);
  }

  public function getsTR(int $num,int $page = 1) : array|PDOStatement|false
  {
    throw new RuntimeException(_('methodo "getsTR" not implement'));
  }

  //FORM要素内のVALUE属性値に埋めるための値が格納されたハッシュ配列を返す。
  public function get_values(int|string $id = 0,bool $conv = true) : array|false
  {
    $r = &get_request();
    $pkey = $this->columns[0];
    $pdo = $this->dbh;
    
    if (empty($id))
      $id = intval($r['id']);

    if(!is_int($id))
      $id = intval($id);

    if ($id <= 0)
      return false;

    $queries = array(sprintf('WHERE %s = %d', $pdo->quoteColumns($pkey), $id));

    if(false === ($posts = $this->get($queries, false)))
      return false;

    if(empty($posts))
      return [];

    $rv = array_shift($posts);
    if($conv)
      $rv = $this->post_to_form($rv);

    return $rv;
  }

  public function get_valuesTR($id = 0,$conv = true)
  {
    throw new RuntimeException(_('method "get_valuesTR" not implement'));
  }

  //$fn        : 各レコードを処理する関数
  //[$query]   : 条件などのSQL文
  //[$columns] : 取得したいカラム
  public function process(callable $fn,string|array $query = '',array $columns = []) : void
  {
    if (!empty($query) && !is_array($query))
      $query = array($query);

    if (false === ($sth = $this->get($query, true)))
      throw new RuntimeException(_('failed to get data'));

    while (false !== ($row = $sth->fetch(PDO::FETCH_ASSOC)))
    {
      if (false === call_user_func($fn, $row))
        break;
    }

    $sth->closeCursor();
    $sth = null;
  }

  public function processTR($fn,$query = '',$columns = array())
  {
    throw new RuntimeException(_('method "processTR" not implement'));
  }
}