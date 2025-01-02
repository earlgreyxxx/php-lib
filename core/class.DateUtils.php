<?php
/******************************************************************************

  DateTimeに依存しない日付計算

  All Written By K.Nakagawa.

******************************************************************************/
class DateUtils
{
  /***************************************************************
   y年m月第n曜日の日付を返す
  ***************************************************************/
  public static function Weekday($yy,$mm,$n,$wday)
  {
    $fst_wday = self::Week($yy,$mm,1);         //# 月初めの曜日計算
    $lastday  = self::Lastday($yy,$mm);        //# 指定した年・月の末日計算
    $dd = 0;

    if($wday >= $fst_wday)
      $n--;

    $dd = 7 * $n + $wday + 1 - $fst_wday;

    if(($dd>$lastday)||($dd<=0))
      $dd='';

    return $dd;
  }

  /***************************************************************
   閏年の計算をしてその年の月ごとの日数を返す
  ***************************************************************/
  public static function Monthday($yy)
  {
    $monthday = [0,31,28,31,30,31,30,31,31,30,31,30,31];

    if((($yy % 4 == 0) && ($yy % 100)) || ($yy % 400 == 0))
      $monthday[2] += 1;

    return $monthday;
  }

  /***************************************************************
    指定した年・月の末日計算
  ***************************************************************/
  public static function Lastday($yy,$mm)
  {
    $monthdays = self::Monthday($yy);

    return $monthdays[$mm];
  }

  /***************************************************************
   Zeller(ツェラー)の公式による曜日計算
  ***************************************************************/
  public static function Week($yy,$mm,$dd)
  {
    if($mm == 1 || $mm == 2)
    {
      $yy--;
      $mm+=12;
    }

    return (($yy + intval($yy/4) - intval($yy/100) + intval($yy/400) + intval(2.6*$mm+1.6) + $dd) % 7);
  }

  /***************************************************************
   指定した年の春分日・秋分日をもとめる
  （1980年から2099年に適用）
  ***************************************************************/
  public static function Equinoxday($yy)
  {
    $days = [];

    $days[0] = intval(20.8431+0.242194*($yy-1980) - intval(($yy-1980)/4));
    $days[1] = intval(23.2488+0.242194*($yy-1980) - intval(($yy-1980)/4));

    return $days;
  }

