<?php
/******************************************************************************

 ■ファイル管理

******************************************************************************/
class FileBaseStore extends UniversalStore
{
  const FS_FILE     = 2**1;
  const FS_LINK     = 2**2;
  const FS_PUBLIC   = 2**3;
  const FS_READONLY = 2**4;
  const FS_LOCK     = 2**5;
  const FS_UNDELETE = 2**6;
  const FS_PRIVATE  = 2**7;

  const DEFAULT_NAME = 'file';
  const DEFAULT_ICON = 'file.png';
  const IMG_DEFAULT_RESAMPLE_SIZE = 250;
  const IMG_MAX_VIDEO_SIZE = 600;
  const PREG_PATTERN_FS = '/[,\\&\?%\+\^#\<\>\:;\|\*\(\)\[\]\{\}\$\=@`\"\\\'\/\s]/u';
  const PREG_PATTERN_DATETIME = '/^\d{4}-[01]\d-[0-3]\d\s[0-2]\d:[0-5]\d:[0-5]\d$/u';

  /*------------------------------------------------------------------------------
   statics
  ------------------------------------------------------------------------------*/

  //テーブルを用意する。
  public static function Prepare(PDOExtension $pdoex, string $table) : void
  {
    $columns = [
      'file_id INTEGER PRIMARY KEY %AUTOINCREMENT%',
      'file_name VARCHAR(256) UNIQUE',
      'file_path VARCHAR(256)',
      'file_slug VARCHAR(256) UNIQUE',
      'file_digest VARCHAR(40)',
      'file_attr INTEGER',
      'file_size INTEGER',
      'file_type VARCHAR(128)',
      'file_owner INTEGER',
      'file_registdate DATETIME',
      'file_lastupdate DATETIME',
      'file_extra TEXT'
    ];

    static::CreateTable($pdoex, $table, $columns, array('file_digest ASC'));
  }

  protected static $Placeholders = [
    'file_id'         => ['?', PDO::PARAM_INT],
    'file_name'       => ['?', PDO::PARAM_STR],
    'file_path'       => ['?', PDO::PARAM_STR],
    'file_slug'       => ['?', PDO::PARAM_STR],
    'file_digest'     => ['?', PDO::PARAM_STR],
    'file_attr'       => ['?', PDO::PARAM_INT],
    'file_size'       => ['?', PDO::PARAM_INT],
    'file_type'       => ['?', PDO::PARAM_STR],
    'file_owner'      => ['?', PDO::PARAM_INT],
    'file_registdate' => ['?', PDO::PARAM_STR],
    'file_lastupdate' => ['?', PDO::PARAM_STR],
    'file_extra'      => ['?', PDO::PARAM_STR]
  ];

  protected static function clean_filename(string $str) : void
  {
    throw new RuntimeException(_('method "clean_filename" not implement'));
  }

  protected static function GetFileDigest(string $filepath) : string|false
  {
    return is_readable($filepath) ? sha1_file($filepath) : false;
  }

  protected static function GetSuffix(string $type, string $suffix = '.') : string
  {
    switch (strtolower($type))
    {
      case 'image/jpg':
      case 'image/jpeg':
        $rv = 'jpg';
        break;
      case 'image/x-png':
      case 'image/png':
        $rv = 'png';
        break;
      case 'image/x-bmp':
      case 'image/x-ms-bmp':
        $rv = 'bmp';
        break;
      case 'application/zip':
      case 'application/x-zip-compressed':
        $rv = 'zip';
        break;
      case 'application/postscript':
        $rv = 'ps';
        break;
      case 'application/pdf':
        $rv = 'pdf';
        break;
      case 'video/x-msvideo':
        $rv = 'avi';
        break;
      case 'video/mpeg':
      case 'video/x-mpeg':
        $rv = 'mpg';
        break;
      case 'video/mp4':
        $rv = 'mp4';
        break;
      case 'application/msword':
        $rv = 'doc';
        break;
      case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
        $rv = 'docx';
        break;
      case 'application/vnd.ms-word.document.macroEnabled.12':
        $rv = 'docm';
        break;
      case 'application/vnd.ms-excel':
        $rv = 'xls';
        break;
      case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
        $rv = 'xlsx';
        break;
      case 'application/vnd.ms-excel.sheet.macroEnabled.12':
        $rv = 'xlsm';
        break;
      case 'application/mspowerpoint':
        $rv = 'ppt';
        break;
      case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
        $rv = 'pptx';
        break;
      case 'application/vnd.ms-powerpoint.presentation.macroEnabled.12':
        $rv = 'pptm';
        break;
      case 'text/csv':
      case 'text/comma-separated-values':
      case 'application/csv':
        $rv = 'csv';
        break;
      case 'text/plain':
        $rv = 'txt';
        break;
      case 'text/html':
        $rv = 'html';
        break;
      default:
        $rv = 'dat';
    }

    return $suffix . $rv;
  }

