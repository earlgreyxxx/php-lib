# PHP ヘルパー関数リファレンス

提供されたコードに定義されている関数を、元ファイル名ではなく用途別に整理したリファレンスです。

## 前提・外部依存

- 本リファレンスは提供コードの実装内容をそのまま説明しています。一般的なPHP関数の推奨実装へ置き換えた説明ではありません。
- 外部定義として `_()`、`Store`、`PDOExtension`、`FileBaseStore`、`ReversibleEncryption`、`get_version()`、`TEMPORARY_DIR` などを参照する関数があります。
- `FILE_TIME_SLICE`、`FILE_SEED` は定義されている場合のみ利用されます。
- `mb_trim()` は条件付きで別ファイルから読み込まれますが、提供コード内に関数本体がないため本リファレンスの対象外です。
- 日付関連では `DATE_NULL = '0000-01-01'`、`DATE_BOUND = '9999-12-31'` が定義されています。

## 目次

- 文字列・サニタイズ
- ファイル・パス・環境
- 配列・汎用
- デバッグ・ログ・DB
- 日付・年度・カレンダー
- 暗号・ハッシュ・ID

## 文字列・サニタイズ

### `__()`

```php
function __(string $text) : void
```

**概要:** `_()` の戻り値をそのまま `echo` する出力用ショートカット。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$text` | `string` | — | 対象テキスト。 |

**戻り値:** 戻り値なし。

**注意:**
- `_()` はこのコード内では定義されていないため、別途利用可能である必要がある。

### `htmlspecialchars_utf8()`

```php
function htmlspecialchars_utf8(?string $src) : string
```

**概要:** 文字列を `htmlspecialchars()` で HTML エスケープする。`ENT_QUOTES`、UTF-8 固定。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$src` | `?string` | — | 入力文字列。`null` を許可する関数では空文字として扱われる場合がある。 |

**戻り値:** 処理結果の文字列。

### `str_sanitize()`

```php
function str_sanitize(?string $src) : string
```

**概要:** `htmlspecialchars_utf8()` を呼び出すサニタイズ用エイリアス。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$src` | `?string` | — | 入力文字列。`null` を許可する関数では空文字として扱われる場合がある。 |

**戻り値:** 処理結果の文字列。

### `str_sanitize_decode()`

```php
function str_sanitize_decode(?string $src) : string
```

**概要:** HTML エンティティを `htmlspecialchars_decode()` でデコードする。`ENT_QUOTES` を使用。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$src` | `?string` | — | 入力文字列。`null` を許可する関数では空文字として扱われる場合がある。 |

**戻り値:** 処理結果の文字列。

### `str_sanitize_html()`

```php
function str_sanitize_html(?string $html,array|string $tags = ['script','object']) : string
```

**概要:** 指定した HTML タグだけを正規表現で検索し、開始タグ・終了タグをエスケープする。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$html` | `?string` | — | 対象 HTML 文字列。 |
| `$tags` | `array\|string` | `['script','object']` | エスケープするタグ名。文字列の場合は空白・カンマ・セミコロンで分割される。 |

**戻り値:** 処理結果の文字列。

**注意:**
- 対象は開始タグと終了タグを持つ組み合わせで、正規表現による処理。タグの除去ではなくタグ文字列のエスケープを行う。

### `str_quotes()`

```php
function str_quotes(?string $str) : string
```

**概要:** シングルクォートとダブルクォートの直前にバックスラッシュを付加する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `?string` | — | 対象文字列。 |

**戻り値:** 処理結果の文字列。

### `str_quotes_decode()`

```php
function str_quotes_decode(string $str) : string
```

**概要:** `str_quotes()` で付加した `\'` と `\"` を元の引用符へ戻す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 対象文字列。 |

**戻り値:** 処理結果の文字列。

### `str_remove()`

```php
function str_remove(string $str,string $pattern = '[^\w\.]') : string
```

**概要:** 正規表現パターンに一致する文字列を削除する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 対象文字列。 |
| `$pattern` | `string` | `'[^\w\.]'` | 削除対象を表す正規表現本体。 |

**戻り値:** 処理結果の文字列。

### `str_numeric()`

```php
function str_numeric(string $str) : string
```

**概要:** 数字以外を削除し、0～9 だけからなる文字列を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 対象文字列。 |

**戻り値:** 処理結果の文字列。

### `intval_not_empty()`

```php
function intval_not_empty(?string $str) : ?int
```

**概要:** 値が `empty()` なら `null`、それ以外は `intval()` の結果を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `?string` | — | 対象文字列。 |

**戻り値:** 入力が `empty()` なら `null`、それ以外は整数値。

**注意:**
- PHP の `empty()` を使うため、`'0'` も `null` になる。

### `intval_not_null()`

```php
function intval_not_null(?string $str) : ?int
```

**概要:** `null` はそのまま `null`、それ以外は `intval()` の結果を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `?string` | — | 対象文字列。 |

**戻り値:** 入力が `null` なら `null`、それ以外は整数値。

### `nullval_if_empty()`

```php
function nullval_if_empty(?string $str) : ?string
```

**概要:** 値が `empty()` なら `null`、それ以外は元の文字列を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `?string` | — | 対象文字列。 |

**戻り値:** 空相当なら `null`、それ以外は元の文字列。

