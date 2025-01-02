<?php
/******************************************************************************

  分割アップロード処理に使用する関数群をまとめたクラス

******************************************************************************/

class Chunk
{
   //チャンクの寿命秒数
  const LIFE_TIME = 3600;

  //チャンクのファイル名フォーマット
  const FILE_FORMAT = '%s/part-%d.bin';

  //チャンクファイル・クレンジング
  public static function temporary_cleansing($chunk_dir)
    {
      foreach(scandir($chunk_dir) as $filename)
        {
          $file = $chunk_dir . '/' . $filename;
          $test = preg_match('/^\.+$/',$filename) || !is_dir($file) || (time() - filemtime($file) <= self::LIFE_TIME);

          if($test)
            continue;

          rrmdir($file);
        }
    }


  /*------------------------------------------------------------------------------
   Instances
  ------------------------------------------------------------------------------*/
  protected $request = null;
  protected $files = array( 'name' => array(),
                            'size' => array(),
                            'type' => array(),
                            'tmp_name' => array());

  private $marker = null;
  private $tmp_path = null;

  private static function _file_concat($chunk,$is_remove = false)
    {
      $dir = $chunk['dir'];
      $len = $chunk['length'];

      $rv = sprintf('%s/%s.dat',TEMPORARY_DIR,str_uniqid('tmp-'));
      for($i=1;$i<=$len;$i++)
        {
          $file = sprintf(self::FILE_FORMAT,$dir,$i);
          if(!file_exists($file))
            {
              $rv = false;
              break;
            }

          $content = file_get_contents($file);
          file_put_contents($rv,$content,FILE_APPEND);

          if($is_remove === true)
            unlink($file);
        }

      return $rv;
    }

  private function _file_clean()
    {
      $session = &get_session();

      //クリーンアップ
      $chunk = $session['markers'][$this->marker];
      $dir = $chunk['dir'];
      $len = $chunk['length'];
      $id = $chunk['id'];

      for($i=1;$i<=$len;$i++)
        {
          $file = sprintf(self::FILE_FORMAT,$dir,$i);
          if(file_exists($file))
            unlink($file);
        }

      rmdir($dir);

      $session['markers'][$id] = null;
      unset($session['markers'][$id]);
    }


  public function __construct($marker = null)
    {
      $this->marker = empty($marker) ? str_uniqid() : $marker;
      $this->tmp_path = sprintf('%s/%s',
                                defined('TEMPORARY_DIR') ? TEMPORARY_DIR : sys_get_temp_dir(),
                                $this->marker);
    }

  public function begin($post)
    {
      $session = &get_session();
      $marker = $this->marker;
      $dir = $this->tmp_path;
      mkdir($dir);

      if(!isset($session['markers']))
        $session['markers'] = array();

      $type = str_sanitize($post['type']);
      $name = str_sanitize($post['name']);
      $size = intval($post['size']);
      $length = intval($post['length']);
      $id   = $marker;

      if(empty($type) || empty($name) || empty($size) || empty($length) || empty($id))
        return false;

      $session['markers'][$marker] = array('type'   => $type,
                                           'name'   => $name,
                                           'size'   => $size,
                                           'length' => $length,
                                           'dir'    => $dir,
                                           'id'     => $marker);
      return $marker;
    }

  public function put($post,$files)
    {
      $rv = false;
      $session = &get_session();

      // チャンク・データを保存
      $format = empty($post['chunk-format']) ? 'base64' : $post['chunk-format'];
      $marker = $this->marker;
      $order = intval($post['chunk-order']);

      if(isset($session['markers'][$marker]))
        {
          $chunk = $session['markers'][$marker];
          $filename = sprintf(self::FILE_FORMAT,$chunk['dir'],$order);

          switch($format)
            {
            case 'base64':
              if(preg_match('/^data:.*?;base64,/',$post['chunk-data'],$m))
                {
                  $data = base64_decode(substr($post['chunk-data'],strlen($m[0])));
                  file_put_contents($filename,$data);
                  $rv = true;
                }
              break;

            case 'raw':
              $rv = move_uploaded_file($files['chunk-data']['tmp_name'],$filename);
              break;
            }
        }

      return $rv;
    }

  //$_FILESを構築して返す。
  public function end($post)
    {
      $rv = false;
      $session = &get_session();

      $marker = $this->marker;
      $chunk = $session['markers'][$marker];

      if(false !== ($filepath = self::_file_concat($chunk,true)))
        {
          $rv = array('size' => array($chunk['size']),
                      'type' => array($chunk['type']),
                      'name' => array($chunk['name']),
                      'tmp_name' => array($filepath));
        }

      //チャンク除去
      $this->_file_clean();
      return $rv;
    }

  public function abort($post)
    {
      $session = &get_session();

      $marker = $this->marker;
      $dir = $session['markers'][$marker]['dir'];

      $session['markers'][$marker] = null;
      unset($session['markers'][$marker]);

      rrmdir($dir);

      return true;
    }
}
