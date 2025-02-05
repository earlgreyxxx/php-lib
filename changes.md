# Changes

## 2025年2月05日
1. 列挙に関する仕様を変更しました。
2. タイプミスを修正

## 2025年1月31日
1. promptAndRequire関数において空の判定を文字数のカウントで判定するように変更。

## 2025年1月03日
1. DB::valuesメソッドを拡張し、型を判別して適切な値に修正するようにするオプションを追加

## 2024年12月04日
1. php8.4から出るデフォルト引数の暗黙的なnull受入に関するDeprecatedワーニングの対応

## 2024年12月03日
1. mb_trim関数がphp8.4から標準実装になったことによる対応

## 2024年5月18日
1. DBクラスにDistinctCount メソッドを追加しました。

## 2024年5月18日
1. セッションの更新の際のsession_regenrate_idをコールする際、セッションを削除しないように修正しました。
2. 上記、取消。

## 2024年3月19日
1. DBクラスのバグ（ワーニング）を修正しました。

## 2024年2月13日
1. DBクラスのバグ（ワーニング）を修正しました。

## 2023年10月11日
1. htmlspecialchars関数に渡す変数がnullの場合は空文字に変換して渡すように修正しました。

## 2023年8月02日
1. uniqid関数の代替、str_uniqid(関数プロトタイプ一致)をコアライブラリに追加し、置き換えました。
2. openssl_random_pseudo_bytes関数の使用を中止し、random_bytes関数に変更しました。

## 2023年7月30日
1. Sessionクラスのsamesite属性のデフォルト値を Lax に変更しました。
2. Sessionクラスで使用している setcookie関数を Cookie::Raw関数に変更しました。
3. PHPバージョンの分岐に PHP_VERSION_ID を使用するようにしました。

## 2023年7月011日
1. Cookie,SessionクラスのCookie属性のうち、samesite属性のデフォルト値を Strict に設定するようにしました。

## 2023年7月01日
1. jQueryのバージョンを3.7.0へ更新。また、ファイル名を変更しました。

## 2023年6月07日
1. PDOExtensionクラスにテーブルを削除するdrop,dropsメソッドを追加しました。
2. PDOExtensionクラスにtruncateメソッドを追加しました。

1. DatabaseRowsクラスに下記修正
   - コンストラクタパラメータに'id-column'を定義し、コンストラクタ内で setIdColumnメソッドをコールするように修正しました。
   - コンストラクタパラメータ 'row-class' を クラス名からDatabaseRowのインスタンスを生成する関数を保持するように変更しました。
   - アクセサの実装を変更しました。

## 2023年6月03日
1. PDO*クラスに外部キー制約を設定するsetForeignKeyConstraintメソッドを追加しました。

## 2023年06月01日
1. Routeクラスのバグを修正しました。
2. Metaクラスのバグを修正しました。
3. get_route_* 関数のwarning,deprecated警告が出ないように修正しました。
4. 定数の再定義時の警告を出ないように修正しました。
5. 設定のロード方法を修正しました。

## 2023年05月30日
1. PDOMysql::getColumnsメソッドで列名取得の際のバグを修正しました。

## 2023年05月26日
1. DatabaseRow::fetchメソッドを追加しました。
 
## 2023年05月21日
1. DatabaseRow::loadメソッドは常にbool値を返すように変更し、存在チェックがtrueの場合に例外を投げるようにしました。
2. DatabaseRow::_imp_load_from_uniqメソッドでデータベースにない場合は例外を投げるようにしました。
3. DatabaseRowsクラス PHP7系列への対応しました。

## 2023年05月21日
1. chromeのみHTTP認証を解除できるようにViewBase::error401を新規に追加しました。
2. ViewBase::error_codeメソッドを追加し、errorXXXメソッドはラッパーメソッドに変更した。

## 2023年05月19日
1. depregated error を修正
2. DatabaseRow 空のインスタンスを生成できるように修正しました。
3. DatabaseRow _imp_load_from_uniqメソッドを追加し、_imp_load_from_pkはラッパーメソッドに変更しました。

## 2023年05月16日
1. depregated error を修正
2. init_namespace_autoload関数を追加し、名前空間が定義されたクラス等に対応した。

## 2023年02月13日
1. memory_limit の値を変更しました。

## 2023年02月12日
1. ダイジェスト認証を行う HttpAuthenticatio/AccountHtdigestクラスの実装を修正しました。

## 2023年02月12日
1. 複数のinterfaceを定義しているファイルをinterface毎に分割しlib/coreに配置するようにした。
2. 上記に伴い、lib/inc/interfaces.phpを削除しました。