**注意:**
- PHP の `empty()` を使うため、`'0'` も `null` になる。

### `nullval_if_not_date()`

```php
function nullval_if_not_date(?string $str) : ?string
```

**概要:** `YYYY-MM-DD` 形式として扱える文字列だけを返し、それ以外は `null` にする。`/` は `-` に変換される。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `?string` | — | 対象文字列。 |

**戻り値:** 条件に合えば正規化した日付文字列、合わなければ `null`。

**注意:**
- 形式と `strtotime()` の結果で判定しており、`checkdate()` による厳密な暦日検証ではない。

### `nullval_if_not_phone()`

```php
function nullval_if_not_phone(?string $str) : ?string
```

**概要:** 数字とハイフンからなる電話番号形式に一致する場合だけ元の文字列を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `?string` | — | 対象文字列。 |

**戻り値:** 形式に合えば元の文字列、合わなければ `null`。

### `nullval_if_not_zipcode()`

```php
function nullval_if_not_zipcode(?string $str) : ?string
```

**概要:** 7桁の郵便番号（`NNNNNNN` または `NNN-NNNN`）なら元の文字列を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `?string` | — | 対象文字列。 |

**戻り値:** 形式に合えば元の文字列、合わなければ `null`。

### `nullval_if_not_email()`

```php
function nullval_if_not_email(string $str) : ?string
```

**概要:** `validate_email()` に合格したメールアドレスだけを返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 対象文字列。 |

**戻り値:** 検証成功時は元の文字列、失敗時は `null`。

**注意:**
- 引数型は `string` のため、`null` を渡すことはできない。

### `str_correct_zipcode()`

```php
function str_correct_zipcode(string $str,bool $has_hyphen = true,int $len = 8) : string
```

**概要:** 郵便番号文字列を半角化・記号整理し、数字と必要に応じてハイフンだけに補正する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 対象文字列。 |
| `$has_hyphen` | `bool` | `true` | `true` の場合はハイフンを保持する。 |
| `$len` | `int` | `8` | 返却文字列の最大長。 |

**戻り値:** 処理結果の文字列。

**注意:**
- `$has_hyphen=false` の場合、既定の最大長 `$len` から1を引いて処理する。

### `bytes()`

```php
function bytes(string $num,int $float = 0,array $unit = ['Byte','KB','MB','GB','TB','PB','EB']) : void
```

**概要:** `str_bytes()` の結果を `echo` する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$num` | `string` | — | 数値を表す文字列。 |
| `$float` | `int` | `0` | 小数部として残す最大桁数。 |
| `$unit` | `array` | `['Byte','KB','MB','GB','TB','PB','EB']` | 単位文字列の配列。 |

**戻り値:** 戻り値なし。

### `str_bytes()`

```php
function str_bytes(string $num,int $float = 0,array $unit = ['Byte','KB','MB','GB','TB','PB','EB']) : string
```

**概要:** バイト数を 1024 単位で Byte/KB/MB/GB…へ変換して文字列化する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$num` | `string` | — | 数値を表す文字列。 |
| `$float` | `int` | `0` | 小数部として残す最大桁数。 |
| `$unit` | `array` | `['Byte','KB','MB','GB','TB','PB','EB']` | 単位文字列の配列。 |

**戻り値:** 処理結果の文字列。

**注意:**
- 数字以外を1文字でも含む入力は変換せず、そのまま返す。小数は丸めではなく文字列切り出しで扱う。

### `extract_unit_size()`

```php
function extract_unit_size(string $num_str) : int|false
```

**概要:** `10K`、`1.5MB` などの単位付きサイズをバイト数へ展開する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$num_str` | `string` | — | 単位付きサイズ文字列。 |

**戻り値:** 変換後のバイト数。形式不一致なら `false`。

**注意:**
- 単位は K/M/G/T/P/E（大文字小文字不問）、末尾の `B` は省略可能。計算後は `floor()` して整数化する。

### `sanitize_url()`

```php
function sanitize_url(string $path,int $limit = 1) : string
```

**概要:** `.` / `..` を含むスラッシュ区切りパスを簡易的に整理する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$path` | `string` | — | 対象パス。 |
| `$limit` | `int` | `1` | `..` 解決時の下限位置。 |

**戻り値:** 処理結果の文字列。

**注意:**
- 完全な URL 正規化ではなく簡易処理。末尾が `..` の場合など、一般的なパス正規化と異なる結果になるケースがある。

### `html_sanitize()`

```php
function html_sanitize(array $tags,string $html) : string
```

**概要:** 空文字なら空文字を返し、それ以外は `str_sanitize_html()` で指定タグをエスケープする。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$tags` | `array` | — | エスケープするタグ名の配列。 |
| `$html` | `string` | — | 対象 HTML 文字列。 |

**戻り値:** 処理結果の文字列。

### `validate_email()`

```php
function validate_email(string $str,bool $is_enable_checkdns = false) : bool
```

**概要:** 独自正規表現でメールアドレスを検査し、必要なら DNS レコードも確認する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 検査するメールアドレス。 |
| `$is_enable_checkdns` | `bool` | `false` | `true` の場合はドメインの DNS レコードも確認する。 |

**戻り値:** 検証成功時 `true`、失敗時 `false`。

**注意:**
- TLD 部は独自正規表現上 2～6 文字。DNS 検査時は MX/A/NS のいずれかが存在すれば `true`。

### `str_format()`

```php
function str_format() : string
```

**概要:** `{番号:printf書式}` 形式のプレースホルダーを `vsprintf()` 用書式へ変換して値を埋め込む。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$format` | `string` | — | `{番号:printf書式}` を含む書式文字列。 |
| `...$values` | `mixed` | — | 第2引数以降に埋め込み値を渡す。単一の配列なら、その配列を値一覧として扱う。 |

