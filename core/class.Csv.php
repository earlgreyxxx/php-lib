<?php
/*******************************************************************************

  Wrapper class for SplFileObject(CSV).
    This class is delivered with SplFileObject.

********************************************************************************/
class Csv extends SplFileObject
{
  private static $DEFAULT_OPTIONS = array( 'remove' => true,
                                           'encoding' => 'SJIS-WIN',
                                           'mode'   => 'r');
  const CALLABLE_IS_FILTER = 1;
  const CALLABLE_IS_TASK = 2;

  /**************************************************************************
   * Static Functions.
  **************************************************************************/

  // create instance from memory(array of string).
  public static function CreateFromArray(array $lines,$encoding = 'UTF-8')
  {
    $handle = false;
    $csvpath = '';
    if(defined('TEMPORARY_DIR'))
      {
        $csvpath = tempnam(TEMPORARY_DIR,'temp-');
        $handle = fopen($csvpath,'w');
        foreach($lines as $line_)
          fwrite($handle,$line_);
        fclose($handle);

        return new static($csvpath,array('remove' => true));
      }
    else
      {
        $handle = tmpfile();
        foreach($lines as $line_)
          fwrite($handle,$line_);

        return static::CreateFromStream($handle,$encoding);
      }
    
  }
  
  /**************************************************************************
    Protected methods & fields.
  **************************************************************************/
  // CSV file path
  protected $path;
  protected $options;

  protected static function prepare(&$filepath,&$options)
  {
    $path = $filepath . '.utf8';
    $fout = new SplFileObject($path,'w');
    $fin = new SplFileObject($filepath);
    $fin->rewind();
    foreach($fin as $line)
      $fout->fwrite(mb_convert_encoding($line,'UTF-8',$options['encoding']));

    $fout->fflush();
    unset($fin,$fout);

    $filepath = $path;
  }

  // constructor & destructor
  public function __construct($csvpath,array $options = array())
  {
    $this->options = array_merge(self::$DEFAULT_OPTIONS,$options);
    $this->path = $csvpath;
    if(strlen($this->path) == 0)
      throw new Exception(_('CSV file path is required.'));

    // change encoding...
    if(!preg_match('/utf-?8/i',$this->options['encoding']) && file_exists($this->path))
      self::prepare($this->path,$this->options);

    parent::__construct($this->path,$this->options['mode']);
    $flags = SplFileObject::READ_CSV | SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY;
    $this->setFlags($flags);
  }
  public function __destruct()
  {
    if($this->options['remove'])
      unlink($this->path);
  }

  /**************************************************************************
   Public methods.
  **************************************************************************/

  // read all and returns array of rows ( wrapper method for read )
  public function readAll($ignore_first = false)
  {
    return $this->read($ignore_first ? 1 : 0,-1);
  }

  /*********************************************************************
   * read and call $callable with CSV row.
   * $callable must be function with 3 arguments.
   * first argument is line number,
   * second argument is index number of loop,
   * third argument is array of row.
   *    placefolder:  function callable($linenumber,index,$row); 
   *  and if $callable returns -1, loop process is stop immediately.
   *  
   *  return value is length of process lines.
  *********************************************************************/
  public function each($callable, $offset = 0)
  {
    return $this->read($offset,-1,$callable);
  }

  /***********************************************************************
   * read csv 
    if $length is -1, returns all. 
    if $callable is set, return value is number of process.
    if $flags is set, $callable is task or filter of csv reading process
  ***********************************************************************/
  public function read($offset = 0,$length = 0,$callable = null,$flags = self::CALLABLE_IS_TASK)
  {
    $rv = false;
    if($length)
      {
        $is_task = $callable && is_callable($callable) && (($flags & CALLABLE_IS_TASK) == CALLABLE_IS_TASK);
        $is_filter = $callable && is_callable($callable) && (($flags & CALLABLE_IS_FILTER) == CALLABLE_IS_FILTER);
        
        $count = 0;
        $num = $offset;
        $ite = new LimitIterator($this, $offset, $length);
        foreach($ite as $row)
        {
          $num++;
          if(is_null($row[0]))
            continue;

          if($is_task)
            {
              if(!is_int($rv))
                $rv = 0;

              $result = call_user_func_array($callable,array($num,$count++,$row));
              $rv++;
              if(intval($result) < 0)
                break;
            }
          else
            {
              if(!is_array($rv))
                $rv = array();

              if($is_filter)
                {
                  if(call_user_func_array($callable,array($num,$count++,$row)))
                    $rv[] = $row;
                }
              else
                {
                  $rv[] = $row;
                }
            }
        }
      }

    return $rv;
  }

  // search pattern in specified field...
  public function match($pattern,$fields,$offset = 0)
  {
    if(!is_array($fields))
      $fields = array($fields);

    $fields = array_filter(function($v) { return is_int($v); },$fields);

    $callback = function($num,$i,$row) use($fields,$pattern,&$matches)
      {
        $haystack = '';
        foreach($fields as $f_)
          {
            if(array_key_exists($f_,$row))
              $haystack .= $row[$f_];
          }

        if(!empty($haystack))
          {
            if(preg_match($pattern,$haystack,$match))
              $matches[] = array('line' => $num,'match' => $match,'row' => $row);
          }
      };

    $rv = $this->read($offset,-1,$callback);
    return $rv ? $matches : $rv;
  }

  public function getIterator($offset = 0,$length = 0)
  {
    $this->rewind();
    return $length ? new LimitIterator($this, $offset, $length) : this;
  }
}