## 2023年01月19日
1. PageTemplate::paginationメソッドのプロトタイプ変更。1ページのみの場合出力しないオプションを追加。

## 2023年01月18日
1. RowsIterator/RowsGeneratorクラスにrewindメソッドを追加しました。

## 2023年01月07日
1. [php8] #[\ReturnTypeWillChange] 対策

## 2022年12月27日
1. jquery.utility.jsに新たにcreateQueryStringEx/getQueryStringExを追加。createQueryString/getQueryString関数はラッパー関数に変更しました。

## 2022年12月11日
1. [php8] depregated エラー修正

## 2022年12月11日
1. ApcuCacheクラス、list関数使用時のバグを修正しました。

## 2022年12月11日
1. ViewBaseクラス、list関数使用時のバグを修正しました。

## 2022年11月29日
1. DBクラス、list関数使用時のバグを修正しました。

## 2022年09月22日
1. memory_limitの値を32M にしました。
2. expose_php の値をoffにしました。

## 2022年09月20日
1. [php8] PDOSqlserver::quote オーバーライドメソッド引数の型を追加
2. [php8] KeyValueCollectionで実装している ArrayAccessインターフェイスのメソッド引数の型を修正
3. [php8] #[\ReturnTypeWillChange] 対策

## 2022年09月15日
1. ReversibleEncryption::DEFULT_ARGORITHMの値を aes-128-cbc に変更しました。

## 2022年09月02日
1. Route::getPathメソッドにおいて引数ありのルートにおいてパラメーターが空である場合、例外を投げるように修正しました。(PHP8系対応)

## 2022年07月06日
1. DBクラスのバグを修正しました。

## 2022年07月3日
1. Cookieクラスのデフォルトパスを定数BASE_URLが定義されていればBASE_URLに変更

## 2022年06月29日
1. erroline,echoline 関数を追加しました。

## 2022年05月26日
1. 定義されていなければ定義する、defineIf関数を追加した。

## 2022年04月01日
1. GetPdoInstance関数に一度作成したPDOオブジェクトをキャッシュするように修正しました。

## 2022年03月15日
1. ターミナルのサイズを取得する関数を追加しました。  
   tputcols,tputlines
2. tputsize関数を追加。cols,linesを配列として一度にサイズを返す。

## 2022年03月11日
1. echo の 標準エラー出力版の erro 関数を作成し追加しました。 (lib/ext/CLI)
2. 出力を標準エラー出力に変更しました。(lib/ext/CLI)

## 2022年01月24日
1. DB::getIteratorメソッドを追加しました。(mode=select時)
2. DB::select()メソッドのシグネチャを変更し、columns引数を指定できるようにした。
3. Cookieクラスコンストラクタ内のバグを修正しました。

## 2021年09月03日
1. CodebaseRow(extends DatabaseRow) を追加しました。
2. HttpAuthentication::Invokeメソッドにおいて結果を返すようにreturn文を追加しました。

## 2021年04月07日
1. CsrfTokenの各フィールドのアクセス修飾子をprotectedに変更しました。

## 2021年04月02日
1. JSライブラリの管理をnpmにしました。

## 2021年03月13日
1. Responseクラス内のタイプミスを修正しました。
2. jQuery.valilidtyプラグインにおいて要素のdata-validity-message属性から表示するメッセージを指定できるようしました。

## 2020年12月04日
1. TemplateBase::setRowsメソッドでRowsGeneratorオブジェクトを受入れるように修正しました。

## 2020年12月01日
1. assert関数へのラッパー関数asserterを追加しました。

## 2020年11月24日
1. get_route関数でプライベートフィールドを直接参照していたバグを修正しました。

## 2020年11月17日
1. CsrfToken::cleanupメソッドの配列のキーチェックを追加しました。

## 2020年11月12日
1. RowsIterator::getRowメソッドのバグを修正した。

## 2020年11月09日
1. Responseクラスに content_typeメソッドへの以下のラッパーメソッドを追加した。
   * html / plain / css / javascript
   * json / jsonp / pdf / raw

## 2020年10月23日

1. DatabaseRowsクラスに protected getGeneratorメソッドを追加した。
2. 1.に伴い全ての行を返すジェネレータ public rowsメソッドを追加した。
3. RowsGeneratorクラスを追加した。
4. RowsIteratorクラスとの共通メソッドをRowsIteratorImpトレイトに分離した。
5. 上記に伴い trait RowsItereatorImpを追加した。