  protected static function HasCommand(string $commandname) : bool
  {
    system("which $commandname > /dev/null 2>&1", $status);
    return !$status;
  }

  /*------------------------------------------------------------------------------
   Instances
  ------------------------------------------------------------------------------*/
  protected string $rootpath;
  protected string $thumbnailpath;
  protected int $thumbnailSize;
  protected bool $has_ffmpeg;
  protected bool $has_convert;

  //constructor
  public function __construct(string $dsn,string $table_name, string $user = '', string $passwd = '', array $options = [])
  {
    //基本クラスのコンストラクタをコール
    parent::__construct($dsn, $table_name, $user, $passwd, $options);

    //メタキーの定義
    $meta_keys = ['alias', 'text', 'displayname', 'thumbnail', 'width', 'height'];

    if (count($meta_keys) > 0)
      $this->meta_keys = array_merge($this->meta_keys, $meta_keys);

    $this->setroot(isset($options['setroot']) ? $options['setroot'] : '');
    $this->setThumbnailRoot(isset($options['thumbnailRoot']) ? $options['thumbnailRoot'] : $this->rootpath);

    $this->has_ffmpeg = self::HasCommand('ffmpeg');
    $this->has_convert = self::HasCommand('convert');
  }

  public function setroot(string $path) : void
  {
    $rv = false;

    $this->rootpath = $path;
    if (!file_exists($path) || !is_dir($path))
      mkdir($path);
  }

  public function getroot() : string
  {
    return $this->rootpath;
  }

  public function setThumbnailRoot(string $path) : void
  {
    $rv = false;

    $this->thumbnailpath = $path;
    if (!file_exists($path) || !is_dir($path))
      mkdir($path);
  }

  protected function setThumbnailSize(int $width)
  {
    $this->thumbnailSize = $width;
    return $this;
  }
  protected function getThumbnailSize() : int
  {
    return empty($this->thumbnailSize) ? static::IMG_DEFAULT_RESAMPLE_SIZE : $this->thumbnailSize;
  }

  protected function form_to_post(?array $form = null) : array
  {
    if ($form == null)
      $form = &get_post();

    $post = [];

    $ctime = date('Y-m-d H:i:s');
    $attrs = self::FS_FILE;
    if (intval($form['public']) > 0)
      $attrs |= self::FS_PUBLIC;
    if (intval($form['rom']) > 0)
      $attrs |= self::FS_READONLY;
    if (intval($form['lock']) > 0)
      $attrs |= self::FS_LOCK;
    if (intval($form['undelete']) > 0)
      $attrs |= self::FS_UNDELETE;

    $post['file_name']       = trim(str_sanitize($form['name']));
    $post['file_name']       = preg_replace(self::PREG_PATTERN_FS, '', $post['file_name']);
    if (strlen($post['file_name']) <= 0)
      $post['file_name'] = sprintf('%s-%06d.dat', self::DEFAULT_NAME, mt_rand(1, 999999));

    $post['file_slug']       = urlencode(str_sanitize($form['slug']));
    $post['file_digest']     = self::GetFileDigest($form['tmp_path']);
    $post['file_attr']       = $attrs;
    $post['file_size']       = intval($form['size']);
    $post['file_type']       = str_sanitize($form['type']);
    if (empty($post['file_type']))
      $post['file_type'] = 'application/octet-stream';
    $post['file_registdate'] = preg_match(self::PREG_PATTERN_DATETIME, $form['regist']) ? $form['regist'] : $ctime;
    $post['file_lastupdate'] = $ctime;
    $post['file_extra']      = str_sanitize($form['extra']);

    //ここからはメタデータ
    foreach ($this->meta_keys as $key)
    {
      if (isset($form[$key]))
      {
        if (is_array($form[$key]))
        {
          foreach ($form[$key] as &$val)
            $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');

          $post[$key] = $form[$key];
        }
        else
        {
          $post[$key] = htmlspecialchars($form[$key], ENT_QUOTES, 'UTF-8');
        }
      }

      if (method_exists($this, 'meta_form_to_post'))
        $this->meta_form_to_post($key, $form, $post);
    }

    return $post;
  }

