<?php
/******************************************************************************

  データベースに単純な一次配列をストアできる表現を操作します。

  配列を格納するため、スクリプト言語によるシリアライズ表現では、
  SQL文で配列を検索しずらくなるのを防ぎます。
  (例：php/serialize(),perl/Data::Dumper等)

  ※配列の最後の値が空もしくはnullの場合、SQLArray::serializeを使用すると、
    最後の値が切り取られてunserializeすると元の配列が縮小されます。

  All Written By K.Nakagawa.

******************************************************************************/
class SQLArray
{
  private static $delimiter = ';';

  public static function is($ar_str)
    {
      $matches = array();
      $pattern = '/^'.preg_quote(self::$delimiter).'(.+)'.preg_quote(self::$delimiter).'$/';
      return (0 < strlen($ar_str)) && preg_match($pattern,$ar_str,$matches) ? $matches[1] : false;
    }

  public static function serialize(array $ar)
    {
      //入力された配列からデリミタ文字をエスケープする
      $ar = str_replace(self::$delimiter,'&#'.ord(self::$delimiter),$ar);

      return self::$delimiter . implode(self::$delimiter,$ar) . self::$delimiter;
    }

  //[TODO] 戻された配列からデリミタ文字をアンエスケープする
  public static function unserialize($ar_str)
    {
      if(empty($ar_str))
        return array();

      if(($str = self::is($ar_str)) !== false)
        $ar = explode(self::$delimiter,$str);
      else
        $ar = array($ar_str);

      return str_replace('&#'.ord(self::$delimiter),self::$delimiter,$ar);
    }
}

