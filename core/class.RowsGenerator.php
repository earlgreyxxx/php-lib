<?php
/********************************************************************************
  Manage row collection. row is hash array like database row
    structure 
*******************************************************************************/
class RowsGenerator
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

  protected $generator = null;
  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($generator,array $params = array())
    {
      if(!empty($params))
        $this->default = array_merge($this->default,$params);

      if(!($generator instanceof Generator))
        throw new RuntimeException(_('invalid argument error'));

      $this->generator = $generator;
    }

  /*------------------------------------------------------------------------------
    Instance members.
  ------------------------------------------------------------------------------*/

  // override
  public function getRow()
  {
    $rv = $this->generator->current();
    if($rv instanceof stdClass)
      $rv = (array)$rv;

    return $rv;
  }

  public function move()
  {
    if($this->first === true)
      $this->first = false;
    else
      $this->generator->next();

    return $this->generator->valid();
  }

  public function rewind()
  {
    return;
  }

}
