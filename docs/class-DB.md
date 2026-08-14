# DB クラス API リファレンス

## 概要

`DB` は `PDOExtension` を利用して SQL を組み立て、SELECT / INSERT / UPDATE / DELETE / ストアドプロシージャを実行するクエリビルダークラスです。

多くの設定メソッドは自身 (`DB`) を返すため、メソッドチェーンで記述できます。

```php
$db = DB::CreateInstance($pdo);

$sth = $db
    ->select()
    ->columns(['id', 'name'])
    ->from('users')
    ->where(['status' => 1])
    ->orderby('id DESC')
    ->query();
```

主な機能は次のとおりです。

- SELECT / INSERT / UPDATE / DELETE のSQL生成
- WHERE / IN / IS NULL / IS NOT NULL 条件
- INNER / LEFT / RIGHT / OUTER JOIN
- サブクエリを FROM / JOIN に利用
- GROUP BY / HAVING / DISTINCT / ORDER BY
- LIMIT / OFFSET
- SQL Server の `TOP`
- SQL Server 2008 向け `ROW_NUMBER()` ページング
- Prepared Statement の生成
- `Filter` によるSQL文字列へのフック
- COUNT / DISTINCT COUNT 等の静的ヘルパー
- Stored Procedure 呼び出し

---

# 依存クラス

このクラスは少なくとも以下のクラス・機能に依存します。

| 名前 | 用途 |
|---|---|
| `PDOExtension` | PDO接続、識別子のクォート、LIMIT生成、Stored Procedure等 |
| `PDOStatement` | SQL実行結果およびPrepared Statement |
| `Filter` | SQL生成時のフィルター処理 |
| `Generator` | `getIterator()` の戻り値 |
| `RuntimeException` / `Exception` | エラー通知 |

---

# SQL生成モード

以下のメソッドを呼び出すことで、内部のSQL生成モードが切り替わります。

| メソッド | モード | 主な実行メソッド |
|---|---|---|
| `select()` | `select` | `query()`, `prepare()`, `getQuery()` |
| `insert()` | `insert` | `query()`, `exec()`, `prepare()`, `getQuery()` |
| `update()` | `update` | `query()`, `exec()`, `prepare()`, `getQuery()` |
| `delete()` | `delete` | `query()`, `exec()`, `prepare()`, `getQuery()` |
| `procedure()` | `procedure` | `query()`, `exec()`, `getQuery()` |

モード未設定のまま `query()` / `prepare()` / `exec()` / `getQuery()` を呼ぶと例外が発生します。

---

# Static プロパティ

## `DB::$SQLSERVER_IS_2008`

```php
public static bool $SQLSERVER_IS_2008 = false;
```

SQL Server 2008互換モードの既定値です。

コンストラクタの `$options['sqlserver2008']` が `true` の場合はインスタンス単位で有効になります。それ以外の場合はこのstaticプロパティの値が利用されます。

```php
DB::$SQLSERVER_IS_2008 = true;

$db = new DB($pdo);
```

---

# Static メソッド

## `SetErrorInfo()`

```php
public static function SetErrorInfo(mixed $mixed) : void
```

クラス共通のエラー情報配列へ値を追加します。

### 引数

| 引数 | 型 | 説明 |
|---|---|---|
| `$mixed` | `mixed` | 保存するエラー情報 |

### 戻り値

なし。

### 例

```php
DB::SetErrorInfo('database error');
DB::SetErrorInfo($exception);
```

---

## `ErrorInfo()`

```php
public static function ErrorInfo() : array
```

`SetErrorInfo()` で蓄積されたエラー情報を返します。

### 戻り値

`array` — 保存済みのエラー情報。

```php
$errors = DB::ErrorInfo();
```

> 現行実装にはエラー情報を消去する専用メソッドはありません。

---

## `CreateInstance()`

```php
public static function CreateInstance(
    PDOExtension $pdo,
    array $options = []
) : DB
```

`DB` インスタンスを生成するFactoryメソッドです。

内部では `new static(...)` を使用するため、継承クラスから呼び出した場合はLate Static Bindingが有効です。

### 引数

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$pdo` | `PDOExtension` | — | DB接続オブジェクト |
| `$options` | `array` | `[]` | コンストラクタオプション |

### 戻り値

生成された `DB` インスタンス。

```php
$db = DB::CreateInstance($pdo);
```

---

## `Union()`

```php
public static function Union(
    PDOExtension $pdo,
    array $dbs,
    bool $hasAll = false,
    ?array $addtions = null
) : bool|PDOStatement
```

複数の `DB` オブジェクトが生成するSQLを `UNION` または `UNION ALL` で結合し、直ちに実行します。

### 引数

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$pdo` | `PDOExtension` | — | UNION SQLを実行する接続 |
| `$dbs` | `array` | — | `DB` オブジェクトの配列 |
| `$hasAll` | `bool` | `false` | `true` なら `UNION ALL` |
| `$addtions` | `?array` | `null` | UNION SQL末尾へ追加するSQL断片 |