  protected function post_to_form(array $post, string $prefix = 'file_') : array
  {
    $form = [];
    $form['name'] = $post[$prefix . 'name'];
    $form['size'] = $post[$prefix . 'size'];
    $form['mime'] = $post[$prefix . 'type'];
    $form['date'] = $post[$prefix . 'date'];
    $form['time'] = strtotime($form['date']);
    $form['etc']  = $post[$prefix . 'extra'];
    $attrs = $post[$prefix . 'attr'];

    $form['private']  = ($attrs & self::FS_PRIVATE) == self::FS_PRIVATE;
    $form['readonly'] = ($attrs & self::FS_READONLY) == self::FS_READONLY;
    $form['lock']     = ($attrs & self::FS_LOCK) == self::FS_LOCK;
    $form['undelete'] = ($attrs & self::FS_UNDELETE) == self::FS_UNDELETE;

    //ここからはメタデータ
    foreach ($this->meta_keys as $key)
    {
      $form[$key] = $post[$key];
      if (method_exists($this, 'meta_post_to_form'))
        $this->meta_post_to_form($key, $post, $form);
    }

    return $form;
  }

  /*------------------------------------------------------------------------------
    Override methods
  ------------------------------------------------------------------------------*/
  //id, src gd object, filename
  private function add_meta_image(int|string $fid, GDResampleImage $gd, string $filename) : void
  {
    $thumbnailSize = $this->getThumbnailSize();
    $savedir = create_path($filename, $this->thumbnailpath);
    $basename = $gd->resample($thumbnailSize, $savedir, 'png');
    $thumbnail = sprintf('/%s/%s', create_path_prefix($basename), $basename);

    $kv = array('thumbnail' => $thumbnail, 'width' => $gd->width(), 'height' => $gd->height());
    $this->meta->sets($fid, $kv);
  }

  //id, src, filename
  private function add_meta_pdf(int|string $fid, string $filepath, string $filename) : void
  {
    $path = get_platform_filename($filepath);
    $savedir = create_path($filename, $this->thumbnailpath);
    $basename = pathinfo($filename, PATHINFO_FILENAME) . '.png';
    $savepath = "$savedir/$basename";
    $thumbnail = sprintf('/%s/%s', create_path_prefix($basename), $basename);

    $fmt = 'convert "%s[0]" %s > /dev/null 2>&1';
    $commandline = sprintf($fmt, $path, $savepath);
    system($commandline, $status);
    if (!$status)
    {
      $kv = array('thumbnail' => $thumbnail);
      list($width, $height) = getimagesize($savepath);
      if ($width && $height)
        $kv = array_merge($kv, array('width' => $width, 'height' => $height));

      $this->meta->sets($fid, $kv);
    }
  }

  private function add_meta_video(int|string $fid, string $filepath, string $filename) : void
  {
    $path = get_platform_filename($filepath);
    $framesec = 5;
    $savedir = create_path($filename, $this->thumbnailpath);
    $basename = pathinfo($filename, PATHINFO_FILENAME) . '.png';
    $savepath = "$savedir/$basename";
    $thumbnail = sprintf('/%s/%s', create_path_prefix($basename), $basename);

    $commandline = "ffprobe -v quiet -print_format json -show_format -show_streams '$path'";
    exec($commandline, $output, $status);

    $vf = '-1:%d';
    if (!$status && $output)
    {
      $json = json_decode(implode('', $output), true);
      $stream = $json['streams'][0];
      $width = $stream['width'];
      $height = $stream['height'];
      $rotation = isset($stream['tags']['rotate']) ? intval($stream['tags']['rotate']) : 0;
      if ($rotation > 0 && (($rotation / 90) % 2 == 1))
      {
        $width = $stream['height'];
        $height = $stream['width'];
      }
      if ($width / $height >= 1)
        $vf = '%d:-1';
    }
    $size = max($width, $height);
    if ($size > self::IMG_MAX_VIDEO_SIZE)
      $size = self::IMG_MAX_VIDEO_SIZE;

    $fmt = "ffmpeg -y -v quiet -ss %d -i \"%s\" -vframes 1 -vf scale=$vf -vcodec png \"%s\" > /dev/null 2>&1";
    $commandline = sprintf($fmt, $framesec, $path, $size, $savepath);
    system($commandline, $status);
    if (!$status)
    {
      $kv = array('thumbnail' => $thumbnail);
      if (!isset($width) || !isset($height))
        list($width, $height) = getimagesize($savepath);

      if ($width > 0 && $height > 0)
        $kv = array_merge($kv, array('width' => $width, 'height' => $height));

      $this->meta->sets($fid, $kv);
    }
  }

