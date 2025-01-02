<?php
/*******************************************************************************

  日付に関する関数定義

  All Written by K.,Nakagawa.

*******************************************************************************/
/*------------------------------------------------------------------------------

  日付に関する関数・定義

------------------------------------------------------------------------------*/

//DATE型の未定義値の定義
define('DATE_NULL','0000-01-01');
define('DATE_BOUND','9999-12-31');

//日付型の未定義値を空文字に変換する。
function date_to_empty(&$date)
{
  $rv = false;

  if($date === DATE_NULL || $date === DATE_BOUND)
    {
      $date = '';
      $rv = true;
    }

  return $rv;
}

//現在日時を表す文字列を返す、date関数のショートカット
function Now($format = 'Y-m-d H:i:s')
{
  return date($format);
}

// 年度の範囲rangeを取得
// --------------------------------------------------
function get_business_year_range($byear = 0,$fmt = 'Y-m-d')
{
  return array(
    's' => business_year_start_date($byear,$fmt),
    'e' => business_year_end_date($byear,$fmt)
  );
}

// 指定年度の開始日付文字列を返す
// --------------------------------------------------
function business_year_start_date($byear = 0,$fmt = 'Y-m-d')
{
  if($byear == 0)
    $byear = get_business_year();

  $time = mktime(0,0,0,business_year_start_month(),1,$byear);
  return is_null($fmt) ? $time : date($fmt,$time);
}

// 指定年度の終了日付文字列を返す
// --------------------------------------------------
function business_year_end_date($byear = 0,$fmt = 'Y-m-d')
{
  if($byear == 0)
    $byear = get_business_year();

  $time = strtotime('+1Year-1Day',mktime(0,0,0,business_year_start_month(),1,$byear));
  return is_null($fmt) ? $time : date($fmt,$time);
}

// 年度始まりの月の取得／設定
// --------------------------------------------------
function business_year_start_month($set_month = null)
{
  static $start_month = 4;
  if(!is_null($set_month) && is_int($set_month) && $set_month >= 1 && $set_month <= 12)
    $start_month = $set_month;

  return $start_month;
}

function business_year_end_month()
{
  $start = business_year_start_month();
  $end   = $start - 1;
  return $end == 0 ? 12 : $end;
}

// 指定されたUNIX時間における年度を返す
// --------------------------------------------------
function get_business_year($time = null,$start_month = 4)
{
  if(empty($time))
    $time = time();

  $time = intval($time);
  if($time <= 0)
    return false;

  $y = date('Y',$time);
  $m = date('m',$time);

  if($m < $start_month)
    $y--;
  
  return intval($y);
}

// 年度月日を現実月日に変換する
// --------------------------------------------------
function business_date_to_real_date($by,$bm,$bd = 1)
{
  $y = $by;
  $start_month = business_year_start_month();
  if($start_month > $bm)
    $y++;

  return [$y,$bm];
}

// 週の一覧を取得
// --------------------------------------------------
function getWeeks($start = 0)
{
  static $week_jp = ['日','月','火','水','木','金','土'];

  if($start < 0)
    throw new RuntimeException(_('can not accept negative value'));

  $weeks = $week_jp;
  $len = count($weeks);

  if($start > 0)
    $start %= $len;

  $cut = array_splice($weeks,$start);
  return array_merge($cut,$weeks);
}

// 指定した年・月のカレンダー構造を作成する
// ---------------------------------------------------
function calendar($y,$m,$option = 0)
{
  if($y < 1000 || $y > 2100)
    throw new RuntimeException(_('$year was out of range'));

  if($m < 1 || $m > 12)
    throw new RuntimeException(_('$month was out of range'));

  $basetime = mktime(0,0,0,$m,1,$y); 
  $start_week = intval(date('w',$basetime));
  $lastday = date('t',$basetime);

  $begin = 0;
  $callback = function($y,$m,$d) { return sprintf('%02d',$d); };
  if(is_array($begin))
  {
    $begin = $option['begin'];
    $callback = $option['callback'];
  }
  else if(is_callable($option))
  {
    $callback = $option;
  }
  else
  {
    $begin = $option;
  }

  if($begin > 0)
    $begin %= 7;

  $calendar = [];
  for($r = 0,$d = 1;$d <= $lastday;$r++)
  {
    $row = [];
    for($c = 0;$c < 7;$c++)
    {
      if($r > 0)
        $row[$c] = $d <= $lastday ? call_user_func($callback,$y,$m,$d++) : null;  // その他の行
      else
        $row[$c] = $c + $begin >= $start_week ? call_user_func($callback,$y,$m,$d++) : null;  // 月初めの行
    }
    $calendar[$r] = $row;
  }
  return $calendar;
}

/*---------------------------------------------------------------------
  西暦日付から和暦情報(array(年号,年,月,日))を返す。
----------------------------------------------------------------------*/
function get_wareki_year($y,$m,$d)
{
  $rv = get_wareki($y,$m,$d,array('showa'=>'昭和','heisei'=>'平成','taisho'=>'大正','meiji'=>'明治'));

  return implode('',array_splice($rv,0,2)).'年';
}

function get_wareki($y = 1970,
                    $m = 1 ,
                    $d = 1,
                    $nengo = array())
{
  static $wareki = array('meiji' => 'meiji',
                         'taisho' => 'taisho',
                         'showa' => 'showa',
                         'heisei' => 'heisei',
                         'seireki' => 'seireki');

  if(is_array($nengo) && !empty($nengo))
    $nengo = array_merge($wareki,$nengo);

  $date = sprintf('%04d%02d%02d',$y,$m,$d);
  $rv = array('seireki',$y,$m,$d);
  
  if($date >= 19890108)
    {
      $rv = array($nengo['heisei'],$y - 1988,$m,$d);
    }
  else if($date >= 19261225)
    {
      $rv = array($nengo['showa'],$y - 1925,$m,$d);
    }
  else if($date >= 19120730)
    {
      $rv = array($nengo['taisho'],$y - 1911,$m,$d);
    }
  else if($date >= 18680125)
    {
      $rv = array($nengo['meiji'],$y - 1867,$m,$d);
    }

  return $rv;
}

function get_wareki_range($unit,$min = 0)
{
  static $wareki = array('heisei' => 0,
                         'showa' => 64,
                         'taisho' => 15,
                         'meiji' => 45);

  if(empty($unit))
    $unit = 'seireki';

  if(!$wareki['heisei'])
    $wareki['heisei'] = intval(date('Y')) - 1988;

  $thisYear = intval(date('Y'));
  $from = 1;
  if(array_key_exists($unit,$wareki))
    {
      $to = $wareki[$unit];
    }
  else
    {
      $from = $thisYear - 80;
      $to = $thisYear;
      if($min > 1900 && $from > $min)
        $from = $min;
    }

  return array($from,$to);
}

/*---------------------------------------------------------------------
  満年齢を返す
----------------------------------------------------------------------*/
function get_full_age($birth)
{
  return intval((intval(date('Ymd')) - intval(date('Ymd',$birth)))/10000);
}

function full_age($birth,$unit = '')
{
  echo get_full_age($birth),$unit;
}