### 戻り値

- 成功時: `PDOStatement`
- 有効なクエリがない場合など: `false`

### 例

```php
$db1 = DB::CreateInstance($pdo)
    ->select()
    ->columns(['id', 'name'])
    ->from('users_a');

$db2 = DB::CreateInstance($pdo)
    ->select()
    ->columns(['id', 'name'])
    ->from('users_b');

$sth = DB::Union(
    $pdo,
    [$db1, $db2],
    true,
    ['ORDER BY id']
);
```

生成イメージ:

```sql
SELECT ... FROM ... UNION ALL SELECT ... FROM ... ORDER BY id
```

---

## `bindValues()`

```php
public static function bindValues(
    PDOStatement $sth,
    array $values
) : bool|PDOStatement
```

Prepared Statementへ複数の値を `bindValue()` します。

### `$values` の形式

通常値の場合は型が自動判定されます。

```php
[
    ':id'   => 10,
    ':name' => 'Nakagawa',
    ':memo' => null,
]
```

明示的にPDO型を指定する場合:

```php
[
    ':id' => [10, PDO::PARAM_INT],
]
```

### 型判定

| PHP値 | PDO型 |
|---|---|
| `int` | `PDO::PARAM_INT` |
| `null` | `PDO::PARAM_NULL` |
| その他 | `PDO::PARAM_STR` |

数値キーの場合は `1` 始まりの位置プレースホルダへ変換されます。

### 戻り値

- 全てのbindに成功: `$sth`
- `bindValue()` が失敗: `false`

---

## `Count()`

```php
public static function Count(
    PDOExtension $pdo,
    string $table,
    string $count_column = '*',
    string|array|null $conditions = null,
    bool $is_prepared = false
) : mixed
```

指定テーブルの件数を取得します。

### 引数

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$pdo` | `PDOExtension` | — | DB接続 |
| `$table` | `string` | — | 対象テーブル |
| `$count_column` | `string` | `'*'` | COUNT対象列 |
| `$conditions` | `string\|array\|null` | `null` | `where()` に渡す条件 |
| `$is_prepared` | `bool` | `false` | `true` なら実行せずPrepared Statementを返す |

### 戻り値

- 通常: `fetchColumn()` の結果
- `$is_prepared === true`: `PDOStatement|false`

### 例

```php
$count = DB::Count(
    $pdo,
    'users',
    '*',
    ['enabled' => 1]
);
```

### 例外

- テーブルが存在しない場合: `RuntimeException`
- SQL実行失敗: `RuntimeException`

---

## `DistinctCount()`

```php
public static function DistinctCount(
    PDOExtension $pdo,
    string $table,
    string $count_column,
    string|array|null $conditions = null,
    bool $is_prepared = false
) : mixed
```

`COUNT(DISTINCT column)` を実行します。

### 注意

`$count_column` に `'*'` は指定できません。

### 戻り値

- 通常: `int`
- Prepared指定時: `PDOStatement|false`

### 例

```php
$count = DB::DistinctCount(
    $pdo,
    'orders',
    'customer_id',
    ['status' => 1]
);
```

---

## `GetID()`

```php
public static function GetID(
    PDOExtension $pdo,
    string $table,
    string $column,
    mixed $value
) : mixed
```

指定列が指定値に一致する行について、SELECT結果の先頭列を返します。

### 例

```php
$id = DB::GetID(
    $pdo,
    'users',
    'email',
    'user@example.com'
);
```

内部的には概ね次の条件を生成します。

```sql
WHERE email = 'user@example.com'
```

### 戻り値

`PDOStatement::fetchColumn()` の値。

### 例外

SQL実行に失敗すると `RuntimeException`。

---

## `getIterator()`

```php
public static function getIterator(
    PDOStatement $sth,
    int $fetchType = PDO::FETCH_ASSOC
) : Generator
```

`PDOStatement` を1行ずつ読み取る `Generator` を返します。

### 例

```php
$sth = $db->select()->from('users')->query();