**戻り値:** 処理結果の文字列。

**注意:**
- プレースホルダー番号は正規表現上1桁。番号は0始まりで、内部では `printf` の1始まりへ変換する。第2引数が単一配列ならその配列を値リストとして扱う。

### `str_format_escape()`

```php
function str_format_escape() : string
```

**概要:** `str_format()` と同じ書式処理を行いつつ、`{{` と `}}` をリテラル波括弧として扱えるようにする。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$format` | `string` | — | `{番号:printf書式}` を含む書式文字列。`{{` / `}}` で波括弧をエスケープできる。 |
| `...$values` | `mixed` | — | 第2引数以降に埋め込み値を渡す。単一の配列なら、その配列を値一覧として扱う。 |

**戻り値:** 処理結果の文字列。

**注意:**
- 内部で `chr(145)` / `chr(146)` を一時プレースホルダーとして使用する。

### `add_space_ifnot_empty()`

```php
function add_space_ifnot_empty(string $str,int $position = 0,string $adding = ' ') : ?string
```

**概要:** 空でない文字列にだけ、指定位置へ追加文字列を挿入する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 対象文字列。 |
| `$position` | `int` | `0` | 挿入位置。正数・0・負数で動作が変わる。 |
| `$adding` | `string` | `' '` | 挿入する文字列。 |

**戻り値:** 文字列または `null`。

**注意:**
- `$position > 0` はバイト位置で挿入、0は先頭追加、負数は末尾追加。

## ファイル・パス・環境

### `exact_filename()`

```php
function exact_filename(string &$basename,string $dir = '.',string $sep = '_') : int
```

**概要:** 同名ファイルが存在する場合、ベース名へ連番を付けて未使用ファイル名に変更する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$basename`（参照渡し） | `string` | — | ベースファイル名。 |
| `$dir` | `string` | `'.'` | 対象ディレクトリ。 |
| `$sep` | `string` | `'_'` | 連番の前に入れる区切り文字。 |

**戻り値:** 付加した連番。元の名前を使えた場合は `0`。

**注意:**
- ベース名は参照渡しで書き換えられる。拡張子がない場合でも生成書式上は末尾に `.` が付く。

### `get_exact_filename()`

```php
function get_exact_filename(string $basename,string $dir = '.',string $sep = '_') : string
```

**概要:** `exact_filename()` を値返却型で利用するラッパー。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$basename` | `string` | — | ベースファイル名。 |
| `$dir` | `string` | `'.'` | 対象ディレクトリ。 |
| `$sep` | `string` | `'_'` | 連番の前に入れる区切り文字。 |

**戻り値:** 処理結果の文字列。

### `get_platform_filename()`

```php
function get_platform_filename(string $filename) : string
```

**概要:** Windows の場合だけ UTF-8 のファイル名を `SJIS-WIN` へ変換する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$filename` | `string` | — | 対象ファイル名。 |

**戻り値:** 処理結果の文字列。

### `get_disposition_filename()`

```php
function get_disposition_filename(string $filename) : string
```

**概要:** ダウンロード等で使うファイル名向けに一部記号を全角化し、古い IE 向け処理を行う。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$filename` | `string` | — | 対象ファイル名。 |

**戻り値:** 処理結果の文字列。

**注意:**
- `$_SERVER['HTTP_USER_AGENT']` を直接参照するため、未設定環境では注意が必要。

### `rrmdir()`

```php
function rrmdir(string $dir,string $reg_pattern = '') : bool
```

**概要:** 指定ディレクトリ配下を再帰的に削除し、最後にディレクトリ自身も削除する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$dir` | `string` | — | 対象ディレクトリ。 |
| `$reg_pattern` | `string` | `''` | 現実装では使用されない。 |

**戻り値:** 最終的な `rmdir()` の成否。

**注意:**
- `$reg_pattern` は現実装では未使用で、内容を選別せず削除する。

### `set_windows_console()`

```php
function set_windows_console() : void
```

**概要:** 出力バッファを開始し、出力内容を UTF-8 から `SJIS-WIN` へ変換する。

**戻り値:** 戻り値なし。

### `flush_windows_console()`

```php
function flush_windows_console() : void
```

**概要:** 現在の出力バッファを終了して内容を送出する。

**戻り値:** 戻り値なし。

### `is_zip()`

```php
function is_zip(string $filepath) : bool
```

**概要:** ファイル先頭2バイトが `PK` かどうかで ZIP 形式らしさを判定する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$filepath` | `string` | — | 対象ファイルパス。 |

**戻り値:** 先頭2バイトが `PK` なら `true`。

**注意:**
- ZIP の完全検証ではなく、先頭2バイト `PK` のみを確認する簡易判定。

### `is_compoundfile()`

```php
function is_compoundfile(string $filepath) : bool
```

**概要:** ファイル先頭8バイトが OLE2 Compound File のシグネチャかどうかを判定する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$filepath` | `string` | — | 対象ファイルパス。 |

