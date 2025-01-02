<?php
/*******************************************************************************

  ファイル管理に特化

    - ファイル管理およびアップロード管理

  メソッド名 = [mode]_[action]

*******************************************************************************/

define('RESPONSE_TYPE_JSON','application/json; charset=utf-8');
define('RESPONSE_TYPE_HTML','text/html; charset=utf-8');

abstract class FileManagerBase extends WebApplicationStub
{
  /*------------------------------------------------------------------------------
    statics
  ------------------------------------------------------------------------------*/
  protected static $app = null;
  protected static $post_max_size = null;
  protected static $upload_max_filesize = null;
  protected static $max_file_uploads = null;


  /*------------------------------------------------------------------------------
    instances
  ------------------------------------------------------------------------------*/
  protected $result = null;
  protected $response = null;

  //require implement
  protected function init()
    {
      parent::init();

      global $SESSION_PARAMS;
      Session::GetInstance(SESSION_APPNAME,$SESSION_PARAMS);

      if(static::$post_max_size === null)
        {
          static::$post_max_size       = extract_unit_size(ini_get('post_max_size'));
          static::$upload_max_filesize = extract_unit_size(ini_get('upload_max_filesize'));
          static::$max_file_uploads    = extract_unit_size(ini_get('max_file_uploads'));
        }

      // check uploaded byte size;
      $content_length = 0;
      if(array_key_exists('CONTENT_LENGTH',$_SERVER))
        $content_length = intval($_SERVER['CONTENT_LENGTH']);

      if( $content_length >= static::$post_max_size || $content_length >= static::$upload_max_filesize)
        throw new Exception(_('POST size too large.'));

      $this->response = $this->getView()->getResponse();
  }

  //  'file_id' to virtual file system.
  private function associate($fids,$vdir = null)
    {
      return $this;
    }

  // heartbeat check...
  protected function do_heartbeat()
    {
      if(!isset($post['key']) && array_key_exists('multipost',$_SESSION) && is_array($_SESSION['multipost']))
        unset($_SESSION['multipost']);

      $this->getView()->ajax(array('success' => true));
      return false;
    }


  //process of single file upload
  protected function do_upload()
    {
      $name = str_sanitize($this->r['name']);
      if(empty($name))
        $name = 'upload';

      $files = $this->f[$name];

      $rv = $this->getModel()->to($files);

      $this->associate($rv);
      $this->response->redirect(get_route_url());
      return false;
    }

  protected function do_url()
    {
      $name = str_sanitize($this->p['name']);
      $ud = new UrlDownload($this->p);
      $files = false;

      if(false !== $ud->begin($name))
        $files = $ud->end();

      $rv = $this->getModel()->to($files,false);

      $this->associate($rv);
      $this->response->redirect(get_route_url());
      return false;
    }

  // ajax upload that size is under 'post_max_size';
  protected function do_ajax_upload()
    {
      $uu = new UrlencodedUpload($this->p);
      $files = false;

      if(false !== $uu->begin())
        $files = $uu->end();

      $result = $this->getModel()->to($files,false);
      $rv = array('success' => empty($result) ? false : true);
      if($rv['success'])
        $rv['fids'] = $result;

      $this->associate($rv);
      $this->getView()->ajax($rv);
      return false;
    }

  // ajax process of url upload. helper for url().
  protected function do_ajax_url()
    {
      $name = str_sanitize($this->p['name']);
      $ud = new UrlDownload($this->p);
      $files = false;

      if(false !== $ud->begin($name))
        $files = $ud->end();

      $result = $this->getModel()->to($files,false);
      $rv = array('success' => $result ? true : false);
      if($rv['success'])
        $rv['fids'] = $result;

      $this->associate($rv);
      $this->getView()->ajax($rv);

      return false;
    }

  // ajax process of split uploading with HTML5 FileReader API.
  protected function do_ajax_split()
    {
      $marker = str_sanitize($this->r['chunk-marker']);
      $chunk = new Chunk($marker);

      $cmd = str_sanitize($this->r['command']);
      $result = array('success' => false);

      switch($cmd)
        {
        case 'begin':
          $rv = array('success' => true,
                      'marker' => $chunk->begin($this->p));
          break;

        case 'chunk':
          $result = $chunk->put($this->p,$this->f);
          $rv = array('success' => $result);
          break;

        case 'abort':
          $result = $chunk->abort($this->r);
          $rv = array('success' => $result);
          break;

        case 'end':
          $files = $chunk->end($this->p);
          //ここにファイル保存処理
          $result = $this->getModel()->to($files,false);
          $rv =  array('success' => $result ? true : false);
          if($rv['success'])
            {
              $rv['fids'] = $result;
              $this->associate($result);
            }
          break;

        default:
          $rvt = array('success' => false,
                       'message' => 'command nothing!');
          break;
        }

      $this->getView()->ajax($rv);
      return false;
    }
}