foreach (DB::getIterator($sth) as $row) {
    var_dump($row);
}
```

読み取り終了時に `closeCursor()` が呼ばれます。

---

# コンストラクタ

## `__construct()`

```php
public function __construct(
    PDOExtension $pdo,
    array $options = []
)
```

DBビルダーを初期化します。

### `$options`

| キー | 型 | 説明 |
|---|---|---|
| `filter` | `Filter` | 利用するFilterオブジェクト |
| `sqlserver2008` | `bool` | SQL Server 2008互換ページングを有効化 |

### 例

```php
$db = new DB($pdo);
```

```php
$db = new DB($pdo, [
    'filter' => $filter,
    'sqlserver2008' => true,
]);
```

`filter` が指定されない場合、新しい `Filter` が自動生成されます。

---

# クォート関連

## `quoteColumns()`

```php
public function quoteColumns(mixed $column) : mixed
```

`PDOExtension::quoteColumns()` のラッパーです。

```php
$quoted = $db->quoteColumns('user_id');
```

---

## `quoteTable()`

```php
public function quoteTable(string $table) : string
```

`PDOExtension::quoteTable()` のラッパーです。

---

## `quote()`

```php
public function quote(string $str) : string
```

`PDOExtension::quote()` のラッパーです。

主にSQL文字列値をクォートするために使用します。

---

# Filter

## `getFilter()`

```php
public function getFilter() : Filter
```

現在利用している `Filter` を返します。

---

## `attachFilter()`

```php
public function attachFilter(Filter $filter) : Filter|null
```

利用する `Filter` を差し替えます。

### 戻り値

差し替え前の `Filter`。未設定の場合は `null`。

```php
$oldFilter = $db->attachFilter($newFilter);
```

---

# SELECT列

## `columns()`

```php
public function columns(
    string|array $columns = '*',
    bool $is_quoted = false
) : DB
```

SELECT対象列、またはINSERT対象列を設定します。

### 例

```php
$db->columns(['id', 'name', 'email']);
```

```php
$db->columns('*');
```

SQL式をそのまま使用する場合:

```php
$db->columns('COUNT(*)', true);
```

### `$is_quoted`

配列を渡した場合、`false` なら `PDOExtension::quoteColumns()` による識別子クォートを行います。

`true` の場合は配列要素をそのままカンマ結合します。

### 戻り値

`$this`

---

## `columnsAs()`

```php
public function columnsAs(array $columns) : DB
```

列とaliasをまとめて設定します。

### 入力形式

```php
[
    ['user_id', 'id'],
    ['user_name', 'name'],
]
```

生成イメージ:

```sql
"user_id" AS id,
"user_name" AS name
```

### 戻り値

`$this`

> 現行実装は各要素を配列として扱う前提です。

---

# WHERE条件

## `where()`

```php
public function where(
    mixed $cond,
    string $operator = 'AND'
) : DB
```

WHERE条件を追加します。

文字列または配列を受け取れます。

### 文字列指定

```php
$db->where('age >= 20');
```

複数回呼び出すと論理演算子で連結されます。

```php
$db
    ->where('age >= 20')
    ->where('enabled = 1', 'AND');
```

### 配列指定

配列の場合は `wheres()` に委譲されます。

```php
$db->where([
    'status' => 1,
    'type'   => 'admin',
]);
```

### 戻り値

`$this`

---

## `wheres()`

```php
public function wheres(
    array $cv,
    string $operator = 'AND',
    bool $is_quoted = false,
    bool $is_value_quoted = false
) : DB
```

複数条件を一度に設定します。

## 単純形式

```php
$db->wheres([
    'status' => 1,
    'name'   => 'Nakagawa',
]);
```

概念的には次のSQLになります。

```sql
WHERE status = 1
  AND name = 'Nakagawa'
```

## 詳細形式

値を配列にすると演算子などを指定できます。

```php
[
    'age' => ['>=', 20],
]
```

現行実装で使用される配列位置は次のとおりです。

| index | 内容 |
|---:|---|
| `0` | 比較演算子 |
| `1` | 値 |
| `2` | 列名を既にクォート済みとして扱うか |
| `3` | 値を既にSQL表現済みとして扱うか |
| `4` | 条件連結演算子 `AND` / `OR` |

例:

```php
$db->wheres([
    'age' => ['>=', 20, false, false, 'AND'],
    'deleted_at' => ['IS', 'NULL', false, true, 'AND'],
]);
```

## プレースホルダ

値として `?` または `:` で始まる文字列を指定すると、その値はPDOによる文字列クォートを行わずSQLへ配置されます。

```php
$db->wheres([
    'id'   => ':id',
    'name' => ':name',
]);
```

その後:

```php
$sth = $db->select()->from('users')->prepare();

DB::bindValues($sth, [
    ':id'   => 10,
    ':name' => 'Nakagawa',
]);

