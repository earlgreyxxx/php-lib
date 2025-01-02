<?php
/*******************************************************************************

  View base class

*******************************************************************************/
class ViewBase
{
  //Factory 
  public static function CreateInstance($tmpl = null)
  {
    return new static($tmpl);
  }

  private $tmpl;

  protected $response;
  protected $header;
  protected $footer;
  protected $content;

  protected $title;

  // create template object
  protected function createTemplate()
  {
    return TemplateBase::GetInstance(TEMPLATE_DIR,array('url' => TEMPLATE_URL));
  }

  // initialize this object
  protected function init($tmpl)
  {
    if(!($tmpl instanceof TemplateBase))
    { 
      $tmpl = $this->createTemplate();
      if(!($tmpl instanceof TemplateBase))
        throw new Exception(_('Invalid arguments was given...'));
    }
    $this->tmpl = $tmpl;
    $this->response = Response::GetInstance();
  }

  // $template_values is array ( initialize template object )
  public function __construct($tmpl = null)
  {
    $this->init($tmpl);
  }

  // returns current template object
  public function getTemplate()
  {
    return $this->tmpl;
  }
  public function setTemplate($template)
  {
    $rv = $this->tmpl;
    if(!($template instanceof TemplateBase))
      throw new Exception(_('Invalid arguments was given...'));

    $this->tmpl = $template;
    return $rv;
  }
  public function setHeader($template)
  {
    $this->header = $template;
  }
  public function getHeader()
  {
    return $this->header;
  }

  public function setFooter($template)
  {
    $this->footer = $template;
  }
  public function getFooter()
  {
    return $this->footer;
  }

  public function setContent($template)
  {
    $this->content = $template;
  }
  public function getContent()
  {
    return $this->content;
  }


  public function setTitle($title)
  {
    $this->title = $title;
  }
  public function addTitle($title)
  {
    if(empty($title))
      return false;

    if(!is_array($this->title))
      $this->title = empty($this->title) ? array() : array($this->title);

    if(is_array($title))
      $this->title = array_merge($this->title,$title);
    else
      $this->title[] = $title;
  }

  public function getResponse()
  {
    return $this->response;
  }

  public function render()
  {
    $title = $this->title;
    add_action('title',
               function() use($title) 
               {
                 if(empty($title))
                   return;
                 echo is_array($title) ? implode('：',array_reverse($this->title)) : $title;
               });

    $tmpl = $this->getTemplate();
    foreach(array($this->getHeader(),$this->getContent(),$this->getFooter()) as $t_)
    {
      if($t_ === false)
        continue;

      if(!empty($t_))
      {
        if(is_string($t_))
          echo $t_;
        else if(is_array($t_) && method_exists($tmpl,'apply'))
          call_user_func_array(array($tmpl,'apply'),$t_);
        else if(is_callable($t_))
          call_user_func($t_);
        else
          throw new Exception(_('can not process rendering template!'));
      }
    }
  }

  //output JSON data of result upload processing.
  public function ajax($ar,$is_exit = true)
  {
    $response = $this->getResponse();
    $response->nocache();
    $response->content_type('application/json');
    echo json_encode($ar);

    if($is_exit)
      exit;
  }

  // show 401 Authroization error
  public function error401($is_exit = true)
  {
    $this->error_code(401,$is_exit);
  }

  // show 403 Forbidden error
  public function error403($is_exit = true)
  {
    $this->error_code(403,$is_exit);
  }

  // show 404 File not found error
  public function error404($is_exit = true)
  {
    $this->error_code(404,$is_exit);
  }

  // show 500 Internal Server Error
  public function error500($is_exit = true)
  {
    $this->error_code(500,$is_exit);
  }

  public function error_code(int $status_code,bool $is_exit = true)
  {
    $response = $this->getResponse();
    $response->status($status_code);
    $response->content_type('text/html');

    $headers = [];
    $template = ['error/' . strval($status_code)];

    $this->error($template,$headers,$is_exit);
  }

  // show generic error
  public function error($template,$headers = array(),$is_exit = true)
  {
    if(!is_array($headers))
      $headers = array($headers);

    $this->response->headers($headers);
    if(is_array($template))
    {
      if(count($template) < 2)
        $template[1] = '';

      list($name,$suffix) = $template;
      $this->getTemplate()->apply($name,$suffix);
    }
    else
    {
      echo $template;
    }

    if($is_exit)
      exit; 
  }
}
