<?php
require_once LIB_INC_DIR.'/functions.common.php';

/************************************************************************************
  ImageMagick関連の関数定義
************************************************************************************/
class IMResampleImage : ResampleImage
{
  protected function resample_internal($size,$size_format,$out_dir,$out_format,$pos = array('x' => 0,'y' => 0))
    {
      $width = $size['width'];
      $height = $size['height'];

      $src = array('width' => $this->width,
                   'height' => $this->height);

      if($pos['x'] > 0 || $pos['y'] > 0)
        {
          if($pos['x'] > 0)
            $src = array('width' => $this->height,'height' => $this->height);
          else if($pos['y'] > 0)
            $src = array('width' => $this->width,'height' => $this->width);
        }

      $rv = false;
      $image = imagecreatetruecolor($width,$height);

      if(true == imagecopyresampled($image,$this->image,
                                    0,0,
                                    $pos['x'],$pos['y'],
                                    $width,$height,
                                    $src['width'],$src['height']))
        {
          $filename = pathinfo($this->path,PATHINFO_FILENAME);
          $out_filepath = sprintf('%s/%s%s.%s',$out_dir,$filename,$size_format,$out_format);

          switch($out_format)
            {
            case 'jpg':
              imagejpeg($image,get_platform_filename($out_filepath),80);
              break;
            case 'png':
              imagepng($image,get_platform_filename($out_filepath),9);
            }

          $rv = basename($out_filepath);
        }

      imagedestroy($image);

      return $rv;
    }

  public function __construct($image_path)
    {
      $this->path = str_replace('\\','/',$image_path);

      $info = getimagesize(get_platform_filename($image_path));

      $this->width = $info[0];
      $this->height = $info[1];
      $this->type = $info[2];

      switch($this->type)
        {
        case IMAGETYPE_JPEG:
          $this->image = imagecreatefromjpeg(get_platform_filename($image_path));
          break;
        case IMAGETYPE_PNG:
          $this->image = imagecreatefrompng(get_platform_filename($image_path));
          break;
        case IMAGETYPE_GIF:
          $this->image = imagecreatefromgif(get_platform_filename($image_path));
          break;
        }
    }
}