$sth->execute();
```

### 戻り値

`$this`

### 例外

詳細形式として空配列を指定した場合 `RuntimeException`。

---

## `isNull()`

```php
public function isNull(
    string $column,
    bool $is_quoted = false,
    string $operator = 'AND'
) : DB
```

`column IS NULL` 条件を追加します。

```php
$db->isNull('deleted_at');
```

---

## `isNotNull()`

```php
public function isNotNull(
    string $column,
    bool $is_quoted = false,
    string $operator = 'AND'
) : DB
```

`column IS NOT NULL` 条件を追加します。

```php
$db->isNotNull('updated_at');
```

---

## `in()`

```php
public function in(
    string $column,
    array $elements,
    bool $is_quoted = false
) : DB
```

`IN (...)` 条件を追加します。

```php
$db->in('id', [1, 2, 3]);
```

生成イメージ:

```sql
WHERE id IN (1,2,3)
```

文字列値:

```php
$db->in('status', ['new', 'done']);
```

`$is_quoted === false` の場合、列名および文字列要素は `PDOExtension` を利用してクォートされます。

---

# SQLの生成・実行

## `prepare()`

```php
public function prepare() : PDOStatement|false
```

現在のモードに対応するSQLを生成し、`PDOExtension::prepare()` を呼びます。

### 例

```php
$sth = $db
    ->select()
    ->from('users')
    ->where(['id' => ':id'])
    ->prepare();

$sth->bindValue(':id', 10, PDO::PARAM_INT);
$sth->execute();
```

### 例外

モードが未設定の場合:

```text
query mode is not defined
```

---

## `query()`

```php
public function query() : PDOStatement|false
```

SQLを生成して `PDOExtension::query()` で実行します。

```php
$sth = $db
    ->select()
    ->from('users')
    ->query();
```

### 戻り値

`PDOStatement|false`

---

## `exec()`

```php
public function exec() : int|false
```

SQLを生成して `PDOExtension::exec()` で実行します。

INSERT / UPDATE / DELETE 等で更新行数を取得する用途を想定します。

```php
$count = $db
    ->update()
    ->table('users')
    ->set('enabled', 0)
    ->where(['id' => 10])
    ->exec();
```

### 戻り値

PDOの `exec()` と同様に、影響行数または `false`。

---

## `queryAndFetchAll()`

```php
public function queryAndFetchAll(
    int $method = PDO::FETCH_BOTH
) : array|false
```

`query()` 実行後、その結果を `fetchAll()` して返します。

```php
$rows = $db
    ->select()
    ->from('users')
    ->queryAndFetchAll(PDO::FETCH_ASSOC);
```

---

## `getQuery()`

```php
public function getQuery() : bool|string
```

SQLを実行せず、生成されたSQL文字列だけを返します。

```php
$sql = $db
    ->select()
    ->from('users')
    ->where(['id' => 10])
    ->getQuery();
```

デバッグやサブクエリ生成に利用できます。

---

# SELECT

## `select()`

```php
public function select(
    ?string $columns = null,
    bool $is_quoted = false
) : DB
```

SQL生成モードをSELECTへ設定します。

列名を同時指定することもできます。

```php
$db->select();
```

```php
$db->select('COUNT(*)', true);
```

---

## `from()`

```php
public function from(
    string $table,
    string $alias = '',
    bool $is_quoted = false
) : DB
```

FROM句へテーブルを追加します。

複数回呼ぶとカンマ区切りのFROM句になります。

```php
$db->from('users', 'u');
```

`$is_quoted === false` の場合、テーブル名は `quoteTable()` されます。

---

## `fromAs()`

```php
public function fromAs(
    DB $db,
    string $alias
) : DB
```

別の `DB` オブジェクトが生成するSELECTをサブクエリとしてFROM句へ追加します。

```php
$sub = DB::CreateInstance($pdo)
    ->select()
    ->from('orders')
    ->where(['status' => 1]);

$db = DB::CreateInstance($pdo)
    ->select()
    ->fromAs($sub, 'o');
