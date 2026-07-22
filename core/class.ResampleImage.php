<?php

/************************************************************************************
  縮小画像生成 基本抽象クラス
************************************************************************************/
abstract class ResampleImage
{
  protected mixed $image = false;
  protected int $width;
  protected int $height;
  protected string $path;
  protected string $type;

  // 画像をリサンプルして生成した画像のbasenameを返す。 
  protected abstract function resample_internal(array $size,string $size_format,string $out_dir,string $out_format,array $pos = ['x' => 0,'y' => 0]) : string;

  // 長辺サイズを基準にリサンプルサイズを返す。
  protected function resampled_size(int $length,bool $is_short = false) : array
  {
    if ($this->width >= $this->height)
    {
      $new_width = $length;
      $new_height = round(($this->height / $this->width) * $new_width);
    }
    else
    {
      $new_height = $length;
      $new_width = round(($this->width / $this->height) * $new_height);
    }

    return ['width' => $new_width, 'height' => $new_height];
  }

  public function get_image() : mixed
    {
      return $this->image;
    }

  // 正方形サイズにクロッピング。
  public function crop_square(int $length,string $out_dir,string $out_format = 'png') : string
  {
    return $this->resample_internal(
      ['width' => $length, 'height' => $length],
      '',
      $out_dir,
      $out_format,
      $this->width >= $this->height ? array('x' => intval(($this->width - $this->height) / 2), 'y' => 0) : array('x' => 0, 'y' => intval(($this->height - $this->width) / 2))
    );
  }

  //長辺サイズを基準にリサンプルする。
  public function resample(int $length, string $out_dir, string $out_format = 'png') : string
  {
    $size = $this->resampled_size($length);
    $size_format = sprintf('-%dx%d', $size['width'], $size['height']);

    return $this->resample_internal($size, $size_format, $out_dir, $out_format);
  }

  //幅を指定
  public function resampleW(int $length,string $out_dir,string $out_format = 'png') : string
  {
    $size = [
      'width' => $length,
      'height' => round(($this->height / $this->width) * $length)
    ];
    $size_format = sprintf('-%d', $size['width']);

    return $this->resample_internal($size, $size_format, $out_dir, $out_format);
  }

  //高さを指定
  public function resampleH(int $length,string $out_dir,string $out_format = 'png') : string
  {
    $size = [
      'height' => $length,
      'width' => round(($this->width / $this->height) * $length)
    ];
    $size_format = sprintf('+%d', $size['height']);

    return $this->resample_internal($size, $size_format, $out_dir, $out_format);
  }

  public function resampleWH(array $size,string $out_dir,string $out_format = 'png') : string
  {
    if (!$size['width'] || !$size['height'])
      return false;

    $size_format = sprintf('-%dx%d', $size['width'], $size['height']);

    return $this->resample_internal($size, $size_format, $out_dir, $out_format);
  }

  public function width() : int
  {
    return $this->width;
  }

  public function height() : int
  {
    return $this->height;
  }

  public function size() : array
  {
    return [
      'width' => $this->width,
      'height' => $this->height
    ];
  }
}