**戻り値:** OLE2 シグネチャ一致時 `true`。

### `is_ssl()`

```php
function is_ssl() : bool
```

**概要:** `$_SERVER` の HTTPS/SSL/Forwarded/Port 情報から HTTPS 接続かどうかを判定する。

**戻り値:** HTTPS と判定した場合 `true`。

**注意:**
- 条件は `HTTPS` → `SSL` → `HTTP_X_FORWARDED_PROTO` → `HTTP_X_FORWARDED_PORT` → `SERVER_PORT` の順に評価される。

### `get_filehead()`

```php
function get_filehead(string $filepath,int $num = 0) : string|false
```

**概要:** ファイル先頭の指定バイト数、または先頭1行を読み込む。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$filepath` | `string` | — | 対象ファイルパス。 |
| `$num` | `int` | `0` | 0より大きければ読み込むバイト数。0以下なら先頭1行を返す。 |

**戻り値:** 読込結果。対象がファイルでない場合などは `false`。

### `get_temporary_filename()`

```php
function get_temporary_filename(string $prefix = 'auto_',string $suffix = '.dat') : string
```

**概要:** 日付とランダム ID を組み合わせた一時ファイル名を生成する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$prefix` | `string` | `'auto_'` | 生成名の先頭に付加する文字列。 |
| `$suffix` | `string` | `'.dat'` | 生成名の末尾に付加する文字列。 |

**戻り値:** 処理結果の文字列。

### `get_temporary_filepath()`

```php
function get_temporary_filepath(string $savedir,int|false $hint=false) : string
```

**概要:** 保存先ディレクトリとランダム ID から一時ファイルパスを生成する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$savedir` | `string` | — | 保存先ディレクトリ。 |
| `$hint` | `int\|false` | `false` | 整数ならファイル名へ `-%02d` 形式で付加する。`false` なら付加しない。 |

**戻り値:** 処理結果の文字列。

### `create_path_prefix()`

```php
function create_path_prefix(string $basename) : string
```

**概要:** ベース名先頭2文字から保存先サブディレクトリ用プレフィックスを決める。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$basename` | `string` | — | 先頭2文字を抽出する元文字列。 |

**戻り値:** 処理結果の文字列。

### `create_path()`

```php
function create_path(string $hint,string $rootpath) : string|false
```

**概要:** ヒント文字列から保存先サブディレクトリを決め、存在しなければ作成する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$hint` | `string` | — | 保存先サブディレクトリ名を決めるヒント。 |
| `$rootpath` | `string` | — | 保存先ルートディレクトリ。 |

**戻り値:** 保存先ディレクトリパス。パス衝突時は `false`。

**注意:**
- ルートが空またはディレクトリでない場合は `.` を使用する。新規ディレクトリは `0777` に `chmod()` する。

### `create_basename()`

```php
function create_basename(array $_file,string $rootpath) : string
```

**概要:** アップロードファイル情報から安全化した保存用ベース名を生成し、重複しない名前にする。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$_file` | `array` | — | アップロードファイル情報を含む配列。`name` キーを参照する。 |
| `$rootpath` | `string` | — | 保存先ルートディレクトリ。 |

**戻り値:** 処理結果の文字列。

**注意:**
- `FileBaseStore::PREG_PATTERN_FS`、`get_exact_filename()`、`create_path()` などに依存する。

## 配列・汎用

### `defineIf()`

```php
function defineIf(string $name,mixed $value) : bool
```

**概要:** 未定義の定数だけを `define()` する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$name` | `string` | — | 定数名、またはファイル情報内の名前。 |
| `$value` | `mixed` | — | 定数へ設定する値。 |

**戻り値:** 新たに定義できた場合 `true`。既に定義済みなら `false`。

### `array_inserter()`

```php
function array_inserter(array &$ar,mixed $item,int $pos = 0) : void
```

**概要:** 配列の先頭・末尾・任意位置へ要素を挿入する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$ar`（参照渡し） | `array` | — | 対象配列。 |
| `$item` | `mixed` | — | 挿入する要素。 |
| `$pos` | `int` | `0` | 挿入位置。0=先頭、負数=末尾、正数=その位置。 |

**戻り値:** 戻り値なし。

### `array_identical()`

```php
function array_identical(array $array1,array $array2,bool $only_index = true) : bool
```

**概要:** 2配列を厳密比較する。既定では数値系キーだけを比較対象にする。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$array1` | `array` | — | 比較対象配列1。 |
| `$array2` | `array` | — | 比較対象配列2。 |
| `$only_index` | `bool` | `true` | `true` の場合は数値系キーだけを比較する。 |

**戻り値:** 比較条件を満たす場合 `true`、それ以外は `false`。

**注意:**
- どちらかの配列が空なら `false`。`$only_index=true` では整数キー、または数字を含む文字列キーだけを厳密比較する。

### `_array_identical_()`

```php
function _array_identical_(array $a,array $b,bool $r = true) : bool
```

**概要:** `array_identical()` が数値系キー比較に使用する内部補助関数。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$a` | `array` | — | 比較対象配列。 |
| `$b` | `array` | — | 比較対象配列。 |
| `$r` | `bool` | `true` | 内部再帰判定用フラグ。 |