```

生成イメージ:

```sql
SELECT *
FROM (
    SELECT * FROM orders WHERE status = 1
) o
```

### 例外

- サブクエリが空
- aliasが空

---

# JOIN

## `join()`

```php
public function join(
    string $table,
    mixed $condition,
    string $type = 'INNER'
) : DB
```

任意JOINを追加します。

### 文字列条件

```php
$db->join(
    'orders o',
    'u.id = o.user_id',
    'LEFT'
);
```

### 配列条件

```php
$db->join('orders o', [
    'u.id' => 'o.user_id',
]);
```

### callable

`$condition` には callable も指定できます。

```php
$db->join('orders o', function () {
    return 'u.id = o.user_id';
});
```

---

## `joinAs()`

```php
public function joinAs(
    DB $db,
    string $alias,
    string $condition,
    string $type = 'INNER'
) : DB
```

別の `DB` クエリをサブクエリとしてJOINします。

```php
$db->joinAs(
    $subQuery,
    's',
    's.user_id = u.id',
    'LEFT'
);
```

---

## `innerJoin()`

```php
public function innerJoin(
    string $table,
    string $column1,
    string $column2
) : DB
```

2列の等価条件による `INNER JOIN`。

```php
$db->innerJoin(
    'orders',
    'users.id',
    'orders.user_id'
);
```

---

## `leftJoin()`

```php
public function leftJoin(
    string $table,
    string $column1,
    string $column2
) : DB
```

2列の等価条件による `LEFT JOIN`。

---

## `rightJoin()`

```php
public function rightJoin(
    string $table,
    string $column1,
    string $column2
) : DB
```

2列の等価条件による `RIGHT JOIN`。

---

## `outerJoin()`

```php
public function outerJoin(
    string $table,
    string $column1,
    string $column2
) : DB
```

2列の等価条件による `OUTER JOIN`。

> DB製品によって `OUTER JOIN` 単独の構文サポート状況が異なるため、利用先DBのSQL仕様に依存します。

---

# 集約・並び替え

## `orderby()`

```php
public function orderby(
    string $orderby,
    ?int $sqlsrv_num = 0,
    ?int $sqlsrv_offset = 0
) : DB
```

ORDER BY句を追加します。

```php
$db->orderby('created_at DESC');
```

複数回指定できます。

```php
$db
    ->orderby('status ASC')
    ->orderby('created_at DESC');
```

## SQL Server OFFSET/FETCH

第2・第3引数を指定すると、ORDER BYにSQL Server形式のOFFSET/FETCHを追加します。

```php
$db->orderby('id', 20, 40);
```

生成イメージ:

```sql
ORDER BY id
OFFSET 40 ROWS
FETCH NEXT 20 ROWS ONLY
```

---

## `groupby()`

```php
public function groupby(string $groupby) : DB
```

GROUP BY句を設定します。

```php
$db->groupby('category_id');
```

---

## `having()`

```php
public function having(
    string $cond,
    string $operator = 'AND'
) : DB
```

HAVING条件を追加します。

複数回呼ぶと指定演算子で連結されます。

```php
$db
    ->having('COUNT(*) >= 10')
    ->having('SUM(price) > 10000', 'AND');
```

---

## `distinct()`

```php
public function distinct() : DB
```

SELECTへ `DISTINCT` を追加します。

```php
$db
    ->select()
    ->distinct()
    ->columns('category_id')
    ->from('products');
```

---

## `top()`

```php
public function top(int|string $num) : DB
```

SQL Server用 `TOP (n)` を追加します。

```php
$db
    ->select()
    ->top(10)
    ->from('users');
```

生成イメージ:

```sql
SELECT TOP (10) *
FROM users
```

### 例外

接続DBのprefixが次のいずれでもない場合は `RuntimeException`。

- `sqlsrv`
- `dblib`

数値でない文字列を指定した場合も `RuntimeException`。

---

# ページング

## `limit()`

```php
public function limit(
    int $num,
    int $offset
) : DB
```

取得件数とオフセットを設定します。

実際のSQL構文生成は `PDOExtension::limit()` に委譲されます。

```php
$db
    ->select()
    ->from('users')
    ->limit(20, 40);
```

`$num <= 0` の場合は設定されません。

---

## `slice()`

```php
public function slice(
    int $offset,
    int $num = 0
) : mixed
```

ページングを設定し、その場でSELECTクエリを実行します。

一般DBとSQL Server 2008互換モードで挙動が異なります。

## 通常モード

概ね以下を行います。

```php
$this->limit($num, $offset);
$this->select()->query();
```

## SQL Server 2008互換モード

`ROW_NUMBER() OVER(ORDER BY ...)` を使用してページングします。

このモードでは先に `orderby()` を呼ぶ必要があります。

```php
$sth = $db
    ->from('users')
    ->orderby('id')
    ->slice(0, 20);
```

### `$offset`

実装内部で最初に `++$offset` され、SQL Server 2008のROW_NUMBER条件は1始まりとして扱われます。

### `$num`

- `0`: 指定位置以降
- `> 0`: 指定件数分

### 例外

SQL Server 2008モードでORDER BY未指定の場合:

```text
call orderby() first.
```

---

# INSERT

## `insert()`

```php
public function insert() : DB
```

SQL生成モードをINSERTに設定します。

---

## `into()`

```php
public function into(
    string $table,
    bool $is_quoted = false
) : DB
```

INSERT先テーブルを設定します。

```php
$db
    ->insert()
    ->into('users');
