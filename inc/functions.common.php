<?php
/*******************************************************************************

  雑多な関数

  All Written by K.,Nakagawa.

*******************************************************************************/

/*------------------------------------------------------------------------------
定義されていなければ定義する。
------------------------------------------------------------------------------*/
function defineIf(string $name,$value)
{
$rv = false;
if(!defined($name))
  $rv = define($name,$value);

return $rv;
}

/*------------------------------------------------------------------------------
  Windowsの場合ファイル名をSJISで返す。
------------------------------------------------------------------------------*/
function get_platform_filename($filename)
{
  return DIRECTORY_SEPARATOR == '\\' ? mb_convert_encoding($filename,'SJIS-WIN','UTF-8') : $filename;
}

/*------------------------------------------------------------------------------
  ファイル名のデコード
------------------------------------------------------------------------------*/
function get_disposition_filename($filename)
{
  $filename = str_replace("?","？",$filename);
  $filename = str_replace("/","／",$filename);
  $filename = str_replace(";","；",$filename);

  if(preg_match("/MSIE/i",$_SERVER['HTTP_USER_AGENT']) && strlen(rawurlencode($filename)) > 21 * 3 * 3)
    {
      $filename = mb_convert_encoding($filename, "SJIS-win","UTF-8");
      $filename = str_replace('#','%23', $filename);
    }

  return $filename;
}

/*------------------------------------------------------------------------------
  ディレクトリの再帰削除
------------------------------------------------------------------------------*/
function rrmdir($dir,$reg_pattern = '')
{
  if(!is_dir($dir))
    return false;

  foreach(scandir($dir) as $filename)
    {
      if(preg_match('/^\.+$/',$filename))
        continue;

      $file = "$dir/$filename";
      if(is_dir($file))
        {
          rrmdir($file);
        }
      else
        {
          unlink($file);
        }
    }

  return rmdir($dir);
}

/*------------------------------------------------------------------------------
  Windowsコマンドコンソール環境用にSJISでバッファリング
------------------------------------------------------------------------------*/
function set_windows_console()
{
  function sjis_buffering($buffer)
    {
      return mb_convert_encoding($buffer,'SJIS-WIN','UTF-8');
    }

  ob_start('sjis_buffering');
}

/*------------------------------------------------------------------------------
  出力バッファ・フラッシュ関数のラッパー
------------------------------------------------------------------------------*/
function flush_windows_console()
{
  ob_end_flush();
}

/*------------------------------------------------------------------------------
  ZIPファイル判別
------------------------------------------------------------------------------*/
function is_zip($filepath)
{
  return 'PK' === get_filehead($filepath,2);
}

/*------------------------------------------------------------------------------
  マイクロソフト OLE2 複合ファイルの判別 (MS Office ファイル等)
------------------------------------------------------------------------------*/
function is_compoundfile($filepath)
{
  return "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" === get_filehead($filepath,8);
}