**戻り値:** 内部比較が一致した場合 `true`、それ以外は `false`。

**注意:**
- 通常は公開 API ではなく `array_identical()` の内部処理として使用する。

### `array_merge_unless_exists()`

```php
function array_merge_unless_exists(array $src,array $additionals) : array
```

**概要:** 元配列に存在しないキーだけを追加配列から取り込む。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$src` | `array` | — | 入力文字列。`null` を許可する関数では空文字として扱われる場合がある。 |
| `$additionals` | `array` | — | 追加候補のキー・値を持つ配列。 |

**戻り値:** 処理結果の配列。

### `check_digit_12()`

```php
function check_digit_12(string $num) : string|int
```

**概要:** 12文字の番号から Modulus 10 Weight 3/1 方式のチェックディジットを計算する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$num` | `string` | — | 12文字の番号。 |

**戻り値:** 計算したチェックディジット。通常は整数、実装上 `X` を返す分岐もある。

**注意:**
- 長さが12でなければ `RuntimeException('invalid parameter')`。数字だけかどうかの明示検証はない。

### `check_digit_9()`

```php
function check_digit_9(string $num) : int|string
```

**概要:** 9文字の番号から Modulus 11 Weight 10～2 方式のチェックディジットを計算する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$num` | `string` | — | 9文字の番号。 |

**戻り値:** 計算したチェックディジット。0～9 の整数、または `X`。

**注意:**
- 長さが9でなければ `RuntimeException('invalid parameter')`。

### `asserter()`

```php
function asserter(mixed $mixed,string $message) : void
```

**概要:** `assert()` を呼び出し、失敗時用の `RuntimeException` を指定する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$mixed` | `mixed` | — | ダンプまたは検査対象の値。 |
| `$message` | `string` | — | アサーション失敗時の例外メッセージ。 |

**戻り値:** 戻り値なし。

**注意:**
- 実際にアサーションが評価されるかは PHP の assert 設定に依存する。

### `uuid()`

```php
function uuid() : string
```

**概要:** UUID v4 形式の文字列を生成する。

**戻り値:** 処理結果の文字列。

## デバッグ・ログ・DB

### `logSQL()`

```php
function logSQL(Store $store,string $eventname) : void
```

**概要:** `Store` の指定イベントへ SQL ログ出力コールバックを登録する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$store` | `Store` | — | イベント登録先の `Store` オブジェクト。 |
| `$eventname` | `string` | — | SQL ログコールバックを登録するイベント名。 |

**戻り値:** 戻り値なし。

**注意:**
- `Store` クラス、`TEMPORARY_DIR` 定数、`_()` など外部定義に依存する。ログは `TEMPORARY_DIR/sql.log` へ追記される。

### `print_r_html()`

```php
function print_r_html(array $ar,bool $return = false) : ?string
```

**概要:** 配列を `print_r()` し、`<pre>` で囲んだ HTML として返すか出力する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$ar` | `array` | — | 対象配列。 |
| `$return` | `bool` | `false` | `true` なら文字列を返し、`false` なら直接出力する。 |

**戻り値:** `$return=true` の場合は HTML 文字列、それ以外は出力して `null`。

**注意:**
- `print_r()` の内容自体は HTML エスケープしていない。

### `var_dump_ret()`

```php
function var_dump_ret(mixed $mixed) : string
```

**概要:** `var_dump()` の出力を文字列として取得する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$mixed` | `mixed` | — | ダンプまたは検査対象の値。 |

**戻り値:** 処理結果の文字列。

### `var_dump_html()`

```php
function var_dump_html(mixed $var) : void
```

**概要:** `var_dump()` の内容を HTML エスケープして `<pre>` 内へ出力する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$var` | `mixed` | — | ダンプ対象の値。 |

**戻り値:** 戻り値なし。

### `var_dump_to()`

```php
function var_dump_to(mixed $mixed,mixed $to = '/dev/null') : void
```

**概要:** `var_dump()` の結果をファイルパスまたはストリームへ追記する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$mixed` | `mixed` | — | ダンプまたは検査対象の値。 |
| `$to` | `mixed` | `'/dev/null'` | 出力先ファイルパス、またはストリームリソース。 |

**戻り値:** 戻り値なし。

**注意:**
- 文字列パスでは `FILE_APPEND | LOCK_EX` を使用。ストリームでは `flock()`、末尾 `fseek()`、`fwrite()` を行う。

### `GetPdoInstance()`

```php
function GetPdoInstance(string $dsn,string $user = '',string $passwd = '',array $options = []) : PDOExtension|false
```

**概要:** DSN 等をキーに `PDOExtension` インスタンスをキャッシュし、同じ接続を再利用する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$dsn` | `string` | — | PDO 接続用 DSN。 |
| `$user` | `string` | `''` | DB ユーザー名。 |
| `$passwd` | `string` | `''` | DB パスワード。 |
| `$options` | `array` | `[]` | PDO オプション。独自キー `cache-id` も利用できる。 |

**戻り値:** 取得した `PDOExtension`。生成結果が得られなければ `false`。

**注意:**
- キャッシュキーは `DSN + user + cache-id` の SHA-1 で、パスワードはキーに含まれない。`PDO::ATTR_DEFAULT_FETCH_MODE` は `PDO::FETCH_ASSOC` に設定される。`PDOExtension` が別途必要。