```

---

## `values()`

```php
public function values(
    string|array|DB $values,
    bool $validate = false
) : DB
```

INSERTする値を追加します。

### 配列指定

```php
$db
    ->insert()
    ->into('users')
    ->columns(['name', 'age'])
    ->values(['Nakagawa', 54], true);
```

`$validate === true` の場合、配列内の値は次のように変換されます。

| 値 | 変換 |
|---|---|
| `string` | `$pdo->quote()` |
| `null` | SQL文字列 `null` |
| `bool` | `0` / `1` |
| その他 | そのまま |

### 複数行

`values()` を複数回呼べます。

```php
$db
    ->values(['Alice', 20], true)
    ->values(['Bob', 30], true);
```

### DBオブジェクト

`DB` を渡すと、INSERT ... SELECT 用のクエリ値として扱う設計です。

```php
$db->values($selectDb);
```

### 注意

既に文字列クエリが値として設定されている状態で上書きしようとすると `RuntimeException`。

---

# UPDATE

## `update()`

```php
public function update() : DB
```

SQL生成モードをUPDATEに設定します。

---

## `table()`

```php
public function table(
    string $table,
    bool $is_quoted = false
) : DB
```

`updateTable()` のエイリアスです。

```php
$db
    ->update()
    ->table('users');
```

---

## `updateTable()`

```php
public function updateTable(
    string $table,
    bool $is_quoted = false
) : DB
```

UPDATE対象テーブルを設定します。

---

## `set()`

```php
public function set(
    string $column,
    mixed $value,
    bool $is_column_quoted = false
) : DB
```

UPDATEのSET句を1項目追加します。

```php
$db->set('name', $pdo->quote('Nakagawa'));
```

### 重要

`$value` はこのメソッド内部では自動クォートされません。

そのため、文字列リテラルを直接セットする場合は呼び出し側でクォートするか、プレースホルダを利用します。

```php
$db->set('name', ':name');
```

---

## `sets()`

```php
public function sets(
    array $cv,
    bool $is_column_quoted = false
) : DB
```

複数のSET項目を追加します。

```php
$db->sets([
    'name'       => ':name',
    'updated_at' => 'CURRENT_TIMESTAMP',
]);
```

内部では各項目について `set()` を呼びます。

---

# DELETE

## `delete()`

```php
public function delete() : DB
```

SQL生成モードをDELETEに設定します。

DELETE対象テーブルは `from()` で指定します。

```php
$count = $db
    ->delete()
    ->from('users')
    ->where(['id' => 10])
    ->exec();
```

---

# Stored Procedure

## `procedure()`

```php
public function procedure(
    string $procedure_name,
    mixed ...$vars
) : DB
```

Stored Procedure呼び出しモードへ設定します。

```php
$db->procedure(
    'sp_update_user',
    10,
    'Nakagawa'
);
```

引数は可変長引数として内部へ保存されます。

実際の処理は `PDOExtension::procedure()` へ委譲されます。

---

# SQL Server 2008状態

## `isSQLServer2008()`

```php
public function isSQLServer2008() : bool
```

現在のインスタンスがSQL Server 2008互換モードかどうかを返します。

```php
if ($db->isSQLServer2008()) {
    // SQL Server 2008用処理
}
```

---

# Filterフック

SQL生成時、次のFilterイベントが呼び出されます。

| Filter名 | 呼び出し位置 |
|---|---|
| `select-after-table` | SELECTのFROM句直後 |
| `select-after-query` | SELECT文末 |
| `insert-after-query` | INSERT文末 |
| `update-after-query` | UPDATE文末 |
| `delete-after-query` | DELETE文末 |

例:

```php
$filter = $db->getFilter();

