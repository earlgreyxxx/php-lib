<?php
/*******************************************************************************

  Base Controller

  All Written by K.,Nakagawa.

*******************************************************************************/
abstract class ControllerBase extends Controller
{
  // Statics...
  // ------------------------------------------------------------------------------
  public static function Invoke($method,$params)
  {
    try {
      $inst = new static();
      if(false !== call_user_func_array(array($inst,$method),$params))
        $inst->getView()->render();
    }
    catch(Exception $e) {
      $view = ViewBase::CreateInstance();
      $tmpl = $view->getTemplate();
      $message = $e->getMessage();
      $tmpl['server_error'] = $message;
      $tmpl['file'] = $e->getFile();
      $tmpl['line'] = $e->getLine();

      $view->error500();
    }
  }


  // Instances...
  // ------------------------------------------------------------------------------

  // view & model
  protected $view;
  protected $model;

  // base model implementation
  protected function createModel()
  {
    return null;
  }

  protected function getModel()
  {
    return $this->model;
  }

  protected function createView()
  {
    return null;
  }

  protected function getView()
  {
    return $this->view;
  }

  protected function init()
  {
    $this->view = $this->createView();
    $this->model = $this->createModel();
  }
}
