<?php
/*******************************************************************************

  テキスト処理に関するヘルパー関数定義

  All Written by K.,Nakagawa.

*******************************************************************************/

/*------------------------------------------------------------------------------
  shortcut of "echo _();"
------------------------------------------------------------------------------*/
function __($text)
{
  echo _($text);
}

/*------------------------------------------------------------------------------
 サニタイズ
------------------------------------------------------------------------------*/
function htmlspecialchars_utf8($src)
{
  return htmlspecialchars($src ?? '',ENT_QUOTES,'UTF-8');
}
function str_sanitize($src)
{
  return htmlspecialchars_utf8($src);
}
function str_sanitize_decode($src)
{
  return htmlspecialchars_decode($src ?? '',ENT_QUOTES);
}
function str_sanitize_html($html,$tags = array('script','object'))
{
  $rv = '';
  if(!is_array($tags) && is_string($tags))
    $tags = preg_split('/[\s,;]+/',$tags);

  foreach($tags as $tag)
    {
      $pattern = sprintf('/<%s(\s*.*?)>(.*?)<\/%s>/is',$tag,$tag);
      $replace = sprintf('&lt;%s$1&gt;$2&lt;/%s&gt;',$tag,$tag);

      $rv = preg_replace($pattern,$replace,$html);
    }

  return str_replace("\\\"","\"",$html);
}

// 引用符・二重引用符にバックスラッシュを付加またはデコードします。
function str_quotes($str)
{
  static $from = array('\'','"');
  static $to   = array("\\'","\\\"");

  return str_replace($from,$to,$str);
}
function str_quotes_decode($str)
{
  static $from   = array("\\'","\\\"");
  static $to = array('\'','"');

  return str_replace($from,$to,$str);
}

// 英数字、ピリオド以外は除去
function str_remove($str,$pattern = '[^\w\.]')
{
  $regstr = sprintf('/%s/',$pattern);
  return preg_replace($regstr,'',$str);
}

// 数字以外は除去
function str_numeric($str)
{
  return str_remove($str,'[^0-9]');
}

function intval_not_empty($str)
{
  return empty($str) ? null : intval($str);
}

function intval_not_null($str)
{
  return $str === null ? null : intval($str);
}

function nullval_if_empty($str)
{
  return empty($str) ? null : $str;
}

function nullval_if_not_date($str)
{
  $str = str_replace('/','-',$str);
  return preg_match('/^\d{4}-\d{2}-\d{2}$/',$str) && intval(strtotime($str)) > 0 ? $str : null;
}
function nullval_if_not_phone($str)
{
  return preg_match('/^(?:\d{1,4}-)+?\d{1,4}$/',$str) ? $str : null;
}
function nullval_if_not_zipcode($str)
{
  return preg_match('/^\d{3}-?\d{4}$/',$str) ? $str : null;
}
function nullval_if_not_email($str)
{
  return validate_email($str) ? $str : null;
}

/*------------------------------------------------------------------------------
  郵便番号のバリデーション (数字とハイフン以外を除去 及び 半角化)
  $has_hypen : ハイフンを保持する？ $len : 文字数制限値
------------------------------------------------------------------------------*/
function str_correct_zipcode($str,$has_hyphen = true,$len = 8)
{
  $rv = $str;
  $re = $has_hyphen ? '/[^\d\-]/' : '/[^\d]/';

  $rv = str_replace(array('－','ー'),'-',$rv);
  $rv = mb_convert_kana($rv,'kn');
  $rv = preg_replace($re,'',$rv);

  if($has_hyphen == false)
    $len--;

  if(strlen($rv) > $len)
    $rv = substr($rv,0,$len);

  return $rv;
}

/*------------------------------------------------------------------------------
  数値をKB,MB,GB単位にして表示します。

  $num   : 変換する数値
  $float : 小数点以下何桁？
  $unit  : 単位文字列(1024^0,1024^1,1024^2,1024^3,....)
------------------------------------------------------------------------------*/
function bytes($num,$float = 0,$unit = array('Byte','KB','MB','GB','TB','PB','EB'))
{
  echo str_bytes($num,$float,$unit);
}

