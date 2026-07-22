<?php /************************************************************************

  分割アップロード処理に使用する関数群をまとめたクラス

******************************************************************************/

class Chunk
{
   //チャンクの寿命秒数
  const int LIFE_TIME = 3600;

  //チャンクのファイル名フォーマット
  const string FILE_FORMAT = '%s/part-%d.bin';

  //チャンクファイル・クレンジング
  public static function temporary_cleansing(string $chunk_dir) : bool
  {
    $rv = true;
    try {
    if(!is_dir($chunk_dir))
      throw new RuntimeException(_('1argument must be existing directory'));

      foreach (scandir($chunk_dir) as $filename)
      {
        $file = $chunk_dir . '/' . $filename;
        $test = preg_match('/^\.+$/', $filename) || is_file($file) || (time() - filemtime($file) <= self::LIFE_TIME);

        if ($test)
          continue;

        rrmdir($file);
      }
    } catch (Exception $e) {
      $rv = false;
    }

    return $rv;
  }


  /*------------------------------------------------------------------------------
   Instances
  ------------------------------------------------------------------------------*/
  protected ?array $request = null;
  protected array $files = [ 'name' => [], 'size' => [], 'type' => [], 'tmp_name' => [] ];

  private ?string $marker = null;
  private ?string $tmp_path = null;

  private static function _file_concat(array $chunk,bool $is_remove = false) : string|false
  {
    $dir = $chunk['dir'];
    $len = $chunk['length'];

    $rv = $tmpfile = sprintf('%s/%s.dat', TEMPORARY_DIR, str_uniqid('tmp-'));
    $fin = null;
    $fout = null;

    try {
      if (false === ($fout = fopen($tmpfile, 'wb')))
        throw new RuntimeException('failed to output file');

      for ($i = 1; $i <= $len; $i++)
      {
        $file = sprintf(self::FILE_FORMAT, $dir, $i);
        try {
          if (!is_readable($file))
            throw new RuntimeException('failed to read chunk file');

          if(false === ($fin = fopen($file, 'rb')))
            throw new RuntimeException('failed to open file:' . $file);

          if (false === stream_copy_to_stream($fin, $fout))
            throw new RuntimeException('failed to copy stream');
        } catch(Exception $exLoop) {
          $rv = false;
          throw $exLoop;
        } finally {
          if ($fin)
          {
            fclose($fin);
            $fin = null;
          }
        }
        if ($is_remove === true)
          unlink($file);
      }
      $rv = $tmpfile;
    } catch(Exception $e) {
      unlink($tmpfile);
    } finally {
      if($fout)
        fclose($fout);
    }

    return $rv;
  }

  private function _file_clean() : bool
  {
    $rv = true;
    $session = &get_session();

    //クリーンアップ
    $chunk = $session['markers'][$this->marker];
    $dir = $chunk['dir'];
    $len = $chunk['length'];
    $id = $chunk['id'];

    try {
      for ($i = 1; $i <= $len; $i++)
      {
        $file = sprintf(self::FILE_FORMAT, $dir, $i);
        if (is_writable($file))
          unlink($file);
      }

      if(!rmdir($dir))
        throw new RuntimeException(_('failed to delete chunk directory'));

      $session['markers'][$id] = null;
      unset($session['markers'][$id]);
    } catch (Exception $e) {
      $rv = false;
    }

    return $rv;
  }

  public function __construct(?string $marker = null)
  {
    $this->marker = empty($marker) ? str_uniqid() : $marker;
    $this->tmp_path = sprintf(
      '%s/%s',
      defined('TEMPORARY_DIR') ? TEMPORARY_DIR : sys_get_temp_dir(),
      $this->marker
    );
  }

  public function begin(array $post) : string|false
  {
    $session = &get_session();
    $marker = $this->marker;
    $dir = $this->tmp_path;
    mkdir($dir);

    if (!isset($session['markers']))
      $session['markers'] = array();

    $type = str_sanitize($post['type']);
    $name = str_sanitize($post['name']);
    $size = intval($post['size']);
    $length = intval($post['length']);
    $id   = $marker;

    if (empty($type) || empty($name) || empty($size) || empty($length) || empty($id))
      return false;

    $session['markers'][$marker] = array(
      'type'   => $type,
      'name'   => $name,
      'size'   => $size,
      'length' => $length,
      'dir'    => $dir,
      'id'     => $marker
    );
    return $marker;
  }

  public function put(array $post,array $files) : bool
  {
    $rv = false;
    $session = &get_session();

    // チャンク・データを保存
    $format = empty($post['chunk-format']) ? 'base64' : $post['chunk-format'];
    $marker = $this->marker;
    $order = intval($post['chunk-order']);

    if (isset($session['markers'][$marker]))
    {
      $chunk = $session['markers'][$marker];
      $filename = sprintf(self::FILE_FORMAT, $chunk['dir'], $order);

      switch ($format)
      {
        case 'base64':
          if (preg_match('/^data:.*?;base64,/', $post['chunk-data'], $m))
          {
            $data = base64_decode(substr($post['chunk-data'], strlen($m[0])));
            file_put_contents($filename, $data);
            $rv = true;
          }
          break;

        case 'raw':
          $rv = move_uploaded_file($files['chunk-data']['tmp_name'], $filename);
          break;
      }
    }

    return $rv;
  }

  //$_FILESを構築して返す。
  public function end(array $post) : array|false
  {
    $rv = false;
    $session = &get_session();

    $marker = $this->marker;
    $chunk = $session['markers'][$marker];

    if (false !== ($filepath = self::_file_concat($chunk, true)))
    {
      $rv = [
        'size' => [$chunk['size']],
        'type' => [$chunk['type']],
        'name' => [$chunk['name']],
        'tmp_name' => [$filepath]
      ];
    }

    //チャンク除去
    $this->_file_clean();
    return $rv;
  }

  public function abort($post) : bool
  {
    $rv = true;
    try {
      $session = &get_session();

      $marker = $this->marker;
      $dir = $session['markers'][$marker]['dir'];

      $session['markers'][$marker] = null;
      unset($session['markers'][$marker]);

      if(!rrmdir($dir))
        throw new RuntimeException(_('failed to delete directory'));
    } catch (Exception $e) {
      $rv = false;
    }

    return $rv;;
  }
}
