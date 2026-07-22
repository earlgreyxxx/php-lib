<?php
/*******************************************************************************

  Request Controller

  All Written by K.,Nakagawa.

*******************************************************************************/
abstract class Controller
{
  // Statics
  // ---------------------------------------------------------------------------
  public static function GetInstance(array $define = []) : static
  {
    static $instances = [];
    $classname = get_called_class();

    if(!isset($instances[$classname]))
      $instances[$classname] = new static($define);

    return $instances[$classname];
  }

  // Instances...
  // ---------------------------------------------------------------------------

  // create view object
  abstract protected function createView() : ?ViewBase;

  // view object gettter
  abstract protected function getView() : ViewBase;

  // create model object
  abstract protected function createModel() : mixed;

  // model object getter
  abstract protected function getModel() : mixed;

  // initialize object
  abstract protected function init() : void;

  // constructor
  public function __construct(?array $params = null)
  {
    $this->init();
  }
}
