<?php
/*******************************************************************************

  Request Dispatcher ( based mode & action )

  All Written by K.,Nakagawa.

*******************************************************************************/

abstract class Dispatch extends Controller
{
  //Constructor
  public function __construct(array $define = array())
    {
      if(isset($define['primary']) && !empty($define['primary']))
        $this->primary = $define['primary'];

      if(isset($define['secondary']) && !empty($define['secondary']))
        $this->secondary = $define['secondary'];

      if(isset($define['input']) && is_array($define['input']))
      {
        list($request,$files,$post,$get) = $define['input'];
        $this->r = &get_request($request);
        $this->f = &get_files($files);
        $this->p = &get_post($post);
        $this->g = &get_get($get);
      }

      $this->init();
    }

  private $primary = 'mode';
  private $secondary = 'action';

  // parameters
  protected $r = null;
  protected $f = null;
  protected $p = null;
  protected $g = null;

  // default method when that can not be spcified call method
  protected function __default()
    {
      //メソッドが特定されない場合は、エラー出力
      echo 'Default method is now called.';
      return false;
    }

  private function decide()
    {
      $rv = false;
      $r = $this->r;

      if(array_key_exists($this->primary,$r))
        $prm = preg_replace('/\W+/','',$r[$this->primary]);
      if(array_key_exists($this->secondary,$r))
        $sec = preg_replace('/\W+/','',$r[$this->secondary]);

      if(empty($prm))
        {
          $rv = '__default';
        }
      else
        {
          $rv = $prm;
          if(!empty($sec))
            $rv = $prm.'_'.$sec;

          $rv = "do_$rv";
        }
      return $rv;
    }

  protected function requestExists($name)
    {
      return array_key_exists($name,$this->r);
    }
  protected function postExists($name)
    {
      return array_key_exists($name,$this->p);
    }
  protected function getExists($name)
    {
      return array_key_exists($name,$this->g);
    }
  protected function filesExists($name)
    {
      return array_key_exists($name,$this->f);
    }

  // public method
  public function invoke()
    {
      $rv = true;
      if(($method = $this->decide()) && method_exists($this,$method))
        $rv = call_user_func(array($this,$method));
      else
        throw new Exception(_('Dispatcher can not determine method to process request!'));

      if($rv !== false)
        {
          if($view = $this->getView())
            $view->render();
        }

      return $rv;
    }
}

