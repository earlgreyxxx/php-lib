# PHP ユーティリティ API リファレンス

このドキュメントは、提供されたPHPコードに定義されているグローバル関数およびクラスメソッドを、実装上の挙動に基づいて整理したリファレンスです。

## 記載方針

- 関数シグネチャは実装に合わせて記載しています。
- `void` 関数のうち `echo` を行うものは「直接出力」と明記しています。
- 参照渡し、`static` キャッシュ、例外、未使用引数など、呼び出し時に影響する実装上の仕様は「補足」に記載しています。
- ファイル構成には依存せず、機能カテゴリ単位で整理しています。

## 関連定数

| 定数 | 値 | 用途 |
|---|---:|---|
| `DATE_NULL` | `'0000-01-01'` | 未定義日として扱う日付値 |
| `DATE_BOUND` | `'9999-12-31'` | 未定義日として扱う上限側の日付値 |
| `VERSION` | `'unkown'` | `.ver` からバージョンを取得できない場合の初期値 |
| `ReversibleEncryption::DEFAULT_ALGORITHM` | `'aes-128-cbc'` | 可逆暗号化の既定アルゴリズム |

## 目次

- Accessor / URL / Routing / CSRF
- Action / Filter
- General Utilities
- Date / Calendar
- Encryption / Hash / ID
- String / Validation / Debug Output
- Template Output Helpers
- Version
- Class: `ReversibleEncryption`
- Class: `DebugUtils`

## Accessor / URL / Routing / CSRF

### `get_inputs()`

```php
function &get_inputs(string $type = '') : array|false
```

指定された種別のスーパーグローバル配列を参照で取得します。

**引数**

- `$type` (`string` / 既定値: `''`) — 取得対象。`post` / `get` / `cookie` / `files` / `request` を指定します。

**戻り値:** 指定されたスーパーグローバル配列への参照。未対応の種別では `false`。

**参照返却:** この関数は戻り値を参照として返します。

**補足**

- 戻り値は参照です。呼び出し側で変更すると対応するスーパーグローバルへ影響します。

### `get_request()`

```php
function &get_request(?array $request = null) : array
```

リクエスト配列を初回取得時に保持し、以後同じ配列を参照で返します。

**引数**

- `$request` (`?array` / 既定値: `null`) — 初回値として使用するリクエスト配列。`null` の場合は `$_REQUEST` を使用します。

**戻り値:** 保持されているリクエスト配列への参照。

**参照返却:** この関数は戻り値を参照として返します。

**補足**

- 値は `static` 変数に保持されるため、同一リクエスト中の2回目以降の引数は反映されません。
- `$request == null` の緩い比較を使用しています。

### `get_post()`

```php
function &get_post(?array $posts = null) : array
```

POST配列を初回取得時に保持し、以後同じ配列を参照で返します。

**引数**

- `$posts` (`?array` / 既定値: `null`) — 初回値として使用するPOST配列。`null` の場合は `$_POST` を使用します。

**戻り値:** 保持されているPOST配列への参照。

**参照返却:** この関数は戻り値を参照として返します。

**補足**

- 値は `static` 変数に保持されるため、同一リクエスト中の2回目以降の引数は反映されません。
- `$posts == null` の緩い比較を使用しています。

### `get_get()`

```php
function &get_get(?array $gets = null) : array
```

GET配列を初回取得時に保持し、以後同じ配列を参照で返します。

**引数**

- `$gets` (`?array` / 既定値: `null`) — 初回値として使用するGET配列。`null` の場合は `$_GET` を使用します。

**戻り値:** 保持されているGET配列への参照。

**参照返却:** この関数は戻り値を参照として返します。

**補足**

- 値は `static` 変数に保持されるため、同一リクエスト中の2回目以降の引数は反映されません。

### `get_cookie()`

```php
function &get_cookie(?array $cookies = null) : array
```

Cookie配列を初回取得時に保持し、以後同じ配列を参照で返します。

**引数**

- `$cookies` (`?array` / 既定値: `null`) — 初回値として使用するCookie配列。`null` の場合は `$_COOKIE` を使用します。

**戻り値:** 保持されているCookie配列への参照。

**参照返却:** この関数は戻り値を参照として返します。

**補足**

- 値は `static` 変数に保持されるため、同一リクエスト中の2回目以降の引数は反映されません。

### `get_files()`

```php
function &get_files(?array $files = null) : array
```

アップロードファイル配列を初回取得時に保持し、以後同じ配列を参照で返します。

**引数**

- `$files` (`?array` / 既定値: `null`) — 初回値として使用するファイル配列。`null` の場合は `$_FILES` を使用します。

**戻り値:** 保持されているファイル配列への参照。

**参照返却:** この関数は戻り値を参照として返します。

**補足**

- 値は `static` 変数に保持されるため、同一リクエスト中の2回目以降の引数は反映されません。

### `get_session()`

```php
function &get_session(?array $session = null) : array
```

セッション配列を初回取得時に保持し、以後同じ配列を参照で返します。

**引数**

- `$session` (`?array` / 既定値: `null`) — 初回値として使用するセッション配列。`null` の場合は `$_SESSION` を使用します。

**戻り値:** 保持されているセッション配列への参照。

**参照返却:** この関数は戻り値を参照として返します。

**補足**

- 値は `static` 変数に保持されるため、同一リクエスト中の2回目以降の引数は反映されません。

### `get_url()`

```php
function get_url(?string $set_url = null,bool $return_old_value = false) : string
```

`get_base_url()` / `get_site_url()` 用の共通URLアクセサです。

**引数**

- `$set_url` (`?string` / 既定値: `null`) — 設定するURL。空値またはHTTP/HTTPS以外の文字列は設定に使用されません。
- `$return_old_value` (`bool` / 既定値: `false`) — `true` の場合、設定変更前の値を返します。

**戻り値:** 現在値、または `$return_old_value=true` の場合は変更前の値。

**補足**

- `debug_backtrace()` で呼び出し元関数名を判定するため、通常は `get_base_url()` / `get_site_url()` 経由で使用します。

### `get_base_url()`

```php
function get_base_url(?string $set_url = null,bool $return_old_value = false) : string
```

ベースURLを取得します。HTTP/HTTPS URLを渡すと保持値を変更できます。

**引数**

- `$set_url` (`?string` / 既定値: `null`) — 設定するURL。`http://` または `https://` で始まる値のみ設定されます。
- `$return_old_value` (`bool` / 既定値: `false`) — `true` の場合、設定変更前の値を返します。

**戻り値:** ベースURL。

### `get_site_url()`

```php
function get_site_url(?string $set_url = null,bool $return_old_value = false) : string
```

サイトURLを取得します。HTTP/HTTPS URLを渡すと保持値を変更できます。

**引数**

- `$set_url` (`?string` / 既定値: `null`) — 設定するURL。`http://` または `https://` で始まる値のみ設定されます。
- `$return_old_value` (`bool` / 既定値: `false`) — `true` の場合、設定変更前の値を返します。

**戻り値:** サイトURL。

### `get_self_url()`

```php
function get_self_url() : array|string|int|false|null
```

ベースURLと現在の `REQUEST_URI` から現在ページのパス部分を取得します。

**戻り値:** `parse_url(..., PHP_URL_PATH)` の結果。

### `get_route_url()`

```php
function get_route_url($route = '',?array $params = null,$suffix = false) : string
```

ルート名とパラメータからルーティングURLを生成します。

**引数**

- `$route` (`mixed` / 既定値: `''`) — ルート名。空の場合は現在のルートを使用します。
- `$params` (`?array` / 既定値: `null`) — ルートへ渡すパラメータ。
- `$suffix` (`mixed` / 既定値: `false`) — `Route::getPath()` へ渡すサフィックス指定。

**戻り値:** 生成されたルートURL。

**補足**

- `Route::GetInstance(ROUTE_BASE)` に依存します。

### `get_route_tag()`

```php
function get_route_tag(string $route = '') : string
```

URLリライト無効時にルート情報を送信する hidden input タグを生成します。

**引数**

