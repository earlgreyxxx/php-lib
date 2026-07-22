<?php
/*------------------------------------------------------------------------------
  バージョンの定義
------------------------------------------------------------------------------*/
define('VERSION','unkown');

/*------------------------------------------------------------------------------
  バージョンを出力
------------------------------------------------------------------------------*/
function version(string $before = '?v=',string $after = '') : void
{
  echo $before,get_version(),$after;
}

function get_version() : string
{
  static $version = null;

  if($version === null)
  {
    $version = VERSION;

    $filepath = LIB_DIR . '/.ver';
    if(file_exists($filepath) && ($content = file_get_contents($filepath)))
    {
      $content = preg_split('/[\r\n]+/',$content);
      $lines = [];
      foreach($content as $line)
      {
        if(empty($line) || $line[0] === '#')
          continue;

        $lines[] = $line;
      }

      if(!empty($lines))
        $version = $lines[0];
    }
  }

  return $version;
}
