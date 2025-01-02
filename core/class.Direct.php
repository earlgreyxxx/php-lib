<?php
/*****************************************************************************

  直接出力

*****************************************************************************/
class Direct
{
  const BUF_SIZE = 65536;

  public static function out($path,$mime,$conv = true)
    {
      return self::rawout($path,
                          array('Content-type' => $mime),
                          $conv);
    }

  public static function partial($path,$start,$end = -1)
    {
      $rv = false;
      $path = get_platform_filename($path);

      // returns false if start or end is not integer type.
      if(!is_int($start) || !is_int($end) || $start < 0 || ($end > 0 && $start >= $end) || $end == 0)
        return $rv;

      if(file_exists($path))
        {
          ob_clean();
          flush();

          while (ob_get_clean())
            ob_end_clean();

          $length = $end - $start + 1;
          $mod = $length % self::BUF_SIZE;
          $limit = intval(floor($length / self::BUF_SIZE));

          $fh = fopen($path,'rb');
          fseek($fh,$start);
          $count = 1;
          $buf_read = 0;
          while($count++ <= $limit && false !== ($buf = fread($fh,self::BUF_SIZE)))
          {
            $buf_read += strlen($buf);
            echo $buf;
          }

          if(false !== ($buf = fread($fh,$mod)))
          {
            $buf_read += strlen($buf);
            echo $buf;
          }
          $rv = $buf_read;
          flush();
        }

      if(!$rv)
        throw new Exception($length);

      return $rv;
    }

  public static function rawout($path,$headers = null,$conv = true)
    {
      $rv = false;

      if($conv)
        $path = get_platform_filename($path);

      if(file_exists($path))
        {
          if(!empty($headers))
            {
              if(is_array($headers))
              {
                foreach($headers as $name => $value)
                  header(sprintf('%s: %s',$name,is_array($value) ? implode('; ',$value) : $value));
              }
              else if(is_string($headers))
              {
                header($headers);
              }
            }

          ob_clean();
          flush();

          while (ob_get_clean())
            ob_end_clean();

          readfile($path);

          $rv = true;
        }

      return $rv;
    }
}