- `$route` (`string` / 既定値: `''`) — ルート名。空の場合は現在のルートを使用します。

**戻り値:** 必要な場合は hidden input タグ。URLリライト有効時は空文字。

**補足**

- URLリライト無効時のみ `energize` というhidden入力を生成します。

### `get_route()`

```php
function get_route() : string
```

現在のルート名を取得します。

**戻り値:** 現在のルート名。

### `get_request_path()`

```php
function get_request_path(?string $request_uri = null) : string
```

リクエストURIからパス部分を取得します。

**引数**

- `$request_uri` (`?string` / 既定値: `null`) — 解析対象のリクエストURI。`null` または空の場合は `$_SERVER["REQUEST_URI"]` を使用します。

**戻り値:** リクエストURIのパス部分。

### `get_form_action_path()`

```php
function get_form_action_path(string $route = '',?array $params = null) : string
```

フォームの action に使用するパスを、ルーティング設定に応じて生成します。

**引数**

- `$route` (`string` / 既定値: `''`) — ルート名。空の場合は現在のルートを使用します。
- `$params` (`?array` / 既定値: `null`) — ルートへ渡すパラメータ。

**戻り値:** フォームaction用パス。

### `get_csrf_tag()`

```php
function get_csrf_tag(mixed $data = null,string $tokenname = 'csrf-tokens',string $name = 'csrf-token') : string
```

CSRFトークンを生成し、hidden input タグとして返します。

**引数**

- `$data` (`mixed` / 既定値: `null`) — CSRFトークン生成時に関連付けるデータ。
- `$tokenname` (`string` / 既定値: `'csrf-tokens'`) — 利用するCSRFトークン管理名。
- `$name` (`string` / 既定値: `'csrf-token'`) — 名前。

**戻り値:** CSRFトークンを含む hidden input タグ。

**補足**

- `CsrfTokens::GetInstance()` に依存します。

### `get_csrf_token()`

```php
function get_csrf_token(mixed $data = null,string $tokenname = 'csrf-tokens') : string
```

CSRFトークン文字列を生成して返します。

**引数**

- `$data` (`mixed` / 既定値: `null`) — CSRFトークン生成時に関連付けるデータ。
- `$tokenname` (`string` / 既定値: `'csrf-tokens'`) — 利用するCSRFトークン管理名。

**戻り値:** 生成されたCSRFトークン。

**補足**

- `CsrfTokens::GetInstance()` に依存します。

## Action / Filter

### `get_action()`

```php
function get_action(string $actionID = '') : Action
```

共有して使用する `Action` インスタンスを取得します。

**引数**

- `$actionID` (`string` / 既定値: `''`) — `Action` インスタンス識別子。初回呼び出し時のみ使用されます。

**戻り値:** 共有 `Action` インスタンス。

**補足**

- 識別子は最初の呼び出しで確定し、以降に別の `$actionID` を渡しても変更されません。

### `add_action()`

```php
function add_action(string $name,callable $callback) : void
```

指定名のアクションにコールバックを登録します。

**引数**

- `$name` (`string`) — 名前。
- `$callback` (`callable`) — 登録・実行に使用するコールバック。

**戻り値:** 戻り値なし。

### `add_actions()`

```php
function add_actions(array $array) : void
```

`アクション名 => コールバック` の配列をまとめて登録します。

**引数**

- `$array` (`array`) — `アクション名 => callable` の連想配列。

**戻り値:** 戻り値なし。

### `clear_actions()`

```php
function clear_actions(string $action_name) : void
```

指定したアクション名の登録内容を削除します。

**引数**

- `$action_name` (`string`) — 削除するアクション名。

**戻り値:** 戻り値なし。

### `do_action()`

```php
function do_action(string $name,array $args = []) : mixed
```

指定したアクションを引数付きで実行します。

**引数**

- `$name` (`string`) — 名前。
- `$args` (`array` / 既定値: `[]`) — アクションへ渡す引数配列。

**戻り値:** `Action::fire()` の戻り値。

### `get_filter()`

```php
function get_filter(string $filterID = '') : Filter
```

共有して使用する `Filter` インスタンスを取得します。

**引数**

- `$filterID` (`string` / 既定値: `''`) — `Filter` インスタンス識別子。初回呼び出し時のみ使用されます。

**戻り値:** 共有 `Filter` インスタンス。

**補足**

- 識別子は最初の呼び出しで確定し、以降に別の `$filterID` を渡しても変更されません。

### `add_filter()`

```php
function add_filter(string $name,callable $callback,int $priority = -1,int $count = -1) : string
```

フィルターを優先度・実行回数付きで登録します。

**引数**

- `$name` (`string`) — 名前。
- `$callback` (`callable`) — 登録・実行に使用するコールバック。
- `$priority` (`int` / 既定値: `-1`) — フィルターの優先度。
- `$count` (`int` / 既定値: `-1`) — 実行回数。`-1` は実装側の既定動作に委ねます。

**戻り値:** `Filter::insert()` が返す識別文字列。

### `addonce_filter()`

```php
function addonce_filter(string $name,callable $callback) : string|false
```

1回だけ実行されるフィルターを末尾に登録します。

**引数**

- `$name` (`string`) — 名前。
- `$callback` (`callable`) — 登録・実行に使用するコールバック。

**戻り値:** `Filter::append()` の結果。文字列または `false`。

### `append_filter()`

```php
function append_filter(string $name,callable $callback,int $count = -1) : string|false
```

フィルターを末尾に登録します。

**引数**

- `$name` (`string`) — 名前。
- `$callback` (`callable`) — 登録・実行に使用するコールバック。
- `$count` (`int` / 既定値: `-1`) — 実行回数。`-1` は実装側の既定動作に委ねます。

**戻り値:** `Filter::append()` の結果。文字列または `false`。

### `prepend_filter()`

```php
function prepend_filter(string $name,callable $callback,int $count = -1) : string|false
```

フィルターを先頭に登録します。

**引数**

- `$name` (`string`) — 名前。
- `$callback` (`callable`) — 登録・実行に使用するコールバック。
- `$count` (`int` / 既定値: `-1`) — 実行回数。`-1` は実装側の既定動作に委ねます。

**戻り値:** `Filter::prepend()` の結果。文字列または `false`。

### `add_filters()`

```php
function add_filters(array $array,int $count = -1) : void
```

`フィルター名 => コールバック` の配列をまとめて末尾へ登録します。

**引数**

- `$array` (`array`) — `フィルター名 => callable` の連想配列。
- `$count` (`int` / 既定値: `-1`) — 実行回数。`-1` は実装側の既定動作に委ねます。

**戻り値:** 戻り値なし。

### `clear_filter()`

```php
function clear_filter(string $filter_name) : void
```

指定したフィルター名の登録内容を削除します。

**引数**

- `$filter_name` (`string`) — 削除するフィルター名。

**戻り値:** 戻り値なし。

### `do_filter()`

```php
function do_filter(string $name,mixed $initial = '') : mixed
```

指定した初期値に対してフィルターを実行し、処理後の値を返します。

**引数**

- `$name` (`string`) — 名前。
- `$initial` (`mixed` / 既定値: `''`) — フィルター処理の初期値。

**戻り値:** フィルター適用後の値。

## General Utilities

### `defineIf()`

```php
function defineIf(string $name,mixed $value) : bool
```

定数が未定義の場合に限り定数を定義します。

**引数**

- `$name` (`string`) — 定数名。
- `$value` (`mixed`) — 定義する値。

**戻り値:** 新規定義に成功した場合 `true`。すでに定義済みなら `false`。

### `get_platform_filename()`

```php
function get_platform_filename(string $filename) : string
```

Windows環境ではUTF-8のファイル名をSJIS-WINへ変換し、それ以外ではそのまま返します。

**引数**

- `$filename` (`string`) — 対象ファイル名。

**戻り値:** プラットフォームに合わせて変換されたファイル名。

### `get_disposition_filename()`

```php
function get_disposition_filename(string $filename) : string
```

HTTPレスポンスのファイル名用途を想定し、一部記号の置換と旧IE向け文字コード処理を行います。