$filter->append(
    'select-after-query',
    function ($sql) {
        return $sql . ' FOR UPDATE';
    }
);
```

Filterの正確なcallback仕様は `Filter` クラス側の実装に依存します。

---

# protected / private メソッド

以下は主にSQL生成内部で利用されるメソッドです。

## `appendColumn()`

```php
protected function appendColumn(
    string $column,
    bool $is_quoted = false
) : DB
```

内部の列リスト末尾へ列を追加します。

`$is_quoted === false` の場合は `quoteColumns()` されます。

---

## `prependColumn()`

```php
protected function prependColumn(
    string $column,
    bool $is_quoted = false
) : DB
```

内部の列リスト先頭へ列を追加します。

SQL Server 2008用 `ROW_NUMBER()` ページング等で使用されます。

---

## `_where()`

```php
private function _where(
    string $cond,
    string $operator = 'AND'
) : DB
```

WHERE内部文字列を追加します。

既存条件がある場合:

```sql
<existing> AND <new>
```

のように連結します。

---

## `imp_isNull()`

```php
private function imp_isNull(
    string $column,
    bool $is_not = false,
    bool $is_quoted = false,
    string $operator = 'AND'
) : DB
```

`IS NULL` / `IS NOT NULL` 共通実装です。

---

## `_join()`

```php
protected function _join(
    string $table,
    mixed $condition,
    string $which = 'INNER'
) : DB
```

JOIN SQLを内部配列へ追加します。

`join()` および各種JOINメソッドの共通実装です。

---

## `_joinOnSubQuery()`

```php
protected function _joinOnSubQuery(
    string $subquery,
    string $alias,
    string $condition,
    string $which = 'INNER'
) : DB
```

サブクエリに対するJOIN SQLを生成します。

---

## `_joinWithId()`

```php
protected function _joinWithId(
    string $table,
    string $column1,
    string $column2,
    string $which = 'INNER'
) : DB
```

2列の等価条件を生成して `_join()` を呼びます。

---

## `_select()`

```php
protected function _select() : string
```

現在の内部状態からSELECT SQLを生成します。

生成順序は概ね次のとおりです。

```text
SELECT
TOP
DISTINCT
columns
FROM
select-after-table filter
JOIN
WHERE
GROUP BY
HAVING
ORDER BY
OFFSET / FETCH
LIMIT
select-after-query filter
```

FROM未指定の場合は例外になります。

---

## `_insert()`

```php
protected function _insert() : string
```

INSERT SQLを生成します。

概ね次の形式です。

```sql
INSERT INTO table
(columns...)
VALUES
(...)
```

またはINSERT ... SELECT形式のSQLを構成します。

---

## `queryValues()`

```php
protected function queryValues(
    string|DB $values
) : DB
```

INSERTの値としてSELECTクエリを登録するための内部メソッドです。

---

## `_update()`

```php
protected function _update() : string
```

UPDATE SQLを生成します。

```sql
UPDATE table
SET column = value, ...
WHERE ...
```

テーブル未指定の場合は例外になります。

---

## `_delete()`

```php
protected function _delete() : string
```

DELETE SQLを生成します。

```sql
DELETE
FROM table
WHERE ...
```

FROM未指定の場合は例外になります。

---

## `_procedure()`

```php
protected function _procedure() : mixed
```

`PDOExtension::procedure()` を呼び出します。

### 例外

- procedure未設定
- `PDOExtension` に `procedure()` が実装されていない

---

# 使用例

## SELECT

```php
$rows = DB::CreateInstance($pdo)
    ->select()
    ->columns([
        'id',
        'name',
        'email',
    ])
    ->from('users')
    ->where([
        'enabled' => 1,
    ])
    ->orderby('id DESC')
    ->queryAndFetchAll(PDO::FETCH_ASSOC);
```

---

## Prepared SELECT

```php
$db = DB::CreateInstance($pdo)
    ->select()
    ->from('users')
    ->where([
        'id' => ':id',
    ]);

$sth = $db->prepare();

DB::bindValues($sth, [
    ':id' => [10, PDO::PARAM_INT],
]);

$sth->execute();

$row = $sth->fetch(PDO::FETCH_ASSOC);
```

---

## JOIN

```php
$sql = DB::CreateInstance($pdo)
    ->select()
    ->columns([
        'u.id',
        'u.name',
        'o.id',
    ])
    ->from('users', 'u')
    ->leftJoin(
        'orders o',
        'u.id',
        'o.user_id'
    )
    ->where([
        'u.enabled' => 1,
    ])
    ->getQuery();
```

---

## GROUP BY

```php
$sql = DB::CreateInstance($pdo)
    ->select('COUNT(*)', true)
    ->from('orders')
    ->groupby('customer_id')
    ->having('COUNT(*) >= 5')
    ->orderby('COUNT(*) DESC')
    ->getQuery();
```

---

## INSERT

```php
$count = DB::CreateInstance($pdo)
    ->insert()
    ->into('users')
    ->columns([
        'name',
        'age',
    ])
    ->values([
        'Nakagawa',
        54,
    ], true)
    ->exec();
```

---

## UPDATE + Prepared Statement

```php
$db = DB::CreateInstance($pdo)
    ->update()
    ->table('users')
    ->sets([
        'name'       => ':name',
        'updated_at' => 'CURRENT_TIMESTAMP',
    ])
    ->where([
        'id' => ':id',
    ]);