function str_bytes($num,$float = 0,$unit = array('Byte','KB','MB','GB','TB','PB','EB'))
{
  if(preg_match('/\D/',$num))
    return $num;

  $limit = count($unit) - 1;
  $i = 0;
  while(strlen(floor($num)) > 3 && $i <= $limit)
    {
      $num = $num / 1024;
      $i++;
    }

  $ar = explode('.',$num);
  $add = '';
  if($float > 0 && isset($ar[1]))
    {
      $ar[1] = substr($ar[1],0,$float);
      if(intval($ar[1]) > 0)
        $add = '.'.$ar[1];
    }

  return $ar[0] . $add . $unit[$i];
}

/*------------------------------------------------------------------------------
  単位を展開する。
------------------------------------------------------------------------------*/
function extract_unit_size($num_str)
{
  $rv = false;
  static $unit_base = array( 'k' => 1,'m' => 2,'g' => 3,'t' => 3,'p' => 4,'e' => 5 );

  if(preg_match('/^(\-?\d+)([kmgtpe])$/i',$num_str,$m))
    {
      $num = $m[1];
      $unit = strtolower($m[2]);
      $times = 1;

      $rv = $num * pow(1024,$unit_base[$unit]);
    }

  return $rv;
}

/*------------------------------------------------------------------------------
  相対アドレスが混じったパスを正規のパスに変換する
  $limitは、パス区切り文字の位置に変換制限をかける。
------------------------------------------------------------------------------*/
function sanitize_url($path,$limit = 1)
{
  $rva = array();
  $names = preg_split(sprintf('/[%s]/',preg_quote('\/','/')),$path);

  $pos = 0;
  foreach($names as $name_)
    {
      switch($name_)
        {
        case '..':
          if($pos > $limit)
            unset($rva[$pos--]);
          break;

        case '.':
          break;

        default:
          $rva[$pos++] = $name_;
        }
    }

  return implode('/',$rva);
}

/*------------------------------------------------------------------------------
  $contentから$tagsで指定したHTMLタグをエスケープする
------------------------------------------------------------------------------*/
function html_sanitize($tags,$html)
{
  if(empty($html))
    return '';

  return str_sanitize_html($html,$tags);
}


/*------------------------------------------------------------------------------
  メールアドレスの検査
------------------------------------------------------------------------------*/
function validate_email($str,$is_enable_checkdns = false)
{
  static $pattern = '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/iD';

  $rv = !empty($str) && preg_match($pattern, $str);
  if($rv && $is_enable_checkdns)
    {
      list($username,$domain) = explode('@',$str,2);
      $rv = (checkdnsrr($domain,'MX') || checkdnsrr($domain,'A') || checkdnsrr($domain,'NS'));
    }

  return $rv;
}

/*------------------------------------------------------------------------------
  すでにそのファイル名が存在すれば、名前に番号を振った名前に変更する。
  その番号を返す。
------------------------------------------------------------------------------*/
function exact_filename(&$basename,$dir = '.',$sep = '_')
{
  $count = 0;

  if(file_exists(get_platform_filename("$dir/$basename")))
    {
      $fmt = "%s$sep%d.%s";
      $pinfo = pathinfo($basename);
      if(!isset($pinfo['extension']))
        $pinfo['extension'] = '';

      $fmt = sprintf('%s%s%%d.%s',$pinfo['filename'],$sep,$pinfo['extension']);

      do {
        $basename = sprintf($fmt,++$count);
      } while(file_exists(get_platform_filename("$dir/$basename")));
    }

  return $count;
}

function get_exact_filename($basename,$dir = '.',$sep = '_')
{
  exact_filename($basename,$dir,$sep);
  return $basename;
}


