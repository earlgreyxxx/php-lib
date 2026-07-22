<?php
/*******************************************************************************

  Derivered from TemplateBase class for sample web application

  filters: 
    input-class label-class text-class textarea-class checkbox-class radio-class select-class button-class

  public methods:
      addClassFilter($filter_name,$classname)
      addInputAction($filter_name,$str)

      hidden($name,$value,$attrs = array())
      hiddens($nv,array $attrs = array())
      hiddenVal($name,$propname,$attrs = array())
      hiddenVals(array $np,array $attrs = array())

      textbox($name,$value,$label = array(),array $attrs = array())
      textboxVal($name,$propname,$label = array(),array $attrs = array())
      textarea($name,$content,array $attrs = array())
      textareaVal($name,$propname,array $attrs = array())

      checkbox($name,$value,$label = array(),$checked = false,$id = '')
      checkboxVal($name,$propname,$label = array(),$checked = false,$id = '')
      checkboxes($name,$values)
      checkboxesVal($name,$values)

      radio($name,$value,$label = array(),$checked = false,$id = '')
      radioVal($name,$propname,$label = array(),$checked = false,$attrs = array())
      radioes($name,$values,$checked = '')
      radioesVal($name,$values,$checked = '')

      select($name,$options)
      option($value,$content,$selected = false)

      button($name,$value,$attrs = array())
      buttonVal($name,$propname,$attrs = array())

      submit($name = '',$value = '',$attrs = array())
      submitVal($name = '',$propname = '',$attrs = array())

      reset($name = '',$value = '' ,$attrs = array())
      resetVal($name = '',$propname = '' ,$attrs = array())

      OptionRange($start,$end,$delta = 1,$selected_value = false)

*******************************************************************************/

class FormTemplate extends PageTemplate
{
  protected function getInputTag(string $type,string $name,string|int|null $value,array $attrs = []) : string
  {
    $rv = '';
    $classes = do_filter('input-class',array_key_exists('class',$attrs) ? $attrs['class'] : '');
    if(!empty($classes))
      $attrs['class'] = $classes;

    $attrib = $this->buildAttributes($attrs);

    if(empty($type))
      $type = 'text';
    if(!empty($name))
      $name = sprintf(' name="%s"',$name);
    if(!empty($value) || is_int($value))
      $value = sprintf(' value="%s"',$value);

    return sprintf('<input type="%s"%s%s%s />',$type,$name,$value,$attrib);
  }

  // $label : array( before => bool,after => bool,content => string,for => string )
  protected function input(string $type,string $name,int|string|null $value,array $label = array(),array $attrs = []) : void
  {
    $before = '';
    $after = '';
    if(!empty($label))
    {
      $labelAttr = array();
      if(array_key_exists('for',$label))
        $labelAttr['for'] = $label['for'];

      $classes = do_filter('label-class','');
      if(!empty($classes))
        $labelAttr['class'] = $classes;

      $before = $this->getTag('label',$label['content'],$labelAttr);
      $after = '';
      if(array_key_exists('after',$label) && $label['after'] === true)
      {
        $after = $before;
        $before = '';
      }
    }
    do_action('input-before');
    $output = $this->getInputTag($type,$name,$value,$attrs);
    if(!empty($output))
      echo $before,$output,$after,PHP_EOL;
    do_action('input-after');
  }
  protected function inputVal(string $type,string $name,string $propname,array $label = [],array $attrs = []) : void
  {
    $this->input($type,$name,$this[$propname],$label,$attrs);
  }

  // adding class filter
  public function addClassFilter(string $filter_name,string $classname) : void
  {
    add_filter($filter_name,function($class) use($classname) { return $class . (empty($class) ? '' : ' ') . $classname; });
  }
  // adding before filter
  public function addInputAction(string $filter_name,string $str) : void
  {
    add_action($filter_name,function() use($str) { echo $str; });
  }