### `str_escape_sql()`

```php
function str_escape_sql($str)
```

**概要:** SQL インジェクション対策用として宣言されている関数。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `mixed/未指定` | — | 対象文字列。 |

**戻り値:** 実装がないため明示的な `return` はなく、実行時は `null` 相当。

**注意:**
- 本体は `// not implement yet` のみで、現時点では何も処理しない。戻り値型も未宣言。

## 日付・年度・カレンダー

### `date_to_empty()`

```php
function date_to_empty(string &$date) : bool
```

**概要:** 特殊日付値 `DATE_NULL` / `DATE_BOUND` を空文字へ置き換える。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$date`（参照渡し） | `string` | — | 変換対象。`DATE_NULL` / `DATE_BOUND` の場合に空文字へ書き換えられる。 |

**戻り値:** 特殊値を空文字へ変換した場合 `true`、変更しなかった場合 `false`。

**注意:**
- `DATE_NULL='0000-01-01'`、`DATE_BOUND='9999-12-31'` が同じコード内で定義されている。

### `Now()`

```php
function Now(string $format = 'Y-m-d H:i:s') : string
```

**概要:** 現在日時を `date()` で指定形式へ変換して返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$format` | `string` | `'Y-m-d H:i:s'` | `date()` へ渡すフォーマット。 |

**戻り値:** 処理結果の文字列。

### `get_business_year_range()`

```php
function get_business_year_range(int $byear = 0,?string $fmt = 'Y-m-d') : array
```

**概要:** 指定年度の開始日・終了日を配列で返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$byear` | `int` | `0` | 年度。0 の場合は現在年度を使用する。 |
| `$fmt` | `?string` | `'Y-m-d'` | `date()` 用フォーマット。`null`/空値で UNIX 時刻を返す関数がある。 |

**戻り値:** `['s' => 開始日, 'e' => 終了日]`。

### `business_year_start_date()`

```php
function business_year_start_date(int $byear = 0,?string $fmt = 'Y-m-d') : int|string|false
```

**概要:** 指定年度の開始日時を、文字列または UNIX タイムスタンプで返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$byear` | `int` | `0` | 年度。0 の場合は現在年度を使用する。 |
| `$fmt` | `?string` | `'Y-m-d'` | `date()` 用フォーマット。`null`/空値で UNIX 時刻を返す関数がある。 |

**戻り値:** `$fmt` が `null` なら UNIX タイムスタンプ、それ以外は書式化文字列。`mktime()` の仕様上 `false` の可能性も型に含む。

**注意:**
- `$byear=0` の場合は `get_business_year()` を使用する。年度開始月は `business_year_start_month()` の現在設定値を使用。

### `business_year_end_date()`

```php
function business_year_end_date(int $byear = 0,?string $fmt = 'Y-m-d') : int|string|false
```

**概要:** 指定年度の終了日時を、文字列または UNIX タイムスタンプで返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$byear` | `int` | `0` | 年度。0 の場合は現在年度を使用する。 |
| `$fmt` | `?string` | `'Y-m-d'` | `date()` 用フォーマット。`null`/空値で UNIX 時刻を返す関数がある。 |

**戻り値:** `$fmt` が空なら UNIX タイムスタンプ、それ以外は書式化文字列。型宣言上 `false` も含む。

**注意:**
- 終了日は「次年度開始日の1日前」として算出する。`$fmt` が空なら UNIX タイムスタンプを返す。

### `business_year_start_month()`

```php
function business_year_start_month(?int $set_month = null) : int
```

**概要:** 年度開始月を取得または設定する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$set_month` | `?int` | `null` | 設定する年度開始月。`null` なら取得のみ。 |

**戻り値:** 整数値。

**注意:**
- 初期値は4月。1～12以外の値を渡しても設定は変更されない。

### `business_year_end_month()`

```php
function business_year_end_month() : int
```

**概要:** 現在設定されている年度開始月から年度終了月を算出する。

**戻り値:** 整数値。

### `get_business_year()`

```php
function get_business_year(int|string|null $time = null,int $start_month = 4) : int|false
```

**概要:** 指定 UNIX 時刻が属する年度を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$time` | `int\|string\|null` | `null` | UNIX 時刻。空値の場合は現在時刻を使用する。 |
| `$start_month` | `int` | `4` | 年度開始月。 |

**戻り値:** 年度を表す整数。時刻が0以下なら `false`。

**注意:**
- `$time` は日付文字列を `strtotime()` せず `intval()` するため、実質的に UNIX タイムスタンプまたは数値文字列向け。

### `business_date_to_real_date()`

```php
function business_date_to_real_date(int $by,int $bm,int $bd = 1) : array
```

**概要:** 年度年と月から実際の西暦年・月を算出する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$by` | `int` | — | 年度年。 |
| `$bm` | `int` | — | 月。 |
| `$bd` | `int` | `1` | 日。現実装では戻り値の計算に使用されない。 |

**戻り値:** `[西暦年, 月]`。

**注意:**
- 戻り値は `[年, 月]` の2要素のみ。引数 `$bd` は現実装では使用されない。

### `getWeeks()`

```php
function getWeeks(int $start = 0) : array
```

