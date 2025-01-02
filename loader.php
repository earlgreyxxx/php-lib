<?php
/*******************************************************************************

  設定及び、ライブラリのロード

*******************************************************************************/
ini_set('memory_limit', '256M');
ini_set('expose_php','off');

mb_language('Japanese');
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

setlocale(LC_ALL, 'ja_JP.UTF-8');

if(file_exists(__DIR__ . '/../site/locale/'))
{
  bindtextdomain('messages',__DIR__ . '/../site/locale/');
  textdomain('messages');
}

// 定数定義
define('RN',PHP_EOL);
define('CRLF',"\r\n");
define('DLMTCSV',',');
define('DLMTREG','/[\s,;]+/');

try {
  // 設定のロード
  require __DIR__ . '/../conf/config.php';

  define('LIB_URL',BASE_URL . '/lib');
  define('SITE_LIB_URL',SITE_URL . '/lib');

  // 初期化・ライブラリのロード
  define('LIB_INC_DIR',__DIR__.'/inc');
  define('LIB_DIR',__DIR__);
  define('LIB_CORE_DIR',__DIR__.'/core'.(file_exists(__DIR__.'/core.compressed') ? '.compressed' : ''));


  define('SITE_LIB_DIR',SITE_DIR . '/lib');
  define('SITE_LIB_INC_DIR',SITE_LIB_DIR.'/inc');
  define('SITE_LIB_APP_DIR',SITE_LIB_DIR.'/app'.(file_exists(SITE_LIB_DIR.'/app.compressed') ? '.compressed' : ''));

  init_autoload([LIB_CORE_DIR,LIB_DIR.'/ext']);
  include_files(LIB_INC_DIR);
  include_files(SITE_LIB_INC_DIR);

  if(function_exists('site_init'))
    site_init();
} catch(Exception $e) {
  header('HTTP/1.1 500 Internal Server Error');
  header('Content-type: text/plain');
  echo $e->getMessage();
}

/*------------------------------------------------------------------------------
  ディレクトリ以下の*.phpに対してrequire_once関数を実行します。
------------------------------------------------------------------------------*/
function include_files($dirName)
{
  if(!file_exists($dirName))
    return false;

  $flags = FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::SKIP_DOTS; 
  $rdite = new RecursiveDirectoryIterator($dirName,$flags);
  $it = new RegexIterator(new RecursiveIteratorIterator($rdite),'/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
  
  foreach($it as $ar)
    require_once $ar[0];

  return true;
}

/*------------------------------------------------------------------------------
  autoload 登録
------------------------------------------------------------------------------*/
function init_autoload(array $rootdirs)
{
  foreach($rootdirs as $rootdir)
  {
    if(!file_exists($rootdir))
      continue;

    spl_autoload_register(function($name) use($rootdir) {
      $phpfile1 = sprintf('%s/class.%s.php',$rootdir,$name);
      $phpfile2 = sprintf('%s/trait.%s.php',$rootdir,$name);
      $phpfile3 = sprintf('%s/interface.%s.php',$rootdir,$name);
      if(file_exists($phpfile1))
        require $phpfile1;
      else if(file_exists($phpfile2))
        require $phpfile2;
      else if(file_exists($phpfile3))
        require $phpfile3;
    });
  }
}