**引数**

- `$filename` (`string`) — 対象ファイル名。

**戻り値:** 変換後のファイル名。

### `rrmdir()`

```php
function rrmdir(string $dir,string $reg_pattern = '') : bool
```

指定ディレクトリ以下を再帰的に削除し、最後にディレクトリ本体を削除します。

**引数**

- `$dir` (`string`) — 対象ディレクトリ。
- `$reg_pattern` (`string` / 既定値: `''`) — 予約引数。現実装では使用されません。

**戻り値:** 最終ディレクトリを削除できた場合 `true`。対象がディレクトリでなければ `false`。

**補足**

- `$reg_pattern` は現在の実装では使用されません。
- 配下のファイル・ディレクトリをすべて削除します。

### `set_windows_console()`

```php
function set_windows_console() : void
```

出力バッファを開始し、出力をUTF-8からSJIS-WINへ変換するコールバックを設定します。

**戻り値:** 戻り値なし。

### `flush_windows_console()`

```php
function flush_windows_console() : void
```

現在の出力バッファを終了し、内容を出力します。

**戻り値:** 戻り値なし。

### `is_zip()`

```php
function is_zip(string $filepath) : bool
```

ファイル先頭2バイトがZIPシグネチャ `PK` かどうかを判定します。

**引数**

- `$filepath` (`string`) — 対象ファイルパス。

**戻り値:** ZIPシグネチャなら `true`。

### `is_compoundfile()`

```php
function is_compoundfile(string $filepath) : bool
```

ファイル先頭8バイトがOLE2複合ファイルシグネチャかどうかを判定します。

**引数**

- `$filepath` (`string`) — 対象ファイルパス。

**戻り値:** OLE2複合ファイルシグネチャなら `true`。

### `is_ssl()`

```php
function is_ssl() : bool
```

サーバー変数から現在のリクエストがHTTPS相当かどうかを判定します。

**戻り値:** HTTPS相当と判定した場合 `true`。

**補足**

- 判定順は `HTTPS` → `SSL` → `HTTP_X_FORWARDED_PROTO` → `HTTP_X_FORWARDED_PORT` → `SERVER_PORT` です。

### `get_filehead()`

```php
function get_filehead(string $filepath,int $num = 0) : string|false
```

ファイルの先頭バイト列、または先頭行を取得します。

**引数**

- `$filepath` (`string`) — 対象ファイルパス。
- `$num` (`int` / 既定値: `0`) — 読み取る先頭バイト数。`0` 以下ではテキストの先頭行を取得します。

**戻り値:** 先頭バイト列または先頭行。対象がファイルでなければ `false`。

**補足**

- `$num > 0` ならバイナリ先頭 `$num` バイト、0以下なら先頭行を `rtrim()` して返します。

### `GetPdoInstance()`

```php
function GetPdoInstance(string $dsn,string $user = '',string $passwd = '',array $options = []) : PDOExtension|false
```

DSN等をキーに `PDOExtension` インスタンスをキャッシュし、同一キーでは再利用します。

**引数**

- `$dsn` (`string`) — PDO接続DSN。
- `$user` (`string` / 既定値: `''`) — データベースユーザー名。
- `$passwd` (`string` / 既定値: `''`) — データベースパスワード。
- `$options` (`array` / 既定値: `[]`) — PDOオプション。独自キー `cache-id` を指定するとキャッシュ識別に利用後、PDOオプションから除去されます。

**戻り値:** `PDOExtension` インスタンス。生成失敗時の初期値は `false`。

**補足**

- キャッシュキーは DSN・ユーザー名・`cache-id` から生成されます。パスワードや他のPDOオプションはキャッシュキーに含まれません。
- `PDO::ATTR_DEFAULT_FETCH_MODE` は常に `PDO::FETCH_ASSOC` に上書きされます。

### `array_inserter()`

```php
function array_inserter(array &$ar,mixed $item,int $pos = 0) : void
```

配列の指定位置へ要素を挿入します。

**引数**

- `$ar` (`array` / 参照渡し) — 変更対象の配列。
- `$item` (`mixed`) — 挿入する値。
- `$pos` (`int` / 既定値: `0`) — 挿入位置。`0` は先頭、負数は末尾、正数はその位置です。

**戻り値:** 戻り値なし。引数配列を直接変更します。

### `array_identical()`

```php
function array_identical(array $array1,array $array2,bool $only_index = true) : bool
```

2つの配列を同一比較します。既定では数値系キーのみを比較対象にします。

**引数**

- `$array1` (`array`) — 比較する配列1。
- `$array2` (`array`) — 比較する配列2。
- `$only_index` (`bool` / 既定値: `true`) — `true` の場合、数値系キーを対象として比較します。

**戻り値:** 比較条件を満たす場合 `true`。

**補足**

- 既定比較では整数キーに加え、正規表現 `/\d+/` に一致する文字列キーも対象になります。
- どちらかの配列が空の場合は `false` です。

### `_array_identical_()`

```php
function _array_identical_(array $a,array $b,bool $r = true) : bool
```

`array_identical()` が数値系キーを双方向比較するために使用する内部ヘルパーです。

**引数**

- `$a` (`array`) — 比較対象配列A。
- `$b` (`array`) — 比較対象配列B。
- `$r` (`bool` / 既定値: `true`) — 内部再帰制御フラグ。

**戻り値:** 数値系キーの比較結果。

**補足**

- 内部利用を想定した関数です。

### `get_temporary_filename()`

```php
function get_temporary_filename(string $prefix = 'auto_',string $suffix = '.dat') : string
```

日付とランダムIDを組み合わせた一時ファイル名を生成します。

**引数**

- `$prefix` (`string` / 既定値: `'auto_'`) — 生成文字列の接頭辞。
- `$suffix` (`string` / 既定値: `'.dat'`) — 生成文字列の接尾辞。

**戻り値:** 生成された一時ファイル名。

### `get_temporary_filepath()`

```php
function get_temporary_filepath(string $savedir,int|false $hint=false) : string
```

保存ディレクトリ内に置く一時ファイルパスを生成します。

**引数**

- `$savedir` (`string`) — 保存先ディレクトリ。
- `$hint` (`int|false` / 既定値: `false`) — 整数なら一時ファイル名へ2桁のヒント値を付加します。`false` なら付加しません。

**戻り値:** 生成された一時ファイルパス。

### `create_path_prefix()`

```php
function create_path_prefix(string $basename) : string
```

ファイル名先頭2文字から保存先ディレクトリ用のプレフィックスを生成します。

**引数**

- `$basename` (`string`) — ベースファイル名。

**戻り値:** 先頭2文字を小文字化したプレフィックス。条件を満たさない場合は `unknown`。

### `create_path()`

```php
function create_path(string $hint,string $rootpath) : string|false
```

ヒント文字列から保存先ディレクトリを決定し、必要なら作成します。

**引数**

- `$hint` (`string`) — 生成や保存先決定に使うヒント値。
- `$rootpath` (`string`) — 保存先ルートディレクトリ。

**戻り値:** 決定したディレクトリパス。不正な競合がある場合 `false`。

**補足**

- ディレクトリ新規作成後に `chmod(..., 0777)` を実行します。

### `create_basename()`

```php
function create_basename(array $_file,string $rootpath) : string
```

アップロードファイル情報から保存用ファイル名を生成し、重複しない名前に調整します。

**引数**

- `$_file` (`array`) — アップロード情報配列。実装では `name` などを展開して使用します。
- `$rootpath` (`string`) — 保存先ルートディレクトリ。

**戻り値:** 保存用の重複しないベースファイル名。

**補足**

- `FileBaseStore::PREG_PATTERN_FS` と `get_exact_filename()` に依存します。

### `array_merge_unless_exists()`

```php
function array_merge_unless_exists(array $src,array $additionals) : array
```

元配列に存在しないキーだけを追加配列から取り込みます。

**引数**

- `$src` (`array`) — 元データ。
- `$additionals` (`array`) — 不足キーとして追加するデータ。

**戻り値:** 不足キーを追加した新しい配列。

