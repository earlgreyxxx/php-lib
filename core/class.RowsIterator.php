<?php
/********************************************************************************
  Manage row collection. row is hash array like database row
    structure 
*******************************************************************************/
class RowsIterator extends ArrayIterator
{
  use RowsIteratorImp;

  //デフォルトオプション
  protected $default = array('before' => '',
                             'after'  => '',
                             'empty'  => '',
                             'delimitor' => '｜',
                             'bool'   => false,
                             'scramble' => false,
                             'filter' => false );

  protected $first = true;

  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($rows,$flag = 0,array $params = array())
    {
      if(!empty($params))
        $this->default = array_merge($this->default,$params);

      parent::__construct($rows,$flag);
    }

  /*------------------------------------------------------------------------------
    Instance members.
  ------------------------------------------------------------------------------*/

  // alias to current()
  public function getRow()
  {
    $rv = $this->current();
    return ($rv instanceof stdClass) ? (array)$rv : $rv;
  }

  public function move()
  {
    if($this->first === true)
      $this->first = false;
    else
      $this->next();

    return $this->valid();
  }

  // get next value but no proceed pointer
  public function peek()
  {
    $ckey = $this->key();
    if(is_numeric($ckey))
    {
      $ckey = intval($ckey) + 1;
      if($this->offsetExists($ckey))
        return $this->offsetGet($ckey);
    }

    return false;
  }

  public function rewind() : void
  {
    parent::rewind();
    $this->first = true;
  }
}
