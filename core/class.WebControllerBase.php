<?php
/*******************************************************************************

  Base Controller for web application

  All Written by K.,Nakagawa.

*******************************************************************************/
class WebControllerBase extends ControllerBase
{
  // parameters
  protected ?array $r = null;
  protected ?array $f = null;
  protected ?array $p = null;
  protected ?array $g = null;

  //Constructor
  public function __construct(array $define = [])
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
  protected function createView() : ViewBase
  {
    return ViewBase::CreateInstance();
  }
  public function getView() : ViewBase
  {
    return $this->view;
  }
  protected function setView(ViewBase $view) : ?ViewBase
    {
      $rv = $this->view;
      if(!($view instanceof ViewBase))
        throw new Exception(_('Invalid arguments was given...'));

      $this->view = $view;
      return $rv;
    }

  protected function init() : void
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
  protected function getPage(string $name = 'p') : int
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