/*------------------------------------------------------------------------------

 str_format($fmt,$param1,$param2,$param3,...)

 sprintfの代替関数

 フォーマット文字列には、フォーマット記述子を"%数字$書式指定"ではなく
 "{数字:printf関数の書式指定子}" の形式で指定するためのヘルパー。

 第二引数以降は、埋め込む値のリスト。

 $format     : '{index:printf format operator}'形式の書式指定子を含んだテキスト
 $value1,... : 埋め込む値のリスト

 $formatに'{}'波カッコ文字を含めたい場合は それぞれ'{{','}}'として、
 str_format_escape関数をコールしてください。

【戻り値】
 フォーマット適用後のテキストが返ります。
 ※変換処理後の$formatにスカラリファレンスを渡すと $format 自体を変更します。

------------------------------------------------------------------------------*/
function str_format()
{
  $params = func_get_args();
  $fmt = array_shift($params);
  if(count($params) == 1 && is_array($params[0]))
    $params = $params[0];

  return vsprintf(preg_replace_callback('/\{(\d)(:[\w\+\-\. #]+?)?\}/',
                                        function($m)
                                        {
                                          $order = intval($m[1]) + 1;

                                          //フォーマット指定子が無い場合のデフォルトは 's' を使用する。
                                          $f = isset($m[2]) && !empty($m[2]) ? ltrim($m[2],':') : 's';

                                          return '%' . $order . '$' . $f;
                                        },
                                        $fmt),
                  $params);
}
function str_format_escape()
{
  $params = func_get_args();
  $fmt = array_shift($params);
  $b = chr(145);
  $e = chr(146);

  //前処理
  $fmt = str_replace('{{',$b,$fmt);
  $fmt = preg_replace('/}}([^}]|\Z|\z)/',"$e$1",$fmt);

  return str_replace(array($b,$e),array('{','}'),str_format($fmt,$params));
}

/*------------------------------------------------------------------------------
 デバッグ出力
-------------------------------------------------------------------------------*/
function logSQL($store,$eventname)
{
  $store->on($eventname,
             function($type,$sql)
             {
               file_put_contents(TEMPORARY_DIR.'/sql.log',
                                 $sql."\n",
                                 FILE_APPEND);
             });
}
function print_r_html($ar,$return = false)
{
  $rv = sprintf('<pre>%s</pre>',print_r($ar,true));
  if($return)
    return $rv;
  else
    echo $rv;
}
function var_dump_ret($mixed)
{
  ob_start();
  var_dump($mixed);
  $content = ob_get_contents();
  ob_end_clean();

  return $content;
}
function var_dump_html($var)
{
  $content = var_dump_ret($var);
  if(!empty($content))
    echo '<pre>',htmlspecialchars($content,ENT_QUOTES),'</pre>';
}
function var_dump_to($mixed,$to = '/dev/null')
{
  $caller = debug_backtrace();
  $content = var_dump_ret($mixed);

  // no action if content is empty.
  if(empty($content))
    return;

  $output = array();
  $output[] = str_repeat('-',70);
  $output[] = sprintf(' * %s  function: %s',date('Y-m-d H:i:s'),$caller[1]['function']);
  $output[] = str_repeat('-',70);
  $output[] = $content;
  $output = implode("\n",$output) . "\n";

  if(is_string($to))
  {
    file_put_contents($to,$content,FILE_APPEND | LOCK_EX);
  }
  else if(is_resource($to))
  {
    if('stream' === get_resource_type($to))
    {
      try {
        flock($to,LOCK_EX);
        fseek($to,0,SEEK_END);
        if(false === fwrite($to,$content))
          throw new Exception(_('File access is denied'));
      }
      catch(Exception $e)
      {
        flock($to,LOCK_UN);
        throw $e;
      }
      fflush($to);
      flock($to,LOCK_UN);
    }
  }

}

/*------------------------------------------------------------------------------
  append or prepend space if not empty.
------------------------------------------------------------------------------*/
function add_space_ifnot_empty($str,$position = 0,$adding = ' ')
{
  $rv = $str;
  if(is_string($str) && !empty($str))
  {
    if($position > 0)
      $rv = substr($str,0,$position) . $adding . substr($str,$position);
    else if($position < 0)
      $rv = $str . $adding;
    else
      $rv = $adding . $str;
  }

  return $rv;
}

/*------------------------------------------------------------------------------
  multi byte trim (add zenkaku space)
------------------------------------------------------------------------------*/
if(!function_exists('mb_trim'))
  include_once __DIR__ . '/../ext/function.mb_trim.php';

/*-------------------------------------------------------------------------------
  UUID v4 generator
------------------------------------------------------------------------------*/
function uuid()
{
  if(function_exists('com_create_guid') === true)
    return trim(com_create_guid(), '{}');

  $data = random_bytes(16);
  $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
  $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
  return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/*-------------------------------------------------------------------------------
  SQLインジェクション対策
------------------------------------------------------------------------------*/
function str_escape_sql($str)
{
  // not implement yet
}
