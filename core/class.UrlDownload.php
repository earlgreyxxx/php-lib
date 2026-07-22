<?php
/*******************************************************************************

  Download file inside of php server.

*******************************************************************************/

class UrlDownload
{
  const BUF_SIZE = 65536;

  //HTTPヘッダを連想配列にして返す
  private static function get_http_header(array $header_string_array) : array
  {
    $rv = [];
    foreach ($header_string_array as $header_string)
    {
      if (preg_match('/^\s*(.+?)\s*:\s*(.+?)\s*$/', $header_string, $matches))
      {
        $name = strtolower($matches[1]);
        $value = $matches[2];

        if (isset($rv[$name]))
        {
          if (is_array($rv[$name]))
            $rv[$name][] = $value;
          else
            $rv[$name] = array($rv[$name], $value);
        }
        else
        {
          $rv[$name] = $value;
        }
      }
    }
    return $rv;
  }


  /*------------------------------------------------------------------------------
    Instances.
  ------------------------------------------------------------------------------*/
  protected array $p;
  protected $files = [
    'name' => [],
    'size' => [],
    'type' => [],
    'tmp_name' => []
  ];

  public function __construct(array $postdata)
  {
    $this->p = $postdata;
  }

  private function _file_clean() : void
  {
    foreach ($this->files['tmp_name'] as $tmp_name)
    {
      if (!empty($tmp_name) && file_exists($tmp_name))
        unlink($tmp_name);
    }

    $this->files = [
      'name' => [],
      'size' => [],
      'type' => [],
      'tmp_name' => []
    ];
  }

  private function _file_download(string $uri) : array|false
  {
    $rv = false;

    $fin = fopen($uri, 'rb');
    if ($fin !== false)
    {
      //レスポンスヘッダーからヘッダー情報を配列に格納する。
      $response = self::get_http_header(
        function_exists('http_get_last_response_headers') ? http_get_last_response_headers() : $http_response_header
      );
      $temp_dir = defined('TEMPORARY_DIR') ? TEMPORARY_DIR : sys_get_temp_dir();
      $temp_path = get_temporary_filepath($temp_dir, rand(1, 99));
      if (false === ($fout = fopen($temp_path, 'wb')))
        throw new Exception(_('can not open file with write mode'));

      $write_byte = 0;
      while (!feof($fin))
      {
        $buf = fread($fin, self::BUF_SIZE);
        $write_byte += fwrite($fout, $buf);
      }

      if ($write_byte > 0)
      {
        //名前決定
        $pi = pathinfo(parse_url($uri, PHP_URL_PATH));
        $content_name = array($pi['filename'], $pi['extension']);
        if (empty($content_name[0]))
          $content_name[0] = 'file ' . str_uniqid();
        if (empty($content_name[1]))
          $content_name[1] = 'dat';

        $rv = array();
        $rv['size'] = $write_byte;
        $rv['name'] = implode('.', $content_name);
        $rv['tmp_name'] = $temp_path;
        if (isset($response['content-type']))
          $rv['type'] = is_array($response['content-type']) ? array_pop($response['content-type']) : $response['content-type'];
        else
          $rv['type'] = 'application/octet-stream';
        $rv['text'] = $uri;
      }
    }

    return $rv;
  }

  //ファイルダウンロードを開始する。
  public function begin(string $name) : bool
  {
    if (empty($this->p[$name]))
      return false;

    if (!is_array($this->p[$name]))
    {
      $urls = array();
      foreach (preg_split('/[\r\n]+/', $this->p[$name]) as $url)
      {
        $url = trim($url);
        if (!empty($url))
          $urls[] = $url;
      }
      $this->p[$name] = $urls;
      unset($urls);
    }

    $rv = true;
    foreach ($this->p[$name] as $uri)
    {
      //'http://'スキームに限定する。
      if (preg_match('/^https?:\/\//i', $uri))
      {
        if (false !== ($file = $this->_file_download($uri)))
        {
          foreach (array('name', 'size', 'type', 'tmp_name', 'text') as $k)
            $this->files[$k][] = $file[$k];
        }
        else
        {
          $rv = false;
          $this->_file_clean();
          break;
        }
      }
    }

    return $rv;
  }

  //$_FILESを返す。
  public function end() : array
  {
    return $this->files;
  }
}