### `check_digit_12()`

```php
function check_digit_12(string $num) : string|int
```

12桁の数字文字列から modulus 10 / weight 3-1 系のチェックデジットを計算します。

**引数**

- `$num` (`string`) — 12桁の数字文字列。

**戻り値:** 計算されたチェックデジット。0〜9または `X`。

**補足**

- 入力長が12でない場合は `RuntimeException` を送出します。

### `check_digit_9()`

```php
function check_digit_9(string $num) : int|string
```

9桁の数字文字列から modulus 11 系のチェックデジットを計算します。

**引数**

- `$num` (`string`) — 9桁の数字文字列。

**戻り値:** 計算されたチェックデジット。0〜9または `X`。

**補足**

- 入力長が9でない場合は `RuntimeException` を送出します。

### `asserter()`

```php
function asserter(mixed $mixed,string $message) : void
```

条件を `assert()` で検査し、失敗時に指定メッセージの `RuntimeException` を使用します。

**引数**

- `$mixed` (`mixed`) — 検査または出力対象の値。
- `$message` (`string`) — 例外メッセージ。

**戻り値:** 戻り値なし。

**補足**

- PHPの `assert()` 設定により実行有無が変わります。

## Date / Calendar

### `date_to_empty()`

```php
function date_to_empty(string &$date) : bool
```

未定義日を表す定数値を空文字へ置換します。

**引数**

- `$date` (`string` / 参照渡し) — 変更対象の日付文字列。

**戻り値:** 対象値を空文字へ置換した場合 `true`、それ以外 `false`。

### `Now()`

```php
function Now(string $format = 'Y-m-d H:i:s') : string
```

現在日時を `date()` 形式で返すショートカットです。

**引数**

- `$format` (`string` / 既定値: `'Y-m-d H:i:s'`) — `date()` 互換の書式文字列。

**戻り値:** 現在日時の書式化文字列。

### `get_business_year_range()`

```php
function get_business_year_range(int $byear = 0,?string $fmt = 'Y-m-d') : array
```

指定年度の開始日と終了日をまとめて返します。

**引数**

- `$byear` (`int` / 既定値: `0`) — 年度。`0` の場合は現在年度を使用します。
- `$fmt` (`?string` / 既定値: `'Y-m-d'`) — 出力書式。`null` 等の場合はUNIXタイムスタンプを返す実装です。

**戻り値:** `["s" => 開始, "e" => 終了]` 形式の配列。

### `business_year_start_date()`

```php
function business_year_start_date(int $byear = 0,?string $fmt = 'Y-m-d') : int|string|false
```

指定年度の開始日時を文字列またはUNIXタイムスタンプで返します。

**引数**

- `$byear` (`int` / 既定値: `0`) — 年度。`0` の場合は現在年度を使用します。
- `$fmt` (`?string` / 既定値: `'Y-m-d'`) — 出力書式。`null` 等の場合はUNIXタイムスタンプを返す実装です。

**戻り値:** 書式指定時は日付文字列、`$fmt === null` ならUNIXタイムスタンプ。

### `business_year_end_date()`

```php
function business_year_end_date(int $byear = 0,?string $fmt = 'Y-m-d') : int|string|false
```

指定年度の終了日時を文字列またはUNIXタイムスタンプで返します。

**引数**

- `$byear` (`int` / 既定値: `0`) — 年度。`0` の場合は現在年度を使用します。
- `$fmt` (`?string` / 既定値: `'Y-m-d'`) — 出力書式。`null` 等の場合はUNIXタイムスタンプを返す実装です。

**戻り値:** 書式が空でなければ日付文字列、空ならUNIXタイムスタンプ。

### `business_year_start_month()`

```php
function business_year_start_month(?int $set_month = null) : int
```

年度開始月を取得または設定します。

**引数**

- `$set_month` (`?int` / 既定値: `null`) — 設定する年度開始月（1〜12）。

**戻り値:** 現在の年度開始月。

**補足**

- 初期値は4月です。1〜12以外の値は設定されません。

### `business_year_end_month()`

```php
function business_year_end_month() : int
```

現在設定されている年度開始月から年度終了月を計算します。

**戻り値:** 年度終了月（1〜12）。

### `get_business_year()`

```php
function get_business_year(int|string|null $time = null,int $start_month = 4) : int|false
```

指定UNIX時間が属する年度を返します。

**引数**

- `$time` (`int|string|null` / 既定値: `null`) — UNIXタイムスタンプ。空値の場合は現在時刻を使用します。
- `$start_month` (`int` / 既定値: `4`) — 年度開始月。

**戻り値:** 年度。時刻が0以下の場合は `false`。

**補足**

- `$start_month` の既定値は4で、`business_year_start_month()` の現在設定値とは連動しません。

### `business_date_to_real_date()`

```php
function business_date_to_real_date(int $by,int $bm,int $bd = 1) : array
```

年度年・月を暦上の年・月へ変換します。

**引数**

- `$by` (`int`) — 年度年。
- `$bm` (`int`) — 月。
- `$bd` (`int` / 既定値: `1`) — 日。現実装では戻り値生成に使用されていません。

**戻り値:** `[西暦年, 月]` の2要素配列。

**補足**

- `$bd` は現在の実装では使用されず、戻り値にも日は含まれません。

### `getWeeks()`

```php
function getWeeks(int $start = 0) : array
```

曜日名配列を、指定曜日位置から始まる順序に並べ替えて返します。

**引数**

- `$start` (`int` / 既定値: `0`) — 開始位置。

**戻り値:** 曜日名の配列。

**補足**

- 負数を指定すると `RuntimeException` を送出します。

### `calendar()`

```php
function calendar(int $y,int $m,mixed $option = 0) : array
```

指定年月について、7列×複数行のカレンダー配列を生成します。

**引数**

- `$y` (`int`) — 西暦年。
- `$m` (`int`) — 月。
- `$option` (`mixed` / 既定値: `0`) — 開始曜日位置、コールバック、または `["begin"=>..., "callback"=>...]` 形式の配列。

**戻り値:** 各行7要素の二次元配列。日付が存在しないセルは `null`。

**補足**

- 年は1000〜2100、月は1〜12のみ受け付けます。範囲外は `RuntimeException` です。
- `$option` が配列の場合は `begin` と `callback` キーを直接参照します。

### `get_wareki_year()`

```php
function get_wareki_year(int $y,int $m,int $d,string $unit = '年') : string
```

西暦年月日を和暦の元号＋年の文字列へ変換します。

**引数**

- `$y` (`int`) — 西暦年。
- `$m` (`int`) — 月。
- `$d` (`int`) — 日。
- `$unit` (`string` / 既定値: `'年'`) — 年数の後ろに付ける単位文字列。既定は `年`。

**戻り値:** 例: `令和8年` のような文字列。

### `get_wareki()`

```php
function get_wareki(int $y = 1970, int $m = 1, int $d = 1, array $nengo = ['reiwa' => '令和','showa'=>'昭和','heisei'=>'平成','taisho'=>'大正','meiji'=>'明治']) : array
```

西暦年月日を `[元号, 年, 月, 日]` の和暦情報へ変換します。

**引数**

- `$y` (`int` / 既定値: `1970`) — 西暦年。
- `$m` (`int` / 既定値: `1`) — 月。
- `$d` (`int` / 既定値: `1`) — 日。
- `$nengo` (`array` / 既定値: `['reiwa' => '令和','showa'=>'昭和','heisei'=>'平成','taisho'=>'大正','meiji'=>'明治']`) — 元号表示名の対応配列。

**戻り値:** `[元号, 元号年, 月, 日]`。

**補足**

- 境界日は明治 1868-01-25、大正 1912-07-30、昭和 1926-12-25、平成 1989-01-08、令和 2019-05-01 として実装されています。

### `get_wareki_range()`

```php
function get_wareki_range(string $unit,int $min = 0)
```

指定元号について選択可能な年の範囲を返します。

**引数**

