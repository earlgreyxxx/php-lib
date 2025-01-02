<?php

/************************************************************************************
  縮小画像生成 基本抽象クラス
************************************************************************************/
abstract class ResampleImage
{
  protected $image = false;
  protected $width;
  protected $height;
  protected $path;
  protected $type;

  // 画像をリサンプルして生成した画像のbasenameを返す。 
  protected abstract function resample_internal($size,$size_format,$out_dir,$out_format,$pos);

  // 長辺サイズを基準にリサンプルサイズを返す。
  protected function resampled_size($length,$is_short = false)
    {
      if($this->width >= $this->height)
        {
          $new_width = $length;
          $new_height = round(($this->height / $this->width) * $new_width);
        }
      else
        {
          $new_height = $length;
          $new_width = round(($this->width / $this->height) * $new_height);
        }

      return array('width' => $new_width,'height' => $new_height);
    }

  public function get_image()
    {
      return $this->image;
    }

  // 正方形サイズにクロッピング。
  public function crop_square($length,$out_dir,$out_format = 'png')
    {
      return $this->resample_internal(array('width' => $length,'height' => $length),
                                      '',
                                      $out_dir,
                                      $out_format,
                                      $this->width >= $this->height ? array('x' => intval(($this->width - $this->height) / 2),'y' => 0) : array('x' => 0, 'y' => intval(($this->height - $this->width) / 2)));
    }

  //長辺サイズを基準にリサンプルする。
  public function resample($length,$out_dir,$out_format = 'png')
    {
      $size = $this->resampled_size($length);
      $size_format = sprintf('-%dx%d',$size['width'],$size['height']);

      return $this->resample_internal($size,$size_format,$out_dir,$out_format);
    }

  //幅を指定
  public function resampleW($length,$out_dir,$out_format = 'png')
    {
      $size = array('width' => $length,
                    'height' => round(($this->height / $this->width) * $length));
      $size_format = sprintf('-%d',$size['width']);
      
      return $this->resample_internal($size,$size_format,$out_dir,$out_format);
    }

  //高さを指定
  public function resampleH($length,$out_dir,$out_format = 'png')
    {
      $size = array('height' => $length,
                    'width' => round(($this->width / $this->height) * $length));
      $size_format = sprintf('+%d',$size['height']);

      return $this->resample_internal($size,$size_format,$out_dir,$out_format);
    }

  public function resampleWH(array $size,$out_dir,$out_format = 'png')
    {
      if(!$size['width'] || !$size['height'])
        return false;

      $size_format = sprintf('-%dx%d',$size['width'],$size['height']);

      return $this->resample_internal($size,$size_format,$out_dir,$out_format);
    }

  public function width()
    {
      return $this->width;
    }

  public function height()
    {
      return $this->height;
    }

  public function size()
    {
      return array('width' => $this->width,
                   'height' => $this->height);
    }
}

