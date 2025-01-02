<?php
/*******************************************************************************

  Request Controller

  All Written by K.,Nakagawa.

*******************************************************************************/
abstract class Controller
{
  // Statics
  // ---------------------------------------------------------------------------
  public static function GetInstance(array $define = array())
  {
    static $instances = array();
    $classname = get_called_class();

    if(!isset($instances[$classname]))
      $instances[$classname] = new static($define);

    return $instances[$classname];
  }

  // Instances...
  // ---------------------------------------------------------------------------

  // create view object
  abstract protected function createView();

  // view object gettter
  abstract protected function getView();

  // create model object
  abstract protected function createModel();

  // model object getter
  abstract protected function getModel();

  // initialize object
  abstract protected function init();

  // constructor
  public function __construct(?array $params = null)
  {
    $this->init();
  }
}
