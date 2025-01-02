<?php
/********************************************************************************
  Debug utilities
*******************************************************************************/
class DebugUtils
{
  private static function GetPeekMemoryText(array $options = [])
  {
    $before = $options['before'] ?? '';
    $after = $options['after'] ?? '';

    $peekmem = $before . number_format(memory_get_peak_usage(true)) . $after;

    return php_sapi_name() === 'cli' ?
      sprintf(
        "%s\t%s",
        date('Y-m-d H:i:s'),
        $peekmem
      ) : 
      sprintf(
        "%s\t%s\t%s\t%s",
        date('Y-m-d H:i:s'),
        $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'],
        parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH),
        $peekmem
      ) ; 
  }

  public static function PeekMemoryToFile(string $filepath = '-',array $options = [])
  {
    $rv = true;
    if(empty($filepath))
      throw new RuntimeException(_('require output logfile...'));

    if($filepath === '-')
      echo self::GetPeekMemoryText($options),PHP_EOL;
    else if(is_writable($filepath) || is_writable(dirname($filepath)))
      $rv = file_put_contents(
        $filepath,
        self::GetPeekMemoryText().PHP_EOL,
        LOCK_EX | FILE_APPEND
      );
    else
      $rv = false;

    return $rv;
  }
}