- `$unit` (`string`) — `reiwa` / `heisei` / `showa` / `taisho` / `meiji` など。その他は西暦扱い。
- `$min` (`int` / 既定値: `0`) — 西暦範囲算出時の最小年候補。

**戻り値:** `[開始年, 終了年]`。

**補足**

- 戻り値型宣言はありません。令和の上限は実行時の西暦年から計算します。

### `get_full_age()`

```php
function get_full_age(int $birth) : int
```

生年月日のUNIXタイムスタンプから現在の満年齢を計算します。

**引数**

- `$birth` (`int`) — 生年月日のUNIXタイムスタンプ。

**戻り値:** 現在の満年齢。

**補足**

- 現在日付と生年月日を `Ymd` 整数として差し引く方式です。

### `full_age()`

```php
function full_age(int $birth,string $unit = '') : void
```

満年齢を計算して、その場で出力します。

**引数**

- `$birth` (`int`) — 生年月日のUNIXタイムスタンプ。
- `$unit` (`string` / 既定値: `''`) — 単位または元号種別。

**戻り値:** 戻り値なし。年齢を直接出力します。

## Encryption / Hash / ID

### `get_cipher()`

```php
function get_cipher(string $key,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : ReversibleEncryption
```

暗号鍵ごとに `ReversibleEncryption` インスタンスを生成・キャッシュして返します。

**引数**

- `$key` (`string`) — 暗号鍵。
- `$algo` (`string` / 既定値: `ReversibleEncryption::DEFAULT_ALGORITHM`) — OpenSSL暗号アルゴリズム名。

**戻り値:** 対応する `ReversibleEncryption` インスタンス。

**補足**

- キャッシュキーは `$key` のみです。同じ鍵で異なる `$algo` を指定した場合も最初に生成したインスタンスが再利用されます。

### `str_encrypt()`

```php
function str_encrypt(string $plain,string $key,bool $base64encode = true,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : string|false
```

指定鍵で文字列を可逆暗号化します。

**引数**

- `$plain` (`string`) — 暗号化またはハッシュ化する平文。
- `$key` (`string`) — 暗号鍵。
- `$base64encode` (`bool` / 既定値: `true`) — 暗号結果をBase64エンコードするか。
- `$algo` (`string` / 既定値: `ReversibleEncryption::DEFAULT_ALGORITHM`) — OpenSSL暗号アルゴリズム名。

**戻り値:** 暗号化結果。設定によりBase64文字列または生バイナリ。

### `str_decrypt()`

```php
function str_decrypt(string $encrypted,string $key,bool $base64decode = true,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : string|false
```

指定鍵で暗号文字列を復号します。

**引数**

- `$encrypted` (`string`) — 復号対象の暗号文字列。
- `$key` (`string`) — 暗号鍵。
- `$base64decode` (`bool` / 既定値: `true`) — 入力をBase64デコードしてから復号するか。
- `$algo` (`string` / 既定値: `ReversibleEncryption::DEFAULT_ALGORITHM`) — OpenSSL暗号アルゴリズム名。

**戻り値:** 復号した平文。復号失敗時は下位実装により `false` の可能性があります。

### `get_time_slice_uniqid()`

```php
function get_time_slice_uniqid() : string
```

一定時間区間ごとに変化するMD5形式の識別文字列を生成します。

**戻り値:** 時間区間に依存する32桁MD5文字列。

**補足**

- 時間区間は `FILE_TIME_SLICE` 定義時はその値、未定義時は1800秒です。
- シードには `FILE_SEED`、未定義時は `get_version()` のSHA-256ダイジェストを使用します。

### `str_encrypt_ts()`

```php
function str_encrypt_ts(string $plain,bool $is_hex = true) : string
```

時間区間依存の鍵で文字列を暗号化し、既定では16進文字列へ変換します。

**引数**

- `$plain` (`string`) — 暗号化またはハッシュ化する平文。
- `$is_hex` (`bool` / 既定値: `true`) — `true` なら暗号バイナリを16進文字列に変換して返します。

**戻り値:** 既定では16進文字列化された暗号データ。

**補足**

- 現在の時間区間に依存するため、時間区間が変わると同じ鍵を再現できない設計です。

### `str_decrypt_ts()`

```php
function str_decrypt_ts(string $encrypted,bool $is_hex = true) : string|false
```

時間区間依存の鍵を使って `str_encrypt_ts()` の結果を復号します。

**引数**

- `$encrypted` (`string`) — 復号対象の暗号文字列。
- `$is_hex` (`bool` / 既定値: `true`) — `true` なら入力を16進文字列としてバイナリへ戻してから復号します。

**戻り値:** 復号した平文。失敗時は `false`。

**補足**

- 復号時にも「現在」の時間区間キーを使います。暗号化時と区間が異なると復号できません。

### `blowfish()`

```php
function blowfish(string $plain, int $cost = 4) : string
```

ランダムソルトと指定コストを使い、`crypt()` のBlowfish形式ハッシュを生成します。

**引数**

- `$plain` (`string`) — 暗号化またはハッシュ化する平文。
- `$cost` (`int` / 既定値: `4`) — Blowfishのコスト値。4〜31へ補正されます。

**戻り値:** `crypt()` 互換のBlowfishハッシュ文字列。

**補足**

- コストは4〜31に補正されます。ソルトは `mt_rand()` で生成されます。

### `crypt_blowfish()`

```php
function crypt_blowfish(string $plain,int $cost = 4) : string
```

`blowfish()` の別名ラッパーです。

**引数**

- `$plain` (`string`) — 暗号化またはハッシュ化する平文。
- `$cost` (`int` / 既定値: `4`) — Blowfishのコスト値。4〜31へ補正されます。

**戻り値:** `blowfish()` の戻り値。

### `create_key()`

```php
function create_key(string $hint1,string $hint2,?callable $callable = null) : string
```

2つのヒント値からキー文字列を生成します。生成方法はコールバックで差し替え可能です。

**引数**

- `$hint1` (`string`) — キー生成用ヒント1。
- `$hint2` (`string`) — キー生成用ヒント2。
- `$callable` (`?callable` / 既定値: `null`) — `null` ならSHA-1、文字列 `crypt` ならBlowfish、その他のcallableなら `$hint1, $hint2` を渡して呼び出します。

**戻り値:** 生成されたキー文字列。

**補足**

- `$callable === "crypt"` の場合は `crypt_blowfish("$hint1:$hint2")` を使用します。その他のコールバックには `$hint1, $hint2` を2引数で渡します。

### `scramble()`

```php
function scramble(int $seed)
```

固定された2つの乗数を用いて整数値をスクランブルします。

**引数**

- `$seed` (`int`) — スクランブル対象の整数。

**戻り値:** スクランブル後の整数値。

**補足**

- 戻り値型宣言はありません。演算結果は31bit範囲へマスクされます。

### `str_uniqid()`

```php
function str_uniqid(string $prefix = '',bool $dummy = false) : string
```

ランダム32バイトのSHA-1から識別文字列を生成します。

**引数**

- `$prefix` (`string` / 既定値: `''`) — 生成文字列の接頭辞。
- `$dummy` (`bool` / 既定値: `false`) — 互換用の未使用引数。

**戻り値:** 接頭辞＋40桁SHA-1文字列。

**補足**

- 第2引数 `$dummy` は互換用で、現実装では使用されません。

### `sha256()`

```php
function sha256(string $str,bool $is_bin = false)
```

文字列のSHA-256ハッシュを返します。

**引数**

- `$str` (`string`) — 対象文字列。
- `$is_bin` (`bool` / 既定値: `false`) — `true` の場合はバイナリ形式のハッシュを返します。

**戻り値:** SHA-256ハッシュ。`$is_bin=true` ならバイナリ。

**補足**

- 戻り値型宣言はありません。

## String / Validation / Debug Output

### `__()`

```php
function __(string $text) : void
```

gettext形式の `_()` に文字列を渡し、その結果を直接出力します。

**引数**

- `$text` (`string`) — 翻訳して出力する文字列。

**戻り値:** 戻り値なし。翻訳結果を直接出力します。

### `htmlspecialchars_utf8()`