$sth = $db->prepare();

DB::bindValues($sth, [
    ':name' => 'Nakagawa',
    ':id'   => [10, PDO::PARAM_INT],
]);

$sth->execute();
```

---

## DELETE

```php
$count = DB::CreateInstance($pdo)
    ->delete()
    ->from('users')
    ->where([
        'id' => 10,
    ])
    ->exec();
```

---

## IN条件

```php
$sql = DB::CreateInstance($pdo)
    ->select()
    ->from('users')
    ->in('id', [1, 2, 3, 4])
    ->getQuery();
```

---

## NULL判定

```php
$sql = DB::CreateInstance($pdo)
    ->select()
    ->from('users')
    ->isNull('deleted_at')
    ->isNotNull('created_at')
    ->getQuery();
```

---

## サブクエリ

```php
$sub = DB::CreateInstance($pdo)
    ->select()
    ->columns(['user_id'])
    ->from('orders')
    ->where([
        'status' => 1,
    ]);

$sql = DB::CreateInstance($pdo)
    ->select()
    ->fromAs($sub, 'active_orders')
    ->getQuery();
```

---

# 実装上の注意事項

## 1. 値のクォートとプレースホルダ

このクラスではメソッドごとに値のクォート方法が異なります。

特に `set()` は値を自動的にクォートしません。

安全性と可読性を考慮すると、外部入力を使用する場合はPrepared Statementとプレースホルダを利用する方が扱いやすくなります。

```php
$db
    ->set('name', ':name')
    ->where(['id' => ':id']);
```

---

## 2. `where()` に生SQL文字列を渡せる

```php
$db->where('price > 1000');
```

自由度が高い一方、外部入力を文字列連結して渡す場合はSQLインジェクションに注意が必要です。

---

## 3. `wheres()` の詳細形式

ソース内コメントと現行実装には古い引数説明が混在しています。

現行コードが実際に展開している主要要素は次の5項目です。

```php
[
    operator,
    value,
    column_quoted,
    value_quoted,
    logical_operator,
]
```

---

## 4. INSERT ... SELECT の内部実装

`values()` に `DB` インスタンスを渡した場合、`queryValues()` へ処理が移ります。

現行コードでは `DB::getQuery()` というstatic形式の呼び出しが記述されているため、PHPバージョンによっては非staticメソッド呼び出しとして問題になる可能性があります。

利用時は実環境での動作確認が必要です。

---

## 5. Stored Procedure初期化部分

`procedure()` の内部初期化条件には、`procedure` ではなく別キーを参照している箇所があります。

通常利用で毎回新規 `stdClass` が生成される方向に働く可能性がありますが、仕様として依存する場合は実装確認を推奨します。

---

## 6. `slice()` は実行まで行う

`limit()` がビルダー状態を変更して `$this` を返すのに対して、`slice()` は最終的に `query()` を呼び出し、SQLを実行します。

したがって次の2つは役割が異なります。

```php
$db->limit(20, 0);   // DBを返す
```

```php
$sth = $db->slice(0, 20); // クエリを実行
```

---

## 7. DB固有SQL

次の処理はDB製品依存です。

- `top()`
- SQL Server 2008用 `slice()`
- `orderby()` の OFFSET / FETCH
- `PDOExtension::limit()`
- 識別子のクォート形式
- Stored Procedure

最終SQLは `getQuery()` で確認できます。

```php
echo $db->getQuery();
```

---

# メソッド一覧

## Static

```text
SetErrorInfo()
ErrorInfo()
CreateInstance()
Union()
bindValues()
Count()
DistinctCount()
GetID()
getIterator()
```

## 基本・共通

```text
__construct()
quoteColumns()
quoteTable()
quote()
getFilter()
attachFilter()
columns()
columnsAs()
where()
wheres()
isNull()
isNotNull()
in()
prepare()
query()
exec()
queryAndFetchAll()
getQuery()
```

## SELECT / JOIN / 集約

```text
select()
from()
fromAs()
joinAs()
join()
innerJoin()
outerJoin()
leftJoin()
rightJoin()
orderby()
groupby()
having()
distinct()
top()
limit()
slice()
```

## INSERT

```text
insert()
into()
values()
```

## UPDATE

```text
update()
table()
updateTable()
set()
sets()
```

## DELETE

```text
delete()
```

## Stored Procedure

```text
procedure()
isSQLServer2008()
```

## protected / private

```text
appendColumn()
prependColumn()
_where()
imp_isNull()
_join()
_joinOnSubQuery()
_joinWithId()
_select()
_insert()
queryValues()
_update()
_delete()
_procedure()
```