  // $options => $_FILES
  protected function add(array $post, array $options = []) : string|false
  {
    $files = $options['files'];
    $is_upload_file = array_key_exists('is_upload_file', $options) ? $options['is_upload_file'] : true;

    if (empty($files) || !is_array($files))
      return false;

    $num = 0;
    $rv = [];
    $keys = ['name', 'size', 'type', 'tmp_name', 'text', 'attr'];

    if (!is_array($files['size']))
    {
      foreach ($keys as $k)
        $files[$k] = array($files[$k]);
    }

    foreach (array_keys($files['size']) as $i)
    {
      if (empty($files['size'][$i]))
        continue;

      $file_ = array();
      foreach ($keys as $k)
        $file_[$k] = isset($files[$k][$i]) ? $files[$k][$i] : '';

      if (empty($file_['tmp_name']) || !file_exists($file_['tmp_name']))
        continue;

      // if no file extension found, suffix is deciced by mime-type
      if (!preg_match('/\.[^\.]+$/', $file_['name']))
      {
        $file_['name'] = rtrim($file_['name'], '.');
        $file_['name'] .= self::GetSuffix($file_['type']);
      }

      $savebasename = create_basename($file_, $this->rootpath);
      $savepathbasename = sprintf('/%s/%s', create_path_prefix($savebasename), $savebasename);
      $savedir = create_path($savebasename, $this->rootpath);
      $savepath = sprintf('%s/%s', $savedir, $savebasename);

      if (is_uploaded_file($file_['tmp_name']) || $is_upload_file === true)
        move_uploaded_file($file_['tmp_name'], get_platform_filename($savepath));
      else
        rename($file_['tmp_name'], get_platform_filename($savepath));

      chmod($savepath, 0666);

      $current_time = date('Y-m-d H:i:s');

      //画像チェック
      $gd = null;
      $is_video = false;
      $is_pdf = false;
      if (preg_match('/^image\//', $file_['type'], $matches))
      {
        $gd = new GDResampleImage($savepath);
        if ($gd->get_image() === false)
        {
          $file_['type'] = 'application/' . substr($file_['type'], strlen($matches[0]));
          $gd = null;
        }
      }
      else if (preg_match('/^video\//', $file_['type']))
      {
        $is_video = true;
      }
      else if ('application/pdf' === $file_['type'])
      {
        $is_pdf = true;
      }

      $attr = self::FS_FILE;
      if (is_numeric($file_['attr']))
        $attr = $file_['attr'];

      $post = [
        'file_name'       => $savebasename,
        'file_path'       => $savepathbasename,
        'file_slug'       => rawurlencode($savebasename),
        'file_digest'     => sha1_file($savepath),
        'file_attr'       => intval($attr),
        'file_size'       => intval($file_['size']),
        'file_type'       => $file_['type'],
        'file_registdate' => $current_time,
        'file_lastupdate' => null,
        'file_extra'      => null,
        'displayname'     => $savebasename
      ];

      if (!empty($file_['text']))
        $post['text'] = $file_['text'];

      try {
        if (false != ($file_id = parent::add($post)))
        {
          $num++;
          $rv[] = $file_id;
          if ($gd)
          {
            $this->add_meta_image($file_id, $gd, $savebasename);
            $gd = null;
          }
          else if ($is_video && $this->has_ffmpeg)
          {
            $this->add_meta_video($file_id, $savepath, $savebasename);
          }
          else if ($is_pdf &&  $this->has_convert)
          {
            $this->add_meta_pdf($file_id, $savepath, $savebasename);
          }
        }
        else
        {
          //DBへの追加で失敗したときの処理
          throw new Exception(_('Failed to adding to store object'));
        }
      } catch (Exception $e) {
        unlink($savepath);
        if (isset($kv) && isset($kv['thumbnail']))
          unlink(sprintf('%s/%s', $savedir, $kv['thumbnail']));

        throw $e;
      }
    }

    return empty($rv) ? false : count($rv);
  }