**概要:** 日本語の曜日一覧を、指定曜日を先頭にして返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$start` | `int` | `0` | 先頭にしたい曜日位置。 |

**戻り値:** 曜日名7件の配列。

**注意:**
- 負数を渡すと `RuntimeException`。7以上は7で剰余を取る。

### `calendar()`

```php
function calendar(int $y,int $m,mixed $option = 0) : array
```

**概要:** 指定年月の月間カレンダーを7列の2次元配列として生成する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$y` | `int` | — | 西暦年。 |
| `$m` | `int` | — | 月。 |
| `$option` | `mixed` | `0` | 整数なら週開始位置、callable なら日付変換コールバック、配列なら `begin` と `callback` を指定。 |

**戻り値:** 各行7要素からなる2次元配列。

**注意:**
- 年は1000～2100、月は1～12のみ。範囲外は `RuntimeException`。配列オプションでは `begin` と `callback` キーを直接参照する。

### `get_wareki_year()`

```php
function get_wareki_year(int $y,int $m,int $d,string $unit = '年') : string
```

**概要:** 西暦日付を和暦の「元号＋年」に変換して文字列化する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$y` | `int` | — | 西暦年。 |
| `$m` | `int` | — | 月。 |
| `$d` | `int` | — | 日。 |
| `$unit` | `string` | `'年'` | 単位文字列の配列。 |

**戻り値:** 処理結果の文字列。

### `get_wareki()`

```php
function get_wareki(int $y = 1970, int $m = 1, int $d = 1, array $nengo = ['reiwa' => '令和','showa'=>'昭和','heisei'=>'平成','taisho'=>'大正','meiji'=>'明治']) : array
```

**概要:** 西暦日付を `[元号, 年, 月, 日]` の和暦情報へ変換する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$y` | `int` | `1970` | 西暦年。 |
| `$m` | `int` | `1` | 月。 |
| `$d` | `int` | `1` | 日。 |
| `$nengo` | `array` | `['reiwa' => '令和','showa'=>'昭和','heisei'=>'平成','taisho'=>'大正','meiji'=>'明治']` | 元号表示名のマッピング。 |

**戻り値:** `[元号名, 和暦年, 月, 日]`。

**注意:**
- 境界は令和=2019-05-01、平成=1989-01-08、昭和=1926-12-25、大正=1912-07-30、明治=1868-01-25。それ以前は `seireki`。

### `get_wareki_range()`

```php
function get_wareki_range(string $unit,int $min = 0)
```

**概要:** 指定元号で選択可能な年範囲、または西暦の年範囲を返す。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$unit` | `string` | — | `reiwa` / `heisei` / `showa` / `taisho` / `meiji`。それ以外は西暦扱い。 |
| `$min` | `int` | `0` | 西暦範囲の最小年候補。 |

**戻り値:** `[開始年, 終了年]`。

**注意:**
- 令和の上限は初回呼び出し時点の `date('Y') - 2018`。元号以外では直近80年を基本範囲とする。戻り値型は未宣言。

### `get_full_age()`

```php
function get_full_age(int $birth) : int
```

**概要:** 誕生日の UNIX タイムスタンプから現在の満年齢を算出する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$birth` | `int` | — | 誕生日を表す UNIX タイムスタンプ。 |

**戻り値:** 整数値。

**注意:**
- `$birth` は UNIX タイムスタンプとして `date('Ymd', $birth)` に渡される。

### `full_age()`

```php
function full_age(int $birth,string $unit = '') : void
```

