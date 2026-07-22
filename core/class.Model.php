<?php
/*******************************************************************************

  Model Base

  copyright k.,nakagawa

*******************************************************************************/
abstract class Model
{
  // Statics --------------------------------------------------------------
  // Create and Get accessor for singleton instance
  public static function GetInstance(array $params = []) : static
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
  protected PDOExtension $handle;
  protected function getHandle() : PDOExtension
  {
    return $this->handle;
  }
  protected function setHandle(PDOExtension $handle) : static
  {
    $this->handle = $handle;
    return $this;
  }

  // constructor
  public function __construct(array $params = [])
  {
    if(isset($params['handle']) && ($params['handle'] instanceof PDOExtension))
      $this->setHandle($params['handle']);
  }

  public function getIterator() : mixed
  {
    return false;
  }
}
