# functions.php 関数リファレンス

CLI アプリケーション向けの標準入出力、ユーザー入力、ターミナルサイズ取得、罫線表示、ANSI カラー制御などを提供するユーティリティ関数群です。

## 関数一覧

| 関数 | 概要 |
|---|---|
| `erro()` | 標準エラー出力（STDERR）へ文字列を出力 |
| `read_user()` | ユーザー名を入力 |
| `read_passwd()` | パスワードを入力 |
| `read()` | CLI から1行入力。非表示入力にも対応 |
| `UserPass()` | ユーザー名とパスワードをまとめて取得 |
| `promptAndRequire()` | 必須入力を要求し、必要に応じて再入力確認 |
| `confirm()` | `y/N` 形式の確認入力 |
| `tputcols()` | ターミナルの列数を取得 |
| `tputlines()` | ターミナルの行数を取得 |
| `tputsize()` | ターミナルの行数・列数をまとめて取得 |
| `echoline()` | 標準出力へ区切り線を出力 |
| `erroline()` | 標準エラー出力へ区切り線を出力 |
| `tcolor()` | ANSI エスケープシーケンスで文字色・背景色を設定 |
| `tescseq()` | 任意の ANSI エスケープシーケンスを文字列へ付加 |

---

# `erro()`

標準エラー出力 `STDERR` へ文字列を出力します。

## シグネチャ

```php
function erro(string ...$outs): int|false
```

## 引数

| 引数 | 型 | 説明 |
|---|---|---|
| `$outs` | `string ...` | 出力する文字列。複数指定可能 |

複数の引数は連結して出力されます。

## 戻り値

`fputs()` の戻り値をそのまま返します。

| 戻り値 | 説明 |
|---|---|
| `int` | 書き込んだバイト数 |
| `false` | 書き込み失敗 |

## 使用例

```php
erro('Error: ', 'file not found', PHP_EOL);
```

出力先は標準出力ではなく `STDERR` です。

```text
Error: file not found
```

---

# `read_user()`

ユーザー名を標準入力から取得します。

`read()` のラッパー関数です。

## シグネチャ

```php
function read_user(bool $hidden = false): string
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---:|---|
| `$hidden` | `bool` | `false` | 入力内容を画面へ表示しない場合は `true` |

## 戻り値

入力されたユーザー名を返します。

```php
string
```

## プロンプト

```text
dbuser:
```

## 使用例

```php
$user = read_user();
```

非表示入力:

```php
$user = read_user(true);
```

---

# `read_passwd()`

パスワードを標準入力から取得します。

`read()` のラッパー関数です。

## シグネチャ

```php
function read_passwd(bool $hidden = false): string
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---:|---|
| `$hidden` | `bool` | `false` | 入力内容を画面へ表示しない場合は `true` |

## 戻り値

入力されたパスワードを返します。

```php
string
```

## プロンプト

```text
password:
```

## 使用例

通常入力:

```php
$password = read_passwd();
```

入力を非表示にする場合:

```php
$password = read_passwd(true);
```

---

# `read()`

標準入力から1行取得します。

`$hidden` を `true` にすると、パスワードなどの入力内容をターミナルへ表示しないようにできます。

## シグネチャ

```php
function read(
    ?string $prompt,
    bool $hidden = false
): string
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---:|---|
| `$prompt` | `?string` | ― | 入力時に表示するプロンプト |
| `$hidden` | `bool` | `false` | 入力内容を非表示にするかどうか |

## 戻り値

入力された文字列を返します。

```php
string
```

入力末尾の改行文字は含まれません。

## 通常入力

`$hidden === false` の場合は PHP の `readline()` を使用します。

```php
$name = read('name: ');
```

表示例:

```text
name: _
```

## 非表示入力

```php
$password = read('password: ', true);
```

非表示入力時は内部的に次のような処理を行います。

```text
stty -echo
    ↓
STDIN から入力
    ↓
stty echo
```

入力した文字はターミナル上に表示されません。

## 使用例

```php
$user = read('User: ');
$pass = read('Password: ', true);
```

## 注意事項

非表示入力には `stty` コマンドを使用しているため、Unix/Linux 系ターミナル環境を前提とします。

Windows の通常のコマンドプロンプトなどでは、そのまま動作しない可能性があります。

---

# `UserPass()`

ユーザー名とパスワードをまとめて取得します。

引数で値が指定されていない場合のみ、ユーザーへ入力を要求します。

## シグネチャ

```php
function UserPass(
    ?string $user = null,
    ?string $pass = null
): array
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---:|---|
| `$user` | `?string` | `null` | ユーザー名 |
| `$pass` | `?string` | `null` | パスワード |