  /***************************************************************
   祝日の算出
  ***************************************************************/
  public static function nHoliday($yy)
  {
    $equinox = self::Equinoxday($yy);     // 春分日・秋分日
    $wday_no = 0;
    $mm = 0;
    $dd = 0;
    $holiday = [];
    for($i=1;$i<=12;$i++)
    {
      $holiday[$i] = [];
    }

    $holiday[1][1] = "元　日";
    $holiday[2][11] = "建国記念日";
    $holiday[3][$equinox[0]] = "春分の日";
    $holiday[4][29] = "みどりの日";
    $holiday[5][3] = "憲法記念日";
    $holiday[5][5] = "こどもの日";
    $holiday[8][11] = "山の日";
    $holiday[9][$equinox[1]] = "秋分の日";
    $holiday[11][3] = "文化の日";
    $holiday[11][23] = "勤労感謝の日";
    $holiday[12][23] = "天皇誕生日";

    if($yy<2000)
    {
      $holiday[1][15] = "成人の日";
      $holiday[10][10] = "体育の日";
    }
    else
    {
      $dd = self::Weekday($yy,1,2,1);    // 指定した曜日の日付を返す
      $holiday[1][$dd] = "成人の日";    // 2000年から１月の第２月曜日

      if($yy == 2020)
      {
        // 2020年はオリンピック等開催の特措法のため体育の日を移動。
        // また「スポーツの日」と改称
        $holiday[7][24] = "スポーツの日";
      }
      else if($yy == 2021)
      {
        // 2021年はオリンピック等開催延期のためスポーツの日を移動。
        $holiday[7][22] = "スポーツの日";
      }
      else
      {
        // 2000年から10月の第２月曜日
        $dd = self::Weekday($yy,10,2,1);   // 指定した曜日の日付を返す
        $holiday[10][$dd] = $yy >= 2020 ? "スポーツの日" : "体育の日";
      }
    }

    if($yy<2003)
    {
      $holiday[7][20] = "海の日";
      $holiday[9][15] = "敬老の日";
    }
    else
    {
      $dd = self::Weekday($yy,7,3,1); // 指定した曜日の日付を返す
      $holiday[7][$dd] = "海の日";      // 7月の第3月曜日
      if($yy == 2020)
      {
        $holiday[7][23] = $holiday[7][$dd];
        unset($holiday[7][$dd]);
      }

      $dd = self::Weekday($yy,9,3,1);    // 指定した曜日の日付を返す
      $holiday[9][$dd] = "敬老の日";    // 9月の第3月曜日
    }

    // 山の日
    if($yy == 2020)
    {
      $holiday[8][10] = $holiday[8][11];
      unset($holiday[8][11]);
    }
    else if($yy == 2021)
    {
      $holiday[8][8] = $holiday[8][11];
      unset($holiday[8][11]);
    }
    else if($yy == 2019)
    {
      $holiday[5][1] = '天皇即位の日';
      $holiday[10][22] = '即位の儀';
    }

    if($yy >= 2019)
    {
      $holiday[2][23] = $holiday[12][23];
      unset($holiday[12][23]); 
    }
    else if($yy < 1989)
    {
      $holiday[4][29] = $holiday[12][23];
      unset($holiday[12][23]);
    }

    if($yy >= 1989 && $yy<2007)
    {
      //# その前日及び翌日が「国民の祝日」である日は、休日とする。
      //#（日曜日にあたる日及び前項振替の休日にあたる日を除く｡) 
      $wday_no = self::Week($yy,5,3);    //# 曜日計算
      if($wday_no!=0)
        $holiday[5][4] = "振替休日";
    }
    else if($yy >= 2007)
    {
      //2005年 法改正により呼称の変更
      $holiday[4][29] = "昭和の日";
      $holiday[5][4] = "みどりの日";
    }

    //「国民の祝日」が日曜日にあたるときは、その翌日を休日とする。 
    $mm = 0;
    foreach($holiday as $mm => $dds)
    {
      if(empty($dds))
        continue;

      $mm = intval($mm);
      $ld = self::Lastday($yy,$mm);
      foreach(array_keys($dds) as $dd)
      {
        $mmx = $mm;
        $dd = intval($dd);
        $wday_no = self::Week($yy,$mmx,$dd); // 曜日計算
        if($wday_no==0)
        {
          do {
            $ddd = $dd;
            $mmm = $mmx;
            if($mmm < 12 && $ddd == $ld)
            {
              $ddd = 1;
              $mmm++;
            }
            else
            {
              $ddd++;
            }

            $dd = $ddd;
            $mmx = $mmm;
          } while(array_key_exists($dd,$holiday[$mmx]));
          $holiday[$mmx][$dd] = "振替休日";
        }
      }
    }

    return $holiday;
  }

  /***************************************************************
   国民の休日を考慮したyy年mm月の祝日を返す。
  ***************************************************************/
  public static function mHoliday($yy,$mm)
  {
    $holiday = self::nHoliday($yy);
    if($yy > 2007)
    {
      $ld = self::Lastday($yy,$mm);
      for($dd = 1;$dd <= $ld;$dd++)
      {
        if(array_key_exists($dd,$holiday[$mm]))
          continue;

        $dp = $dd - 1;
        $dn = $dd + 1;
        $mp = $mm;
        $mn = $mm;
        if($dd == 1)
        {
          $mp = $mm - 1;
          $dp = intval(self::Lastday($yy,$mp));
        }
        else if($dd == $ld)
        {
          $mn = $mm + 1;
          $dn = 1;
        }

        if(array_key_exists($dp,$holiday[$mp]) && $holiday[$mp][$dp] && array_key_exists($dn,$holiday[$mn]) && $holiday[$mn][$dn])
          $holiday[$mm][$dd] = '国民の休日';
      }
    }
    return $holiday[$mm];
  }
}