  public function hidden(string $name,int|string $value,array $attrs = []) : void
  {
    $this->input('hidden',$name,$value,array(),$attrs);
  }
  public function hiddens(array $nv,array $attrs = []) : void
  {
    foreach($nv as $name => $value)
      $this->hidden($name,$value,$attrs);
  }
  public function hiddenVal(string $name,string $propname,$attrs = []) : void
  {
    $this->inputVal('hidden',$name,$propname,array(),$attrs);
  }
  public function hiddenVals(array $np,array $attrs = []) : void
  {
    foreach($np as $name => $propname)
      $this->hiddenVal($name,$propname,$attrs);
  }

  public function textbox(string $name,int|string $value,array $label = [],array $attrs = []) : void
  {
    $classes = do_filter('text-class',array_key_exists('class',$attrs) ? $attrs['class'] : '');
    if(!empty($classes))
      $attrs['class'] = $classes;

    $this->input('text',$name,$value,$label,$attrs);
  }
  public function textboxVal(string $name,string $propname,array $label = [],array $attrs = []) : void
  {
    $this->input('text',$name,$this[$propname],$label,$attrs);
  }

  public function textarea(string $name,string $content,array $attrs = []) : void
  {
    $classes = do_filter('textarea-class',array_key_exists('class',$attrs) ? $attrs['class'] : '');
    if(!empty($classes))
      $attrs['class'] = $classes;

    if(!empty($name))
      $attrs['name'] = $name;

    $this->tag('textarea',$content,$attrs);
  }
  public function textareaVal(string $name,string $propname,array $attrs = []) : void
  {
    $this->textarea($name,$this[$propname],$attrs);
  }

  public function checkbox(string $name,int|string $value,array $label = [],bool $checked = false,string $id = '') : void
  {
    if(empty($id))
      $id = preg_replace('/[\[\]]/','',$name) . '-' . $value;

    if(!is_array($label) && is_string($label))
      $label = array('content' => $label,'for' => $id);

    if(empty($label['for']))
      $label['for'] = $id;

    $classes = do_filter('checkbox-class','');
    $attrs = array();
    if(!empty($id))
      $attrs['id'] = $id;
    if($checked)
      $attrs['checked'] = 'checked';
    if(!empty($classes))
      $attrs['class'] = $classes;

    $this->input('checkbox',$name,$value,$label,$attrs);
  }
  public function checkboxVal(string $name,string $propname,array $label = [],bool $checked = false,string $id = '') : void
  {
    $value = $this->get($propname);
    $this->checkbox($name,$value,$label,$checked,$id);
  }

  //$nv = array( name => array have keys of (value,checked,label,before,after), ....)
  public function checkboxes(string $name,array $values) : void
  {
    foreach($values as $var)
    {
      if(!empty($var['before']))
        echo $var['before'];
      $this->checkbox($name,$var['value'],$var['label'],$var['checked']);
      if(!empty($var['after']))
        echo $var['after'];
    }
  }

  //$nv = array( name => array have keys of (propname,checked,label,before,after), ....)
  public function checkboxesVal(string $name,array $values) : void
  {
    foreach($values as &$var)
      $var['value'] = $this->get($var['propname']);

    $this->checkboxes($name,$values);
  }

  public function radio(string $name,int|string $value,array $label = [],bool $checked = false,string $id = '') : void
  {
    if(empty($id))
      $id = preg_replace('/[\[\]]/','',$name) . '-' . $value;

    if(!is_array($label) && is_string($label))
      $label = array('content' => $label,'for' => $id);

    if(empty($label['for']))
      $label['for'] = $id;

    $classes = do_filter('radio-class','');
    $attrs = [];
    if(!empty($id))
      $attrs['id'] = $id;
    if($checked)
      $attrs['checked'] = 'checked';
    if(!empty($classes))
      $attrs['class'] = $classes;

    $this->input('radio',$name,$value,$label,$attrs);
  }
  public function radioVal(string $name,string $propname,array $label = [],bool $checked = false,string $id = '') : void
  {
    $this->radio($name,$this->get($propname),$label,$checked,$id);
  }