```php
function htmlspecialchars_utf8(?string $src) : string
```

UTF-8・`ENT_QUOTES` 指定でHTML特殊文字をエスケープします。

**引数**

- `$src` (`?string`) — 元データ。

**戻り値:** HTMLエスケープ済み文字列。

### `str_sanitize()`

```php
function str_sanitize(?string $src) : string
```

`htmlspecialchars_utf8()` のラッパーです。

**引数**

- `$src` (`?string`) — 元データ。

**戻り値:** HTMLエスケープ済み文字列。

### `str_sanitize_decode()`

```php
function str_sanitize_decode(?string $src) : string
```

HTML特殊文字エンティティを `ENT_QUOTES` 指定でデコードします。

**引数**

- `$src` (`?string`) — 元データ。

**戻り値:** HTMLエンティティをデコードした文字列。

### `str_sanitize_html()`

```php
function str_sanitize_html(?string $html,array|string $tags = ['script','object']) : string
```

指定HTMLタグの開始・終了タグだけをエスケープした文字列を返します。

**引数**

- `$html` (`?string`) — 対象HTML文字列。
- `$tags` (`array|string` / 既定値: `['script','object']`) — エスケープ対象HTMLタグ。

**戻り値:** 指定タグだけをエスケープしたHTML文字列。

**補足**

- 指定タグのタグ部分だけをエスケープし、タグ内部の内容自体は残します。一般的なHTMLサニタイザの代替ではありません。

### `str_quotes()`

```php
function str_quotes(?string $str) : string
```

シングルクォートとダブルクォートの前にバックスラッシュを付与します。

**引数**

- `$str` (`?string`) — 対象文字列。

**戻り値:** クォートをバックスラッシュでエスケープした文字列。

### `str_quotes_decode()`

```php
function str_quotes_decode(string $str) : string
```

`str_quotes()` が付与したクォート前のバックスラッシュを除去します。

**引数**

- `$str` (`string`) — 対象文字列。

**戻り値:** クォート前のバックスラッシュを除いた文字列。

### `str_remove()`

```php
function str_remove(string $str,string $pattern = '[^\w\.]') : string
```

指定正規表現パターンに一致する文字を削除します。

**引数**

- `$str` (`string`) — 対象文字列。
- `$pattern` (`string` / 既定値: `'[^\w\.]'`) — 削除対象を表す正規表現パターン本体。

**戻り値:** パターン一致文字を削除した文字列。

**補足**

- `$pattern` には区切り文字 `/` を含めず、正規表現本体を渡す前提です。

### `str_numeric()`

```php
function str_numeric(string $str) : string
```

文字列から0〜9以外の文字を削除します。

**引数**

- `$str` (`string`) — 対象文字列。

**戻り値:** 数字のみの文字列。

### `intval_not_empty()`

```php
function intval_not_empty(?string $str) : ?int
```

空と判定される文字列は `null`、それ以外は整数へ変換して返します。

**引数**

- `$str` (`?string`) — 対象文字列。

**戻り値:** 空値なら `null`、それ以外は整数。

**補足**

- PHPの `empty()` を使用するため、文字列 `"0"` も `null` になります。

### `intval_not_null()`

```php
function intval_not_null(?string $str) : ?int
```

`null` はそのまま `null`、それ以外は整数へ変換して返します。

**引数**

- `$str` (`?string`) — 対象文字列。

**戻り値:** `null` なら `null`、それ以外は整数。

### `nullval_if_empty()`

```php
function nullval_if_empty(?string $str) : ?string
```

空と判定される文字列を `null` に変換します。

**引数**

- `$str` (`?string`) — 対象文字列。

**戻り値:** 空値なら `null`、それ以外は元文字列。

**補足**

- PHPの `empty()` を使用するため、文字列 `"0"` も `null` になります。

### `nullval_if_not_date()`

```php
function nullval_if_not_date(?string $str) : ?string
```

`YYYY-MM-DD` 形式かつ `strtotime()` 可能な日付だけを返し、それ以外は `null` にします。

**引数**

- `$str` (`?string`) — 対象文字列。

**戻り値:** 有効と判定された日付文字列、または `null`。

**補足**

- `/` は `-` に置換してから検証します。正規表現は `YYYY-MM-DD` の桁数だけを確認し、最終判定に `strtotime()` を使います。

### `nullval_if_not_phone()`

```php
function nullval_if_not_phone(?string $str) : ?string
```

ハイフン区切りの数字形式に一致する電話番号文字列だけを返します。

**引数**

- `$str` (`?string`) — 対象文字列。

**戻り値:** 形式に一致する電話番号文字列、または `null`。

### `nullval_if_not_zipcode()`

```php
function nullval_if_not_zipcode(?string $str) : ?string
```

7桁郵便番号形式に一致する文字列だけを返します。

**引数**

- `$str` (`?string`) — 対象文字列。

**戻り値:** 形式に一致する郵便番号文字列、または `null`。

### `nullval_if_not_email()`

```php
function nullval_if_not_email(string $str) : ?string
```

`validate_email()` に合格するメールアドレスだけを返します。

**引数**

- `$str` (`string`) — 対象文字列。

**戻り値:** 検証に成功したメールアドレス、または `null`。

### `str_correct_zipcode()`

```php
function str_correct_zipcode(string $str,bool $has_hyphen = true,int $len = 8) : string
```

郵便番号文字列を半角化・不要文字除去し、指定長以内へ整形します。

**引数**

- `$str` (`string`) — 対象文字列。
- `$has_hyphen` (`bool` / 既定値: `true`) — 郵便番号のハイフンを保持するか。
- `$len` (`int` / 既定値: `8`) — 出力の最大文字数。ハイフンを除去する場合は内部で1減算されます。

**戻り値:** 整形済み郵便番号文字列。

### `bytes()`

```php
function bytes(string $num,int $float = 0,array $unit = ['Byte','KB','MB','GB','TB','PB','EB']) : void
```

バイト数を適切な単位表記に変換して直接出力します。

**引数**

- `$num` (`string`) — 数値または読み取りバイト数。
- `$float` (`int` / 既定値: `0`) — 小数部として残す桁数。
- `$unit` (`array` / 既定値: `['Byte','KB','MB','GB','TB','PB','EB']`) — 単位または元号種別。

**戻り値:** 戻り値なし。変換結果を直接出力します。

### `str_bytes()`

```php
function str_bytes(string $num,int $float = 0,array $unit = ['Byte','KB','MB','GB','TB','PB','EB']) : string
```

バイト数をByte/KB/MB/...形式の文字列へ変換します。

**引数**

- `$num` (`string`) — 数値または読み取りバイト数。
- `$float` (`int` / 既定値: `0`) — 小数部として残す桁数。
- `$unit` (`array` / 既定値: `['Byte','KB','MB','GB','TB','PB','EB']`) — 単位または元号種別。

**戻り値:** 単位付きサイズ文字列。入力に数字以外を含む場合は入力をそのまま返します。

### `extract_unit_size()`

```php
function extract_unit_size(string $num_str) : int|false
```

`1K`、`1.5MB` などの単位付き値をバイト数へ展開します。

**引数**

- `$num_str` (`string`) — 単位付き数値文字列。

**戻り値:** バイト数。形式不一致なら `false`。

### `sanitize_url()`

```php
function sanitize_url(string $path,int $limit = 1) : string
```

`.` / `..` を含むパスを正規化し、上位方向への移動を指定位置で制限します。

**引数**

- `$path` (`string`) — 正規化するパス。
- `$limit` (`int` / 既定値: `1`) — `..` で削除可能なパス位置の下限。

**戻り値:** 正規化したパス文字列。

### `html_sanitize()`

```php
function html_sanitize(array $tags,string $html) : string
```

指定タグをエスケープする `str_sanitize_html()` のラッパーです。

**引数**

- `$tags` (`array`) — エスケープ対象HTMLタグ。
- `$html` (`string`) — 対象HTML文字列。

**戻り値:** 指定タグをエスケープしたHTML文字列。

### `validate_email()`

```php
function validate_email(string $str,bool $is_enable_checkdns = false) : bool
```

