<?php
/********************************************************************************
  Manage row collection. row is hash array like database row
    structure 
*******************************************************************************/
class RowsIterator extends ArrayIterator
{
  use RowsIteratorImp;

  //デフォルトオプション
  protected array $default = [
    'before' => '',
    'after'  => '',
    'empty'  => '',
    'delimitor' => '｜',
    'bool'   => false,
    'scramble' => false,
    'filter' => false
  ];

  protected bool $first = true;

  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct(array $rows,int $flag = 0,array $params = [])
    {
      if(!empty($params))
        $this->default = array_merge($this->default,$params);

      parent::__construct($rows,$flag);
    }

  /*------------------------------------------------------------------------------
    Instance members.
  ------------------------------------------------------------------------------*/

  // alias to current()
  public function getRow() : array
  {
    $rv = $this->current();
    return ($rv instanceof stdClass) ? (array)$rv : $rv;
  }

  public function move() : bool
  {
    if($this->first === true)
      $this->first = false;
    else
      $this->next();

    return $this->valid();
  }

  // get next value but no proceed pointer
  public function peek() : mixed
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
