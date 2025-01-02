<?php
/*******************************************************************************

  Base Controller for web application

  All Written by K.,Nakagawa.

*******************************************************************************/
class WebControllerBase extends ControllerBase
{
  // parameters
  protected $r = null;
  protected $f = null;
  protected $p = null;
  protected $g = null;

  //Constructor
  public function __construct(array $define = array())
  {
    if(isset($define['input']) && is_array($define['input']))
    {
      list($request,$files,$post,$get) = $define['input'];
      $this->r = &get_request($request);
      $this->f = &get_files($files);
      $this->p = &get_post($post);
      $this->g = &get_get($get);
    }
    else
    {
      $this->r = &get_request();
      $this->f = &get_files();
      $this->p = &get_post();
      $this->g = &get_get();
    }

    $this->init();
  }

  // base view implementation
  protected function createView()
  {
    return ViewBase::CreateInstance();
  }
  public function getView()
  {
    return $this->view;
  }
  protected function setView($view)
    {
      $rv = $this->view;
      if(!($view instanceof ViewBase))
        throw new Exception(_('Invalid arguments was given...'));

      $this->view = $view;
      return $rv;
    }

  protected function init()
  {
    parent::init();
    if($this->view !== false && $this->view instanceof ViewBase)
    { 
      //set default header,footer,content...
      $this->view->setHeader(array('header'));
      $this->view->setFooter(array('footer'));
    }
    do_action('init');
  }

  // get current page number
  protected function getPage($name = 'p')
  {
    $r = $this->r;
    $rv = 1;

    if(isset($r[$name]) && is_numeric($r[$name]))
    {
      $rv = intval($r[$name]);
      if($rv <= 0)
        $rv = 1;
    }
    return $rv;
  }
}