メールアドレスの書式を検証し、任意でDNSレコードも確認します。

**引数**

- `$str` (`string`) — 対象文字列。
- `$is_enable_checkdns` (`bool` / 既定値: `false`) — `true` の場合、ドメインのMX/A/NSレコードも確認します。

**戻り値:** 検証に成功した場合 `true`。

**補足**

- 正規表現ではトップレベルドメインを2〜6文字に制限しています。DNS確認時はMX/A/NSのいずれかを確認します。

### `exact_filename()`

```php
function exact_filename(string &$basename,string $dir = '.',string $sep = '_') : int
```

同名ファイルが存在する場合に連番を付け、参照渡しされたファイル名を変更します。

**引数**

- `$basename` (`string` / 参照渡し) — 重複回避対象のファイル名。参照渡しで変更されます。
- `$dir` (`string` / 既定値: `'.'`) — 対象ディレクトリ。
- `$sep` (`string` / 既定値: `'_'`) — 重複時にファイル名と連番の間へ入れる区切り文字。

**戻り値:** 付与した連番。変更不要なら `0`。

**補足**

- `$basename` は参照渡しで変更されます。

### `get_exact_filename()`

```php
function get_exact_filename(string $basename,string $dir = '.',string $sep = '_') : string
```

重複しないファイル名を文字列として返します。

**引数**

- `$basename` (`string`) — ベースファイル名。
- `$dir` (`string` / 既定値: `'.'`) — 対象ディレクトリ。
- `$sep` (`string` / 既定値: `'_'`) — 重複時にファイル名と連番の間へ入れる区切り文字。

**戻り値:** 重複を回避したファイル名。

### `str_format()`

```php
function str_format() : string
```

`{番号:printf書式}` 形式を `vsprintf()` 用の位置指定書式へ変換して整形します。

**引数**

- 可変長引数 — 第1引数に書式文字列、第2引数以降に埋め込み値を渡します。値を1つの配列として渡す形式にも対応します。

**戻り値:** 書式適用後の文字列。

**補足**

- プレースホルダ番号は正規表現上1桁（0〜9）のみ対応します。
- 引数は関数宣言上0個ですが、`func_get_args()` で可変長引数を受け取ります。第2引数として配列1個を渡す形式にも対応します。

### `str_format_escape()`

```php
function str_format_escape() : string
```

`{{` / `}}` をリテラル波括弧として扱える `str_format()` ラッパーです。

**引数**

- 可変長引数 — `str_format()` と同じ形式。`{{` と `}}` はリテラル波括弧として扱われます。

**戻り値:** リテラル波括弧を復元した書式適用後文字列。

**補足**

- 引数は関数宣言上0個ですが、`func_get_args()` で可変長引数を受け取ります。

### `logSQL()`

```php
function logSQL(Store $store,string $eventname) : void
```

`Store` オブジェクトの指定イベントにSQLログ出力用コールバックを登録します。

**引数**

- `$store` (`Store`) — イベントを登録する `Store` オブジェクト。
- `$eventname` (`string`) — SQLログ用コールバックを登録するイベント名。

**戻り値:** 戻り値なし。

**補足**

- ログ出力先は `TEMPORARY_DIR . "/sql.log"` 固定です。

### `print_r_html()`

```php
function print_r_html(array $ar,bool $return = false) : ?string
```

配列の `print_r()` 結果を `<pre>` で囲み、返却または直接出力します。

**引数**

- `$ar` (`array`) — 変更対象の配列。
- `$return` (`bool` / 既定値: `false`) — `true` の場合は文字列を返し、`false` の場合は直接出力します。

**戻り値:** `$return=true` の場合HTML文字列、それ以外は `null`。

### `var_dump_ret()`

```php
function var_dump_ret(mixed $mixed) : string
```

`var_dump()` の出力内容を文字列として取得します。

**引数**

- `$mixed` (`mixed`) — 検査または出力対象の値。

**戻り値:** `var_dump()` の出力文字列。

### `var_dump_html()`

```php
function var_dump_html(mixed $var) : void
```

`var_dump()` の内容をHTMLエスケープし `<pre>` で囲んで出力します。

**引数**

- `$var` (`mixed`) — ダンプ対象の値。

**戻り値:** 戻り値なし。HTMLを直接出力します。

### `var_dump_to()`

```php
function var_dump_to(mixed $mixed,mixed $to = '/dev/null') : void
```

`var_dump()` の内容をファイルパスまたはストリームへ追記します。

**引数**

- `$mixed` (`mixed`) — 検査または出力対象の値。
- `$to` (`mixed` / 既定値: `'/dev/null'`) — 出力先。ファイルパス文字列またはストリームリソース。

**戻り値:** 戻り値なし。

**補足**

- ファイルパス文字列の場合は `FILE_APPEND | LOCK_EX` で追記します。ストリームの場合は `flock()` を使用します。

### `add_space_ifnot_empty()`

```php
function add_space_ifnot_empty(string $str,int $position = 0,string $adding = ' ') : ?string
```

空でない文字列に対して、指定位置へ追加文字列を挿入します。

**引数**

- `$str` (`string`) — 対象文字列。
- `$position` (`int` / 既定値: `0`) — 正数ならその文字オフセットに挿入、負数なら末尾、0なら先頭。
- `$adding` (`string` / 既定値: `' '`) — 追加する文字列。

**戻り値:** 追加処理後の文字列。

### `uuid()`

```php
function uuid() : string
```

UUID v4文字列を生成します。

**戻り値:** UUID v4形式の文字列。

**補足**

- Windowsで `com_create_guid()` が利用可能ならそれを使用し、それ以外は `random_bytes(16)` からRFC 4122互換のversion/variantビットを設定します。

### `str_escape_sql()`

```php
function str_escape_sql($str)
```

SQLエスケープ用として定義されていますが、現在は未実装です。

**引数**

- `$str` (`mixed`) — 対象文字列。

**戻り値:** 現実装では戻り値を返しません（暗黙に `null`）。

**補足**

- 関数本体は `// not implement yet` のみで、SQLエスケープ処理は実装されていません。

## Template Output Helpers

### `base_url()`

```php
function base_url() : void
```

`get_base_url()` の結果を直接出力します。

**戻り値:** 戻り値なし。URLを直接出力します。

### `site_url()`

```php
function site_url() : void
```

`get_site_url()` の結果を直接出力します。

**戻り値:** 戻り値なし。URLを直接出力します。

### `lib_url()`

```php
function lib_url() : void
```

`LIB_URL` 定数を直接出力します。

**戻り値:** 戻り値なし。定数値を直接出力します。

### `site_lib_url()`

```php
function site_lib_url() : void
```

`SITE_LIB_URL` 定数を直接出力します。

**戻り値:** 戻り値なし。定数値を直接出力します。

### `route_url()`

```php
function route_url(?string $route = '',?array $params = null,$suffix = false) : void
```

`get_route_url()` の結果を直接出力します。

**引数**

- `$route` (`?string` / 既定値: `''`) — ルート名。空の場合は現在のルートを使用します。
- `$params` (`?array` / 既定値: `null`) — ルートへ渡すパラメータ。
- `$suffix` (`mixed` / 既定値: `false`) — 生成文字列の接尾辞。

**戻り値:** 戻り値なし。URLを直接出力します。

### `route_tag()`

```php
function route_tag(string $route = '',string $eol = PHP_EOL) : void
```

`get_route_tag()` の結果が空でなければ改行付きで出力します。

**引数**

- `$route` (`string` / 既定値: `''`) — ルート名。空の場合は現在のルートを使用します。
- `$eol` (`string` / 既定値: `PHP_EOL`) — 出力末尾に付加する改行文字列。

**戻り値:** 戻り値なし。タグを直接出力します。

**補足**

- 出力内容が空文字の場合は何も出力しません。

### `request_path()`

```php
function request_path(?string $request_uri = null) : void
```

`get_request_path()` の結果を直接出力します。

**引数**