## 戻り値

ユーザー名とパスワードを含む配列を返します。

```php
[$user, $pass]
```

## 動作

`$user` が空の場合:

```php
$user = read_user();
```

`$pass` が空の場合:

```php
$pass = read_passwd(true);
```

が実行されます。

## 使用例

両方を入力させる場合:

```php
[$user, $pass] = UserPass();
```

ユーザー名のみ指定:

```php
[$user, $pass] = UserPass('root');
```

この場合、パスワードだけが入力要求されます。

両方指定:

```php
[$user, $pass] = UserPass(
    'root',
    'secret'
);
```

この場合、ユーザー入力は発生しません。

## 注意事項

値の判定には `empty()` が使用されています。

そのため、次のような値も「未指定」と判定されます。

```php
null
''
'0'
```

---

# `promptAndRequire()`

必須文字列をユーザーから入力させます。

空文字列の場合は再入力を要求し、指定した回数以内に有効な値が入力されなければ例外を送出します。

確認用の再入力にも対応しています。

## シグネチャ

```php
function promptAndRequire(
    string $prompt,
    bool $hidden = false,
    bool $confirm = false,
    int $loop = 3
): ?string
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---:|---|
| `$prompt` | `string` | ― | 入力時に表示する文字列 |
| `$hidden` | `bool` | `false` | 入力内容を非表示にする |
| `$confirm` | `bool` | `false` | 同じ値の再入力を要求する |
| `$loop` | `int` | `3` | 最大試行回数 |

`$loop <= 0` の場合は `3` 回として扱われます。

## 戻り値

正常に入力された文字列を返します。

```php
?string
```

実装上、正常終了時には文字列が返され、入力に失敗した場合は例外が発生します。

## 入力値の処理

入力値には `mb_trim()` が適用されます。

```php
$rv = mb_trim($rv);
```

空文字列になった場合は再入力になります。

## 確認入力

`$confirm = true` の場合、同じ値をもう一度入力する必要があります。

```text
Password:
retype same:
```

2つの入力値が一致しない場合は無効となり、再試行します。

## 例外

指定回数以内に正常な入力が得られなかった場合:

```php
RuntimeException
```

が送出されます。

例外メッセージ:

```text
falied to required input....
```

## 使用例

通常の必須入力:

```php
$user = promptAndRequire('Username');
```

非表示入力:

```php
$password = promptAndRequire(
    'Password',
    true
);
```

確認入力付き:

```php
$password = promptAndRequire(
    'Password',
    true,
    true
);
```

最大5回まで:

```php
$password = promptAndRequire(
    'Password',
    true,
    true,
    5
);
```

## 依存関数

この関数では:

```php
mb_trim()
```

を使用しています。

この関数は PHP 標準関数ではないため、別途定義されている必要があります。

---

# `confirm()`

ユーザーへ Yes / No の確認を行います。

明示的に `y` または `Y` が入力された場合のみ `true` を返します。

## シグネチャ

```php
function confirm(
    string $prompt,
    string $addition = ' .... is it OK?(y/N)'
): bool
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---|---|
| `$prompt` | `string` | ― | 確認対象となるメッセージ |
| `$addition` | `string` | ` .... is it OK?(y/N)` | プロンプト末尾へ追加する文字列 |

## 戻り値

| 入力 | 戻り値 |
|---|---:|
| `y` | `true` |
| `Y` | `true` |
| `n` | `false` |
| `N` | `false` |
| Enter のみ | `false` |
| その他 | `false` |

つまり、`y` または `Y` の1文字だけが肯定として扱われます。

## 使用例

```php
if (confirm('Delete file')) {
    unlink($filename);
}
```

表示例:

```text
Delete file .... is it OK?(y/N)
```

独自メッセージ:

```php
confirm(
    'Continue',
    ' [y/N]: '
);
```

---

# `tputcols()`

現在のターミナルの列数、つまり横方向のサイズを取得します。

## シグネチャ

```php
function tputcols(): int
```

## 戻り値

ターミナルの列数を整数で返します。

```php
int
```

## 内部処理

次のコマンドを実行しています。

```bash
/usr/bin/env tput cols
```

## 使用例

```php
$cols = tputcols();

echo $cols;
```

出力例:

```text
120
```

## 注意事項

`tput` コマンドが利用できる環境を前提とします。

---

# `tputlines()`

現在のターミナルの行数、つまり縦方向のサイズを取得します。

## シグネチャ

```php
function tputlines(): int
```

## 戻り値

ターミナルの行数を整数で返します。

```php
int
```

## 内部処理

次のコマンドを実行しています。

```bash
/usr/bin/env tput lines
```