  //削除
  protected function remove(int|string $id, array $options = []) : bool
  {
    $rv = false;
    if (0 >= ($id = intval($id)))
      return $rv;

    if (false !== ($row = $this->get_values($id, false)))
    {
      if (false != ($rv = parent::remove($id, $options)))
      {
        //ここに削除処理
        $filepath = $this->rootpath . $row['file_path'];
        if (file_exists($filepath))
          unlink($filepath);

        if (!empty($row['thumbnail']))
        {
          $thumbnailpath = $this->thumbnailpath . $row['thumbnail'];
          if (file_exists($thumbnailpath))
            unlink($thumbnailpath);
        }
      }
    }

    return $rv ? true : false;
  }

  //postメソッドは禁止、替りにtoメソッドを使用する。
  public function post(?array $form = null, array $options = []) : int|false
  {
    return false;
  }

  public function modify(int|string $id, ?array $form = null, array $options = []) : true|array
  {
    throw new RuntimeException(_('method "modify" not implement'));
    return false;
  }

  public function delete(int|string $id, array $options = []) : bool
  {
    $rv = $this->remove($id, $options);
    return $rv > 0;
  }

  /*------------------------------------------------------------------------------
    instance methods
  ------------------------------------------------------------------------------*/

  //adding files wrapper for add method.
  public function to(array $files, bool $is_upload_file = true) : string|false
  {
    return $this->add(
      [],
      [ 'files' => $files, 'is_upload_file' => $is_upload_file ]
    );
  }

  public function attr(int|string $id, int|string $attrs = 0) : bool
  {
    $rv = false;
    if (!is_int($id))
      $id = intval($id);
    if (!is_int($attrs))
      $attrs = intval($attrs);

    if ($id <= 0 || $attrs <= 0)
      return $rv;

    $columnvalue = [
      'file_attr'       => $attrs,
      'file_lastupdate' => date('Y-m-d H:i:s')
    ];

    //direct access to Store object
    return $this->_update('file_id', $id, $columnvalue);
  }

  public function slug(int|string $id, string $slug) : bool
  {
    if (empty($slug))
      return false;

    return $this->_update('file_id', $id, ['file_slug' => urlencode($slug)]);
  }

  public function text(int|string $id, string $text) : bool
  {
    return $this->get_meta()->set(intval($id), 'text', $text);
  }

  /*------------------------------------------------------------------------------
   find by column value 'file_id' || 'file_slug' || 'file_name' || 'file_path'
  ------------------------------------------------------------------------------*/
  public function find(int|string $query) : array|false
  {
    $row = false;
    if (is_int($query))
    {
      $row = $this->get_values($query, false);
    }
    else
    {
      $cond = sprintf('WHERE file_name = %1$s OR file_slug = %1$s', $query);
      $rows = $this->get($cond);
      if (!empty($rows))
        $row = array_shift($rows);
    }
    return $row;
  }

  /*------------------------------------------------------------------------------
   return record
  ------------------------------------------------------------------------------*/
  public function getRow(string $query, bool $is_encrypted = false) : array|false
  {
    if ($is_encrypted)
    {
      $query = str_decrypt_ts($query);

      if (is_numeric($query))
        $query = intval($query);
    }

    return $this->find($query);
  }

  /*------------------------------------------------------------------------------
   output upload file by record with digest value
  ------------------------------------------------------------------------------*/
  public function getRowWithDigest(string $digest) : array|false
  {
    $rv = false;
    $row = false;
    $query = str_decrypt_ts($digest);

    $this->set_filter('and', sprintf('file_digest = %s', $this->quote($query)));
    if (false !== ($rows = $this->gets(1)) && count($rows) == 1)
      $rv = $rows[0];

    return $rv;
  }
}