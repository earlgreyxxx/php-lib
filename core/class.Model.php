<?php
/*******************************************************************************

  Model Base

  copyright k.,nakagawa

*******************************************************************************/
abstract class Model
{
  // Statics --------------------------------------------------------------
  // Create and Get accessor for singleton instance
  public static function GetInstance(array $params = array())
  {
    static $instances = null;
    $classname = get_called_class();
    if($instances === null)
      $instances = array();

    if(!isset($instances[$classname]))
      $instances[$classname] = new static($params);

    return $instances[$classname];
  }

  // Instances ------------------------------------------------------------
  protected $handle;
  protected function getHandle()
  {
    return $this->handle;
  }
  protected function setHandle($handle)
  {
    $this->handle = $handle;
    return $this;
  }

  // constructor
  public function __construct(array $params = array())
  {
    if(isset($params['handle']))
      $this->setHandle($params['handle']);
  }

  public function getIterator()
  {
    return false;
  }
}