## 使用例

```php
$lines = tputlines();

echo $lines;
```

出力例:

```text
40
```

---

# `tputsize()`

現在のターミナルの行数と列数をまとめて取得します。

## シグネチャ

```php
function tputsize(): array
```

## 戻り値

数値インデックスと連想インデックスの両方を持つ配列を返します。

例:

```php
[
    40,
    120,
    'lines' => 40,
    'cols'  => 120
]
```

各値は次の意味になります。

| キー | 内容 |
|---|---|
| `0` | 行数 |
| `1` | 列数 |
| `lines` | 行数 |
| `cols` | 列数 |

## 内部処理

次のコマンドを実行しています。

```bash
/usr/bin/env stty size
```

一般的な出力:

```text
40 120
```

これは:

```text
lines cols
```

の順です。

## 使用例

連想インデックス:

```php
$size = tputsize();

echo $size['lines'];
echo $size['cols'];
```

分割代入:

```php
[$lines, $cols] = tputsize();
```

---

# `echoline()`

標準出力へ区切り線を表示します。

## シグネチャ

```php
function echoline(
    string $char = '-',
    int $repeat = -1
): void
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---:|---|
| `$char` | `string` | `-` | 繰り返す文字列 |
| `$repeat` | `int` | `-1` | 繰り返し回数 |

## 自動幅

`$repeat` が負数の場合:

```php
tputcols() - 1
```

が繰り返し回数として使用されます。

つまり、ターミナルの横幅にほぼ合わせた区切り線を出力します。

## 使用例

```php
echoline();
```

出力例:

```text
------------------------------------------------------------
```

文字変更:

```php
echoline('=');
```

長さ指定:

```php
echoline('-', 20);
```

出力:

```text
--------------------
```

---

# `erroline()`

`echoline()` の標準エラー出力版です。

区切り線を `STDERR` へ出力します。

## シグネチャ

```php
function erroline(
    string $char = '-',
    int $repeat = -1
): void
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---:|---|
| `$char` | `string` | `-` | 繰り返す文字列 |
| `$repeat` | `int` | `-1` | 繰り返し回数 |

`$repeat` が負数の場合:

```php
tputcols() - 1
```

が使用されます。

## 使用例

```php
erroline();
```

エラー表示と組み合わせる例:

```php
erroline('=');
erro('ERROR', PHP_EOL);
erroline('=');
```

---

# `tcolor()`

ANSI エスケープシーケンスを使用して、ターミナル表示用文字列に前景色と背景色を設定します。

この関数自身は文字列を出力せず、ANSI エスケープシーケンスを含む文字列を返します。

## シグネチャ

```php
function tcolor(
    string $text,
    string $fgcolor,
    string $bgcolor = '',
    bool $high = false
): string
```

## 引数

| 引数 | 型 | デフォルト | 説明 |
|---|---|---|---|
| `$text` | `string` | ― | 色を設定する文字列 |
| `$fgcolor` | `string` | ― | 前景色 |
| `$bgcolor` | `string` | `''` | 背景色 |
| `$high` | `bool` | `false` | 高輝度色を使用する場合 `true` |

## 対応する前景色

| 指定値 | 通常 | 高輝度 |
|---|---:|---:|
| `black` / `黒` / `ブラック` | `30` | `90` |
| `red` / `赤` / `レッド` | `31` | `91` |
| `green` / `緑` / `グリーン` | `32` | `92` |
| `yellow` / `黄` / `イエロー` | `33` | `93` |
| `blue` / `青` / `ブルー` | `34` | `94` |
| `magenta` / `マゼンタ` | `35` | `95` |
| `cyan` / `シアン` | `36` | `96` |
| `white` / `白` / `ホワイト` | `37` | `97` |
| `''` | 指定なし | 指定なし |

英語の色名は大文字・小文字を区別しません。

例えば、次の指定は同じ意味になります。

```php
tcolor('Error', 'red');
tcolor('Error', 'RED');
tcolor('Error', '赤');
tcolor('Error', 'レッド');
```

## 対応する背景色

| 指定値 | 通常 | 高輝度 |
|---|---:|---:|
| `black` / `黒` / `ブラック` | `40` | `100` |
| `red` / `赤` / `レッド` | `41` | `101` |
| `green` / `緑` / `グリーン` | `42` | `102` |
| `yellow` / `黄` / `イエロー` | `43` | `103` |
| `blue` / `青` / `ブルー` | `44` | `104` |
| `magenta` / `マゼンタ` | `45` | `105` |
| `cyan` / `シアン` | `46` | `106` |
| `white` / `白` / `ホワイト` | `47` | `107` |
| `''` | 指定なし | 指定なし |