- `$request_uri` (`?string` / 既定値: `null`) — 解析対象のリクエストURI。`null` または空の場合は `$_SERVER["REQUEST_URI"]` を使用します。

**戻り値:** 戻り値なし。パスを直接出力します。

### `form_action_path()`

```php
function form_action_path(string $route = '',?array $params = null) : void
```

`get_form_action_path()` の結果を直接出力します。

**引数**

- `$route` (`string` / 既定値: `''`) — ルート名。空の場合は現在のルートを使用します。
- `$params` (`?array` / 既定値: `null`) — ルートへ渡すパラメータ。

**戻り値:** 戻り値なし。パスを直接出力します。

### `csrf_tag()`

```php
function csrf_tag(mixed $data = null,string $tokenname = 'csrf-tokens',string $name = 'csrf-token',string $eol = PHP_EOL) : void
```

`get_csrf_tag()` の結果が空でなければ改行付きで出力します。

**引数**

- `$data` (`mixed` / 既定値: `null`) — CSRFトークン生成時に関連付けるデータ。
- `$tokenname` (`string` / 既定値: `'csrf-tokens'`) — 利用するCSRFトークン管理名。
- `$name` (`string` / 既定値: `'csrf-token'`) — 名前。
- `$eol` (`string` / 既定値: `PHP_EOL`) — 出力末尾に付加する改行文字列。

**戻り値:** 戻り値なし。タグを直接出力します。

**補足**

- 出力内容が空文字の場合は何も出力しません。

### `csrf_token()`

```php
function csrf_token(mixed $data = null,string $tokenname = 'csrf-tokens') : void
```

`get_csrf_token()` の結果を直接出力します。

**引数**

- `$data` (`mixed` / 既定値: `null`) — CSRFトークン生成時に関連付けるデータ。
- `$tokenname` (`string` / 既定値: `'csrf-tokens'`) — 利用するCSRFトークン管理名。

**戻り値:** 戻り値なし。トークンを直接出力します。

### `my_title()`

```php
function my_title() : void
```

`title` アクションを実行します。

**戻り値:** 戻り値なし。

### `my_head()`

```php
function my_head() : void
```

`head` アクションを実行します。

**戻り値:** 戻り値なし。

### `my_header()`

```php
function my_header() : void
```

`header` アクションを実行します。

**戻り値:** 戻り値なし。

### `my_footer()`

```php
function my_footer() : void
```

`footer` アクションを実行します。

**戻り値:** 戻り値なし。

## Version

### `version()`

```php
function version(string $before = '?v=',string $after = '') : void
```

現在のバージョン文字列を前後文字列付きで直接出力します。

**引数**

- `$before` (`string` / 既定値: `'?v='`) — バージョン文字列の前に出力する文字列。
- `$after` (`string` / 既定値: `''`) — バージョン文字列の後に出力する文字列。

**戻り値:** 戻り値なし。バージョン文字列を直接出力します。

### `get_version()`

```php
function get_version() : string
```

バージョン文字列を取得します。`.ver` が読める場合は最初の有効行を使用します。

**戻り値:** 取得したバージョン文字列。

**補足**

- 初回取得結果を `static` 変数へキャッシュします。
- .ver` では空行と `#` で始まる行を無視し、最初の有効行を採用します。

## Class: `ReversibleEncryption`

OpenSSLを利用した可逆暗号化クラスです。暗号文の先頭に初期化ベクトル（IV）を連結し、既定では全体をBase64エンコードします。

**定数**

- `DEFAULT_ALGORITHM = "aes-128-cbc"` — 既定の暗号アルゴリズム。

**クラス補足**

- ソース冒頭コメントにはAES-256-CBCとありますが、実装上の既定値は `aes-128-cbc` です。
- コンストラクタでは入力鍵をSHA-256の生バイナリへ変換して内部鍵として保持します。

### `ReversibleEncryption::CreateInitializingVector()`

```php
private function CreateInitializingVector() : string
```

使用中アルゴリズムに必要な長さのランダムIVを生成します。

**戻り値:** 生成したIV文字列。

**補足**

- `random_bytes(openssl_cipher_iv_length(...))` を使用します。
- privateメソッドです。

### `ReversibleEncryption::__construct()`

```php
public function __construct(string $key,string $algorithm = self::DEFAULT_ALGORITHM)
```

暗号鍵とアルゴリズムを設定してインスタンスを初期化します。

**引数**

- `$key` (`string`) — 暗号鍵。
- `$algorithm` (`string` / 既定値: `self::DEFAULT_ALGORITHM`) — OpenSSL暗号アルゴリズム名。

**戻り値:** コンストラクタのため戻り値なし。

**補足**

- 指定アルゴリズムが `openssl_get_cipher_methods(true)` に存在しない場合は `Exception` を送出します。

### `ReversibleEncryption::encrypt()`

```php
public function encrypt(string $plain, bool $base64encode = true) : string
```

平文を暗号化します。IVを暗号文先頭へ連結し、必要に応じてBase64エンコードします。

**引数**

- `$plain` (`string`) — 暗号化またはハッシュ化する平文。
- `$base64encode` (`bool` / 既定値: `true`) — 暗号結果をBase64エンコードするか。

**戻り値:** 暗号化済み文字列。

**補足**

- 既定ではBase64エンコード済み文字列を返します。

### `ReversibleEncryption::decrypt()`

```php
public function decrypt(string $encrypted,bool $base64decode = true) : string
```

暗号文からIVを切り出して復号します。

**引数**

- `$encrypted` (`string`) — 復号対象の暗号文字列。
- `$base64decode` (`bool` / 既定値: `true`) — 入力をBase64デコードしてから復号するか。

**戻り値:** 復号済み平文。空入力では空文字。

**補足**

- `$base64decode=true` の場合は最初にBase64デコードします。
- `openssl_decrypt()` 自体は失敗時に `false` を返し得ますが、メソッド宣言は `string` です。

## Class: `DebugUtils`

ピークメモリ使用量を日時やアクセス情報とともに整形し、標準出力またはファイルへ追記するデバッグユーティリティです。

### `DebugUtils::GetPeekMemoryText()`

```php
private static function GetPeekMemoryText(array $options = []) : string
```

ピークメモリ使用量をログ用文字列へ整形します。

**引数**

- `$options` (`array` / 既定値: `[]`) — オプション配列。

**戻り値:** 整形済みログ文字列。

**補足**

- CLIでは「日時＋ピークメモリ」、Webでは「日時＋IP＋REQUEST_URIのパス＋ピークメモリ」をタブ区切りで生成します。
- private staticメソッドです。

### `DebugUtils::PeekMemoryToFile()`

```php
public static function PeekMemoryToFile(string $filepath = '-',array $options = []) : int|false
```

ピークメモリ情報を標準出力または指定ファイルへ追記します。

**引数**

- `$filepath` (`string` / 既定値: `'-'`) — 対象ファイルパス。
- `$options` (`array` / 既定値: `[]`) — オプション配列。

**戻り値:** ファイル書き込み時は `file_put_contents()` の書き込みバイト数または `false`。標準出力時は実装上の初期値が返されます。

**補足**

- `$filepath === "-"` なら標準出力へ出力します。
- ファイル出力時は `GetPeekMemoryText()` に `$options` を渡していないため、現実装では `$options` は標準出力時のみ反映されます。

## 使用例

### URLアクセサ

```php
$base = get_base_url();
$old  = get_base_url('https://example.com', true);
```

### Action / Filter

```php
add_action('saved', function ($id) {
    // ...
});

do_action('saved', [123]);

add_filter('label', fn($value) => strtoupper($value));
$label = do_filter('label', 'example');
```

### `str_format()`

```php
$text = str_format('{0:s} : {1:04d}', 'ID', 12);
// ID : 0012
```

### 可逆暗号化

```php
$encrypted = str_encrypt('plain text', 'secret-key');
$plain     = str_decrypt($encrypted, 'secret-key');
```

### `ReversibleEncryption`

```php
$cipher = new ReversibleEncryption('secret-key');
$encrypted = $cipher->encrypt('plain text');
$plain = $cipher->decrypt($encrypted);
```