**概要:** `get_full_age()` の結果と単位文字列をそのまま出力する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$birth` | `int` | — | 誕生日を表す UNIX タイムスタンプ。 |
| `$unit` | `string` | `''` | 単位文字列の配列。 |

**戻り値:** 戻り値なし。

## 暗号・ハッシュ・ID

### `get_cipher()`

```php
function get_cipher(string $key,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : ReversibleEncryption
```

**概要:** 暗号鍵ごとに `ReversibleEncryption` インスタンスを生成・キャッシュする。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$key` | `string` | — | 暗号鍵。 |
| `$algo` | `string` | `ReversibleEncryption::DEFAULT_ALGORITHM` | 暗号アルゴリズム名。 |

**戻り値:** キャッシュ済みまたは新規作成した `ReversibleEncryption`。

**注意:**
- キャッシュのキーは暗号鍵 `$key` のみで、`$algo` はキャッシュキーに含まれない。同じ鍵で異なるアルゴリズムを指定すると最初のインスタンスが再利用される。

### `str_encrypt()`

```php
function str_encrypt(string $plain,string $key,bool $base64encode = true,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : string|false
```

**概要:** `ReversibleEncryption` を使って文字列を可逆暗号化する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$plain` | `string` | — | 平文。 |
| `$key` | `string` | — | 暗号鍵。 |
| `$base64encode` | `bool` | `true` | 暗号化結果を Base64 エンコードするかどうか。 |
| `$algo` | `string` | `ReversibleEncryption::DEFAULT_ALGORITHM` | 暗号アルゴリズム名。 |

**戻り値:** 暗号化結果。暗号化側が失敗した場合は `false`。

**注意:**
- 空の鍵は `Exception`。`ReversibleEncryption` クラスが別途必要。

### `str_decrypt()`

```php
function str_decrypt(string $encrypted,string $key,bool $base64decode = true,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : string|false
```

**概要:** `ReversibleEncryption` を使って暗号文を復号する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$encrypted` | `string` | — | 暗号文。 |
| `$key` | `string` | — | 暗号鍵。 |
| `$base64decode` | `bool` | `true` | 入力暗号文を Base64 デコードするかどうか。 |
| `$algo` | `string` | `ReversibleEncryption::DEFAULT_ALGORITHM` | 暗号アルゴリズム名。 |

**戻り値:** 復号結果。復号側が失敗した場合は `false`。

**注意:**
- 空の鍵は `Exception`。`ReversibleEncryption` クラスが別途必要。

### `get_time_slice_uniqid()`

```php
function get_time_slice_uniqid() : string
```

**概要:** 一定時間区間ごとに変化する MD5 形式の識別文字列を生成する。

**戻り値:** 処理結果の文字列。

**注意:**
- 時間幅は `FILE_TIME_SLICE` があればその値、なければ30分。シードは `FILE_SEED` または `get_version()` の SHA-256 ダイジェストを使い、HTTP_HOST があれば追加する。

### `str_encrypt_ts()`

```php
function str_encrypt_ts(string $plain,bool $is_hex = true) : string
```

**概要:** 現在の時間スライスから生成した鍵で文字列を暗号化する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$plain` | `string` | — | 平文。 |
| `$is_hex` | `bool` | `true` | `true` なら暗号化結果を `bin2hex()` する。 |

**戻り値:** 処理結果の文字列。

**注意:**
- 現在の時間スライス鍵だけを使用するため、時間区間が変わると同じ方式では過去区間の暗号文を復号できない。

### `str_decrypt_ts()`

```php
function str_decrypt_ts(string $encrypted,bool $is_hex = true) : string|false
```

**概要:** 現在の時間スライスから生成した鍵で暗号文を復号する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$encrypted` | `string` | — | 暗号文。 |
| `$is_hex` | `bool` | `true` | `true` なら入力を16進文字列として `hex2bin()` してから復号する。 |

**戻り値:** 復号文字列。失敗時は `false`。

**注意:**
- 復号鍵は呼び出し時点の時間スライスから生成される。

### `blowfish()`

```php
function blowfish(string $plain, int $cost = 4) : string
```

**概要:** ランダムソルトを生成し、`crypt()` 用 Blowfish/bcrypt ハッシュを作る。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$plain` | `string` | — | 平文。 |
| `$cost` | `int` | `4` | Blowfish/bcrypt のコスト値。 |

**戻り値:** 処理結果の文字列。

**注意:**
- ソルト生成に `mt_rand()` を使用し、`$2a$` 形式を `crypt()` へ渡す。コストは4～31へ丸め込まれる。

### `crypt_blowfish()`

```php
function crypt_blowfish(string $plain,int $cost = 4) : string
```

**概要:** `blowfish()` のラッパー。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$plain` | `string` | — | 平文。 |
| `$cost` | `int` | `4` | Blowfish/bcrypt のコスト値。 |

**戻り値:** 処理結果の文字列。

**注意:**
- `blowfish()` と同じ動作。

### `create_key()`

```php
function create_key(string $hint1,string $hint2,?callable $callable = null) : string
```

**概要:** 2つのヒント文字列から SHA-1、Blowfish、または任意コールバックでキー文字列を生成する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$hint1` | `string` | — | キー生成に使うヒント1。 |
| `$hint2` | `string` | — | キー生成に使うヒント2。 |
| `$callable` | `?callable` | `null` | 任意のキー生成コールバック。`'crypt'` は特別扱いされる。 |

**戻り値:** 処理結果の文字列。

**注意:**
- 既定は `sha1("$hint1:$hint2")`。`$callable === 'crypt'` の場合はランダムソルトを使う `crypt_blowfish()` なので、同じ入力でも同じ文字列になるとは限らない。

### `scramble()`

```php
function scramble(int $seed)
```

**概要:** 固定された2つの乗数を使って整数値をスクランブルする。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$seed` | `int` | — | スクランブル対象の整数。 |

**戻り値:** スクランブル後の整数値。

**注意:**
- 戻り値型は未宣言。結果は `0x7fffffff` でマスクされる。暗号学的な暗号化処理ではない。

### `str_uniqid()`

```php
function str_uniqid(string $prefix = '',bool $dummy = false) : string
```

**概要:** `random_bytes()` と SHA-1 を使ってランダム ID 文字列を生成する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$prefix` | `string` | `''` | 生成名の先頭に付加する文字列。 |
| `$dummy` | `bool` | `false` | 互換性のための引数。現実装では使用しない。 |

**戻り値:** 処理結果の文字列。

**注意:**
- 第2引数 `$dummy` は互換用で使用されない。戻り値は prefix + 40桁の SHA-1 16進文字列。

### `sha256()`

```php
function sha256(string $str,bool $is_bin = false)
```

**概要:** SHA-256 ハッシュを生成する。

**引数**

| 引数 | 型 | 既定値 | 説明 |
|---|---|---|---|
| `$str` | `string` | — | 対象文字列。 |
| `$is_bin` | `bool` | `false` | `true` の場合はバイナリ形式のハッシュを返す。 |

**戻り値:** SHA-256 ハッシュ。`$is_bin=false` は16進文字列、`true` はバイナリ。

**注意:**
- 戻り値型は未宣言。`$is_bin=true` では生バイナリ、false では16進文字列を返す。
