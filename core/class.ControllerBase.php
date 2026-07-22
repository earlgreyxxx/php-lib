<?php
/*******************************************************************************

  Base Controller

  All Written by K.,Nakagawa.

*******************************************************************************/
abstract class ControllerBase extends Controller
{
  // Statics...
  // ------------------------------------------------------------------------------
  public static function Invoke(string $method,array $params) : void
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
  protected ViewBase $view;
  protected mixed $model;

  // base model implementation
  protected function createModel() : mixed
  {
    return null;
  }

  protected function getModel() : mixed
  {
    return $this->model;
  }

  protected function createView() : ViewBase
  {
    throw new RuntimeException(_('not implement yet'));
  }

  protected function getView() : ViewBase
  {
    return $this->view;
  }

  protected function init() : void
  {
    $this->view = $this->createView();
    $this->model = $this->createModel();
  }
}