## 高輝度表示

`$high = true` の場合、前景色・背景色の ANSI コードへ `60` を加算します。

例えば:

```text
31 → 91
44 → 104
```

となります。

## 戻り値

ANSI エスケープシーケンスを含む文字列を返します。

```php
string
```

`$text` が空文字列の場合は:

```php
''
```

を返します。

## 使用例

赤文字:

```php
echo tcolor(
    'ERROR',
    'red'
);
```

赤文字 + 白背景:

```php
echo tcolor(
    'ERROR',
    'red',
    'white'
);
```

高輝度の赤:

```php
echo tcolor(
    'ERROR',
    'red',
    '',
    true
);
```

高輝度の白文字 + 青背景:

```php
echo tcolor(
    'INFO',
    'white',
    'blue',
    true
);
```

日本語での指定:

```php
echo tcolor(
    'エラー',
    '赤'
);
```

## 例外

未対応の前景色を指定した場合:

```php
RuntimeException
```

が送出されます。

例外メッセージ:

```text
not support color string
```

未対応の背景色の場合:

```text
not support background color string
```

---

# `tescseq()`

文字列へ任意の ANSI エスケープシーケンスを付加します。

`tcolor()` からも内部的に利用されます。

## シグネチャ

```php
function tescseq(
    string $text,
    int ...$escseq
): string
```

## 引数

| 引数 | 型 | 説明 |
|---|---|---|
| `$text` | `string` | 装飾対象の文字列 |
| `$escseq` | `int ...` | ANSI エスケープコード |

複数の ANSI コードを同時に指定できます。

## 主な ANSI コード

| 値 | 意味 |
|---:|---|
| `0` | Reset |
| `1` | Bold |
| `2` | Dim |
| `4` | Underline |
| `5` | Blink |
| `7` | Inverse |
| `8` | Hidden |

### 通常前景色

```text
30 ～ 37
```

### 高輝度前景色

```text
90 ～ 97
```

### 通常背景色

```text
40 ～ 47
```

### 高輝度背景色

```text
100 ～ 107
```

## 戻り値

ANSI エスケープシーケンスを含む文字列を返します。

```php
string
```

## 使用例

太字:

```php
echo tescseq(
    'Important',
    1
);
```

下線:

```php
echo tescseq(
    'Important',
    4
);
```

太字 + 下線:

```php
echo tescseq(
    'Important',
    1,
    4
);
```

赤文字:

```php
echo tescseq(
    'ERROR',
    31
);
```

太字 + 赤文字:

```php
echo tescseq(
    'ERROR',
    1,
    31
);
```

赤文字 + 白背景:

```php
echo tescseq(
    'ERROR',
    31,
    47
);
```

高輝度赤:

```php
echo tescseq(
    'ERROR',
    91
);
```

## 生成される文字列

例えば:

```php
tescseq(
    'ERROR',
    1,
    31
);
```

は、概念的には次の ANSI シーケンスになります。

```text
ESC[1;31mERRORESC[0m
```

PHP の文字列として表すと:

```php
"\033[1;31mERROR\033[0m"
```

となります。

## `0` の扱い

エスケープコードには:

```php
array_filter($escseq)
```

が適用されています。

そのため `0` は除去されます。

例えば:

```php
tescseq(
    'text',
    0
);
```

では ANSI シーケンスは付加されず:

```text
text
```

がそのまま返ります。

## 空文字列

```php
tescseq(
    '',
    31
);
```

の場合:

```php
''
```

を返します。

---

# 使用例

これらの関数を組み合わせると、CLI プログラムを次のように記述できます。

```php
echoline('=');

echo tcolor(
    'Database Login',
    'cyan',
    '',
    true
), PHP_EOL;

echoline('=');

[$user, $pass] = UserPass();

echo PHP_EOL;

if (!confirm("Login as {$user}")) {
    erro(
        tcolor(
            'Canceled.',
            'red',
            '',
            true
        ),
        PHP_EOL
    );

    exit(1);
}

echo tcolor(
    'Login...',
    'green',
    '',
    true
), PHP_EOL;
```

表示イメージ:

```text
============================================================
Database Login
============================================================
dbuser: root
password:

Login as root .... is it OK?(y/N)y
Login...
```

---

# 動作環境

このファイルには Unix/Linux のターミナル環境へ依存する処理があります。

主に以下の機能・コマンドを利用しています。

```text
STDIN
STDERR
readline()
stty
tput
ANSI escape sequence
```

そのため、主として Unix/Linux 系の PHP CLI 環境での使用を想定しています。

例えば:

```bash
php script.php
```

のような CLI 実行向けのユーティリティです。