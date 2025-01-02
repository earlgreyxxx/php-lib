<?php
/********************************************************************************
  Manage row collection. row is hash array like database row
    structure 
*******************************************************************************/
trait RowsIteratorImp
{
  /*------------------------------------------------------------------------------
    Instance members.
  ------------------------------------------------------------------------------*/
  public function getValue($name,$raw = true)
  {
    $rv = false;
    $row = $this->getRow();
    if(is_array($row) && is_string($name) && !empty($name))
      $rv = array_key_exists($name,$row) ? $row[$name] : null;

    if($raw === false && is_string($rv) && !empty($rv))
      $rv = str_sanitize($rv);

    return $rv;
  }

  public function _imp_print($raw,$name,$before,$after)
  {
    $options = array_merge(
      $this->default,
      is_array($before) ? $before : ['before' => $before,'after' => $after]
    );

    $rv = $this->getValue($name,$raw);

    if($options['bool'] === true && array_key_exists('t',$options) && array_key_exists('f',$options))
    {
      $rv = $options[$rv ? 't' : 'f'];
    }
    else
    {
      if(!isset($rv) && isset($options['empty']))
        $rv = $options['empty'];

      if(is_array($rv))
        $rv = implode($options['delimitor'],$rv);
    }

    if(isset($rv) || isset($options['empty']))
    {
      $scramble = $options['scramble'];
      $filter = $options['filter'];
      if($filter !== false && is_callable($filter))
      {
        $rv = call_user_func($filter,$rv);
      }
      else if($scramble !== false)
      {
        if(is_callable($scramble))
          $rv = call_user_func($scramble,$rv);
        else if(is_string($scramble))
          $rv = $scramble;
        else if(is_int($scramble))
          $rv = str_repeat('*',$scramble);
        else
          $rv = str_repeat('*',10);
      }
      
      if(isset($rv) && strlen($rv) > 0)
        echo $options['before'],$rv,$options['after'];
    }
  }

  public function value($name,$before = '',$after = '')
  {
    $this->_imp_print(true,$name,$before,$after);
  }

  public function html($name,$before = '',$after = '')
  {
    $this->_imp_print(false,$name,$before,$after);
  }

  public function move()
  {
    if($this->first === true)
      $this->first = false;
    else
      $this->next();

    return $this->valid();
  }

  /*----------------------------------------------------------------------
   ex) $obj->valueTo( 'gender', array( 1 => '男', 2 => '女'));
  ----------------------------------------------------------------------*/
  public function getValueTo($name,$arr)
  {
    $val = $this->getValue($name);
    return array_key_exists($val,$arr) ? $arr[$val] : '';
  }

  public function valueTo($name,$arr,$before = '',$after = '')
  {
    $rv = $this->getValueTo($name,$arr);
    if(!empty($rv))
      echo $before,$rv,$after;
  }

  /*----------------------------------------------------------------------
   ex) value is converted to datetime value with $format.
  ----------------------------------------------------------------------*/
  public function getDate($name,$format = 'Y/m/d')
  {
    $rv = $this->getValue($name);

    if(!empty($rv))
    {
      if(is_string($rv))
      {
        $dt = new Datetime($rv);
        $rv = $dt->format($format);
      }
      else if(is_numeric($rv))
      {
        $rv = date($format,intval($rv));
      }
    }
    return $rv;
  }

  public function date($name,$format = 'Y/m/d',$empty = null)
  {
    $rv = $this->getDate($name,$format);
    if(empty($rv) && !empty($empty))
      $rv = $empty;

    if(!empty($rv))
      echo $rv;
  }
}