  //$nv = array( name => array have keys of (value,label,before,$after), ....)
  public function radioes(string $name,array $values,string $checked = '') : void
  {
    foreach($values as $var)
    {
      if(!empty($var['before']))
        echo $var['before'];
      $this->radio($name,$var['value'],$var['label'],$var['value'] === $checked);
      if(!empty($var['after']))
        echo $var['after'];
    }
  }
  //$nv = array( name => array have keys of (propname,label,before,$after), ....)
  public function radioesVal(string $name,array $values,string $checked = '') : void
  {
    foreach($values as &$var)
      $var['value'] = $this->get($var['propname']);

    $this->radioes($name,$values,$checked);
  }

  public function select(string $name,array $options) : void
  {
    $attrs = array();
    if(!empty($name))
      $attrs['name'] = $name;
    $classes = do_filter('select-class','');
    if(!empty($classes))
      $attrs['class'] = $classes;

    echo $this->getStartTag('select',$attrs),PHP_EOL;
    foreach($options as $option)
      $this->option($option['value'],$option['content'],$option['selected']);
    echo $this->getEndTag('select'),PHP_EOL;
  }
  public function option(int|string $value,string $content,bool $selected = false,bool $disabled = false) : void
  {
    $attrs = array();
    if(!empty($value) || (is_int($value) && $value === 0) || (is_string($value) && preg_match('/^0+$/',$value)))
      $attrs['value'] = $value;
    if($selected === true)
      $attrs['selected'] = 'selected';
    if($disabled === true)
      $attrs['disabled'] = 'disabled';

    echo "\t";
    $this->tag('option',$content,$attrs);
  }
  protected function _button(string $buttonname,string $name,int|string $value,array $attrs = []) : void
  {
    $classes = do_filter('button-class',array_key_exists('class',$attrs) ? $attrs['class'] : '');
    if(!empty($classes))
      $attrs['class'] = $classes;

    $this->input($buttonname,$name,$value,[],$attrs);
  }
  public function button(string $name,int|string $value,array $attrs = []) : void
  {
    $this->_button('button',$name,$value,$attrs);
  }
  public function buttonVal(string $name,string $propname,array $attrs = []) : void
  {
    $this->button($name,$this[$propname],$attrs);
  }
  public function submit(string $name = '',int|string $value = '',array $attrs = []) : void
  {
    $this->_button('submit',$name,$value,$attrs);
  }
  public function submitVal(string $name = '',string $propname = '',array $attrs = []) : void
  {
    $this->submit($name,$this[$propname],$attrs);
  }
  public function reset(string $name = '',int|string $value = '' ,array $attrs = []) : void
  {
    $this->_button('reset',$name,$value,$attrs);
  }
  public function resetVal(string $name = '',string $propname = '' ,array $attrs = []) : void
  {
    $this->reset($name,$this[$propname],$attrs);
  }

  /*------------------------------------------------------------------------------
  任意の範囲(range)の値のoption要素を出力する。

  $start : 開始(INT),
  $end   : 終了(INT),
  $delta : 刻み(INT)
  ------------------------------------------------------------------------------*/
  public function OptionRange(int $start,int $end,int $delta = 1,mixed $selected_value = false) : void
  {
    $selected = [];

    if($selected_value !== false &&  $selected_value !== null)
      $selected[$selected_value] = ' selected';

    if($delta > 0)
    {
      for($i=$start;$i<=$end;$i+=$delta)
        echo "<option value=\"$i\"{$selected[$i]}>$i</option>\n";
    }
    else
    {
      for($i=$start;$i>=$end;$i+=$delta)
        echo "<option value=\"$i\"{$selected[$i]}>$i</option>\n";
    }
  }
}
