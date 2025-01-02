<?php
/*******************************************************************************

  Template base class

*******************************************************************************/

class TemplateBase extends KeyValueCollection
{
  protected $path;
  protected $url;
  protected $rows = null;
  protected $header;
  protected $footer;
  protected $id;

  protected function get_filepath($name,$suffix = '')
  {
    $template_filename = sprintf('%s/%s',$this->path,$name);

    if(!empty($suffix))
    {
      $tempo = sprintf('%s-%s',$template_filename,$suffix);
      if(file_exists($tempo . '.php'))
        $template_filename = $tempo;
    }

    return $template_filename . '.php';
  }

  public function __construct($name,$params = array())
  {
    if(empty($name) || !file_exists($name) || !is_dir($name))
      throw new Exception(_('invalid first parameter was given'));

    $this->path = $name;
    $this->url = array_key_exists('url',$params) ? $params['url'] : '';
    $this->header = array_key_exists('header',$params) ? $params['header'] : null;
    $this->footer = array_key_exists('footer',$params) ? $params['footer'] : null;
    if(array_key_exists('iterator',$params) && ($params['iterator'] instanceof ArrayIterator))
      $this->iterators[] = $params['iterator'];

    $this->id = str_uniqid($name.'-');
  }

  public function get_dir()
  {
    return $this->path;
  }

  public function get_url()
  {
    return $this->url;
  }
  public function url()
  {
    echo $this->get_url();
  }

  public function value($k,$subkey = '')
  {
    $params = array(
      'suffix' => '',
      'empty' => null,
      'html' => false,
      'crlf' => null,
      'filter' => null,
      'before' => '',
      'after' => '',
      'force_only_value' => false,
      'scramble' => false,
    );
    if(is_array($subkey)) 
      $params = array_merge($params,$subkey);
    else
      $params['suffix'] = $subkey;

    extract($params,EXTR_SKIP);
    if(!empty($suffix))
      $k .= "-$suffix";

    $val = $this->get($k);
    $orig = $val;

    if(!isset($val) && isset($empty))
      $val = $empty;

    if($html === true)
      $val = str_sanitize($val);

    if($crlf !== null)
      $val = preg_replace('/(?:[\n\r]+)/',$crlf,$val);

    if(isset($val) && isset($filter) && is_callable($filter))
      $val = call_user_func($filter,$val);

    if(isset($val))
    {
      if($force_only_value === true && empty($orig))
        echo $this->scramble($scramble,$val);
      else
        echo $before,$this->scramble($scramble,$val),$after;
    }
  }

  public function date($key,$datefmt = 'Y年m月d日')
  {
    if(isset($this[$key]) && !empty($this[$key]))
    {
      $ctime = !is_int($this[$key]) && !is_numeric($this[$key]) ? strtotime($this[$key]) : intval($this[$key]);
      echo date($datefmt,$ctime);
    }
  }

  private function scramble($scramble,$in)
  {
    $rv = $in;
    if($scramble !== false)
    {
      if(is_callable($scramble))
        $rv = call_user_func($scramble,$rv);
      else if(is_string($scramble))
        $rv = $scramble;
      else if(is_int($scramble))
        $rv = str_repeat('*',$scramble);
      else
        $rv = str_repeat('*',10);
    }
    return $rv;
  }

  public function html($k,$subkey = '')
  {
    $params = array(
      'html' => true,
      'crlf' => '<br>'
    );
    if(is_array($subkey)) 
      $params = array_merge($params,$subkey);
    else
      $params['suffix'] = $subkey;

    $this->value($k,$params);
  }
  /*------------------------------------------------------------------------------
    Outputs the value obtained by using the value obtained by the first argument as 
    the key of the array specified by the second argument

    ex) valueTo( 'gender', array( 1 => '男', 2 => '女')); 
      if container['gender'] is '1', output is '男'...
  ------------------------------------------------------------------------------*/
  public function valueTo($name,array $hash,array $params = array())
  {
    $default_params = array('before' => '','after' => '');
    $params = array_merge($default_params,$params);
    extract($params,EXTR_SKIP);

    if(empty($name))
      return;

    $v = $this->get($name);
    $rv = array_key_exists($v,$hash) ? $hash[$v] : '';

    echo $before,$rv,$after;
  }

  // if match , output string
  public function valueIf($key,$match,$output,$default = '')
  {
    $rv = $default;
    if(is_callable($match))
    {
      if(call_user_func($match,$this->get($key)) === true)
        $rv = $output;
    }
    else if($this->get($key) === $match)
    {
      $rv = $output;
    }

    echo $rv;
  }

  // merge (n => v,....) to container. pair("n-container[n]" => v) is merged to container  
  // purpose of this methos is ... used for select tag or input[radio] tags...
  public function merge(array $nv)
  {
    if(count($nv) == 0)
      return false;

    foreach($nv as $n => $v)
      {
        $rv = $this->get($n);
        if(!empty($rv) && (is_string($rv) || is_int($rv)))
          $this->set("$n-$rv",$v);
      }
  }

  // existing check template name
  public function file_exists($templatename,$suffix = '')
  {
    $templatepath = $this->get_filepath($templatename,$suffix);
    return file_exists($templatepath);
  }

  // include template file with template name and suffix...
  public function apply($templatename,$suffix = '',$multitime = false)
  {
    $filepath = $this->get_filepath($templatename,$suffix);

    if(file_exists($filepath))
    {
      $tmpl = $this;
      if($multitime === true)
        include($filepath);
      else
        include_once($filepath);
    }
  }

  // include template file with template name and suffix...
  public function insert($templatename,$suffix,array $values,$multitime = true)
  {
    $filepath = $this->get_filepath($templatename,$suffix);

    if(file_exists($filepath))
    {
      $tmpl = $this;
      extract($values);
      if($multitime === true)
        include($filepath);
      else
        include_once($filepath);
    }
  }

  // unlike apply, 1st argument is template file path instead of temlatename
  public function assign($templatefile,$multitime = false)
  {
    if(file_exists($templatefile))
    {
      $tmpl = $this;
      if($multitime === true)
        include($templatefile);
      else
        include_once($templatefile);
    }
  }

  // set new rows iterator and returns old one
  public function setRows($items,$flag = 0)
  {
    $rv = $this->rows;
    if($items instanceof RowsIterator || $items instanceof RowsGenerator)
    {
      $this->rows = $items;
    }
    else if($items instanceof Generator)
    {
      $this->rows = new RowsGenerator($items);
    }
    else if($items instanceof ArrayIterator)
    {
      $this->rows = new RowsIterator($items->getArrayCopy());
    }
    else if(is_array($items))
    {
      $this->rows = new RowsIterator($items,$flag);
    }
    else
    {
      $rv = false;
    }
    return $rv;
  }

  // rows iterator getter
  public function getRows()
  {
    return $this->rows;
  }

  // check this object has rows iterator and rows count is not 0
  public function haveRows()
  {
    $rv = false;
    if($this->rows instanceof RowsGenerator || ($this->rows instanceof RowsIterator && $this->rows->count() > 0))
      $rv = $this->rows;

    return $rv;
  }

}
