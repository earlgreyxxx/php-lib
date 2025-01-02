<?php
/*******************************************************************************

  URLEncoded upload acceptor for ajax uploader.

  Upload data is provided with 'POST' Method. 

*******************************************************************************/
class UrlencodedUpload
{
  /*------------------------------------------------------------------------------
    Instances.
  ------------------------------------------------------------------------------*/
  protected $p;
  protected $files = array('name' => array(),
                           'size' => array(),
                           'type' => array(),
                           'tmp_name' => array());

  public function __construct($postdata)
    {
      $this->p = $postdata;
    }

  //ファイルダウンロードを開始する。
  public function begin($name_data = 'data',$name_name = 'name',$name_size = 'size',$name_type = 'type')
    {
      $rv = false;

      if(!is_array($this->p[$name_data]) || !is_array($this->p[$name_size]) || !is_array($this->p[$name_name]) || !is_array($this->p[$name_type]))
        return $rv;

      $num = count($this->p[$name_data]);

      $data = $this->p[$name_data];
      $size = $this->p[$name_size];
      $name = $this->p[$name_name];
      $type = $this->p[$name_type];

      $files = array('size' => array(),
                     'name' => array(),
                     'type' => array(),
                     'tmp_name' => array());

      $temp_dir = defined('TEMPORARY_DIR') ? TEMPORARY_DIR : sys_get_temp_dir();
      for($i=0;$i<$num;$i++)
        {
          if(preg_match('/^data:.*?;base64,/',$data[$i],$matches))
            {
              $temp_path = get_temporary_filepath($temp_dir,$i);

              if(($fh = fopen($temp_path,'x')) === false)
               break;

              fwrite($fh,base64_decode(substr($data[$i],strlen($matches[0]))));
              fclose($fh);

              $files['size'][$i] = intval($size[$i]);
              $files['name'][$i] = $name[$i];
              $files['type'][$i] = str_sanitize($type[$i]);
              $files['tmp_name'][$i] = $temp_path;

              unset($data[$i]);
              unset($this->p[$name_data][$i]);
           }
       }
      $this->files = $files;
      return true;
    }

  //$_FILESを返す。
  public function end()
    {
      return $this->files;
    }
}