/*------------------------------------------------------------------------------
  SSLプロトコル判別
------------------------------------------------------------------------------*/
function is_ssl()
{
  $rv = false;
  if ( isset($_SERVER['HTTPS']) === true ) // Apache
    {
      $rv = ( $_SERVER['HTTPS'] === 'on' or $_SERVER['HTTPS'] === '1' );
    }
  elseif ( isset($_SERVER['SSL']) === true ) // IIS
    {
      $rv = ( $_SERVER['SSL'] === 'on' );
    }
  elseif ( isset($_SERVER['HTTP_X_FORWARDED_PROTO']) === true ) // Reverse proxy
    {
      $rv = ( strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https' );
    }
  elseif ( isset($_SERVER['HTTP_X_FORWARDED_PORT']) === true ) // Reverse proxy
    {
      $rv = ( $_SERVER['HTTP_X_FORWARDED_PORT'] === '443' );
    }
  elseif ( isset($_SERVER['SERVER_PORT']) === true )
    {
      $rv = ( $_SERVER['SERVER_PORT'] === '443' );
    }

  return $rv;
}

/*------------------------------------------------------------------------------
  $num が 0以上ならファイルの先頭 $num バイトを返す (バイナリ) 
  その他なら、$filepathをテキストと判断し、先頭行を返す。
------------------------------------------------------------------------------*/
function get_filehead($filepath,$num = 0)
{
  if(!is_file($filepath))
    return false;

  if($num > 0)
    {
      if(false !== ($fh = fopen($filepath,DIRECTORY_SEPARATOR === '\\' ? 'rb' : 'r')))
        {
          fseek($fh,0);
          $rv = fread($fh,$num);
        }
    }
  else
    {
      if(false !== ($fh = fopen($filepath,'r')))
        {
          fseek($fh,0);
          $rv = rtrim(fgets($fh));
        }
    }

  if($fh !== false)
    fclose($fh);

  return $rv;
}


/*-------------------------------------------------------------------------------

  データベースハンドル(PDOオブジェクト)のシングルトン実装

   - 同じDSNのPDOインスタンスは生成しない。
   - PDO派生クラスが存在すればそのクラスのインスタンス生成を優先させる。

------------------------------------------------------------------------------*/
function GetPdoInstance(string $dsn,string $user = '',string $passwd = '',array $options = [])
{
  static $CACHE = [];

  $cacheid = '';
  if(array_key_exists('cache-id',$options) && !empty($options['cache-id']))
  {
    $cacheid = $options['cache-id'];
    unset($options['cache-id']);
  }
  $key = sha1($dsn.$user.$cacheid);

  if(array_key_exists($key,$CACHE))
    return $CACHE[$key];

  $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
  $rv = false;
  try {
    $rv = PDOExtension::GetInstance($dsn,$user,$passwd,$options);
  } catch(Exception $e) {
    $rv = new PDO($dsn,$user,$passwd,$options);
  } finally {
    if(false !== $rv)
      $CACHE[$key] = $rv;
  }

  return $rv;
}

/*------------------------------------------------------------------------------
  任意の位置に要素を挿入する。
------------------------------------------------------------------------------*/
function array_inserter(&$ar,$item,$pos = 0)
{
  count($ar);
  if($pos == 0)
  {
    array_unshift($ar,$item);
  }
  else if($pos < 0)
  {
    $ar[] = $item;
  }
  else
  {
    $removal = array_splice($ar,$pos);
    $ar[] = $item;
    $ar = array_merge($ar,$removal);
  }
}

/*------------------------------------------------------------------------------
  配列の等価比較
  $only_index を false にすると、foreach でハッシュを含むすべて値に対して
  同一比較を行います。
------------------------------------------------------------------------------*/
function array_identical(array $array1,array $array2,$only_index = true)
{
  if(empty($array1) || empty($array2))
    return false;

  return $only_index ? _array_identical_($array1,$array2) : ($array1 === $array2);
}

function _array_identical_(array $a,array $b,$r = true)
{
  $count = 0;
  foreach($a as $i => $v1)
    {
      if(!is_int($i) && !preg_match('/\d+/',$i))
        continue;
      
      if(!array_key_exists($i,$b) || $v1 !== $b[$i])
        return false;

      $count++;
    }

  return $count > 0 && $r === true ?  _array_identical_($b,$a,false) : ($count > 0);
};


/*------------------------------------------------------------------------------
  一時的なワーキングファイル名を生成
------------------------------------------------------------------------------*/
function get_temporary_filename($prefix = 'auto_',$suffix = '.dat')
{
  return $prefix.date('Ymd').str_uniqid().$suffix;
}

/*------------------------------------------------------------------------------
  一時的に使用するファイルパスを生成して返す。
------------------------------------------------------------------------------*/
function get_temporary_filepath($savedir,$hint=false)
{
  $filename = str_uniqid();
  if(is_int($hint))
    $filename = sprintf('%s-%02d',$filename,$hint);

  return sprintf('%s/tmp-%s.dat',$savedir,$filename);
}


/*------------------------------------------------------------------------------
 decide path prefix for save upload files.
------------------------------------------------------------------------------*/
function create_path_prefix($basename)
{
  $rv = 'unknown';
  if(strlen($basename) > 2)
  {
    if(preg_match('/^(\w{2})/',$basename,$match))
      $rv = strtolower($match[1]);
  }
  return $rv;
}

/*------------------------------------------------------------------------------
  decide path for save upload files and make directory.
------------------------------------------------------------------------------*/
function create_path($hint,$rootpath)
{
  if(empty($rootpath) || !is_dir($rootpath))
    $rootpath = '.';

  $dirpath = sprintf('%s/%s', $rootpath, create_path_prefix($hint));

  if(file_exists($dirpath) && !is_dir($dirpath))
    return false;

  if(!file_exists($dirpath))
  {
    mkdir($dirpath);
    chmod($dirpath,0777);
  }

  return $dirpath;
}

/*------------------------------------------------------------------------------
  decide basename of save upload files.
------------------------------------------------------------------------------*/
function create_basename($_file,$rootpath)
{
  //キーをローカル変数に展開
  extract($_file);

  if(empty($name))
  {
    $name = get_temporary_filename();
  }
  else
  {
    //フルパスが入っている場合(主にIE <= 11)、ベース名以外を削除
    $fullpath_ar = preg_split('/[\\/]+/',$name);
    if(count($fullpath_ar) > 1)
      $name = array_pop($fullpath_ar);

    //ファイル名に使かわない文字は削除
    $name = preg_replace(FileBaseStore::PREG_PATTERN_FS,'',$name);

    //ファイル名が空になってしまったら、新たにファイル名を生成
    if(empty($name))
      $name = get_temporary_filename();

    $filename_ext = pathinfo($name,PATHINFO_EXTENSION);
    if(empty($filename_ext))
      $name .= '.dat';
  }

  return get_exact_filename($name,create_path($name,$rootpath));
}

/*------------------------------------------------------------------------------
  merge array if key is not exists
------------------------------------------------------------------------------*/
function array_merge_unless_exists($src,$additionals)
{
  foreach($additionals as $n => $v)
    if(!array_key_exists($n,$src))
      $src[$n] = $v;

  return $src;
}


/*------------------------------------------------------------------------------
  calculate check digit modulus10w31
------------------------------------------------------------------------------*/
function check_digit_12($num)
{
  if(strlen($num) != 12)
    throw new RuntimeException('invalid parameter');

  $arr = array_reverse(str_split($num));
  $t = 0;
  for($i=0;$i<count($arr);$i++)
    $t += ( ($i+1) % 2) == 0 ? intval($arr[$i]) : intval($arr[$i])*3;

  $cd = $t % 10;
  if($cd > 0)
    $cd = 10 - $cd;

  if($cd == 10)
    $cd = 'X';

  return $cd;
}

/*------------------------------------------------------------------------------
  calculate check digit modulus11w102
------------------------------------------------------------------------------*/
function check_digit_9($num)
{
  if(strlen($num) != 9)
    throw new RuntimeException('invalid parameter');

  $arr = str_split($num);
  $t = 0;
  for($i=0;$i<9;$i++)
    $t += (10 - $i)*$arr[$i];

  $cd = 11 - ($t % 11);
  if($cd == 11)
    $cd = 0;
  else if($cd == 10)
    $cd = 'X';

  return $cd;
}

/*------------------------------------------------------------------------------
  helper assert
------------------------------------------------------------------------------*/
function asserter($mixed,$message)
{
  assert($mixed,new RuntimeException($message));
}
