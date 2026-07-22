<?php
/********************************************************************************
  Manage row collection. row is hash array like database row
    structure 
*******************************************************************************/
class RowsGenerator
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

  protected ?Generator $generator = null;
  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct(Generator $generator,array $params = [])
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
  public function getRow() : array
  {
    $rv = $this->generator->current();
    if($rv instanceof stdClass)
      $rv = (array)$rv;

    return $rv;
  }

  public function move() : bool
  {
    if($this->first === true)
      $this->first = false;
    else
      $this->generator->next();

    return $this->generator->valid();
  }

  public function rewind() : void
  {
    return;
  }
}
