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
  protected function getInputTag($type,$name,$value,$attrs = array())
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
  protected function input($type,$name,$value,array $label = array(),array $attrs = array())
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
  protected function inputVal($type,$name,$propname,$label = array(),$attrs = array())
  {
    $this->input($type,$name,$this[$propname],$label,$attrs);
  }

  // adding class filter
  public function addClassFilter($filter_name,$classname)
  {
    add_filter($filter_name,function($class) use($classname) { return $class . (empty($class) ? '' : ' ') . $classname; });
  }
  // adding before filter
  public function addInputAction($filter_name,$str)
  {
    add_action($filter_name,function() use($str) { echo $str; });
  }

  public function hidden($name,$value,$attrs = array())
  {
    $this->input('hidden',$name,$value,array(),$attrs);
  }
  public function hiddens($nv,array $attrs = array())
  {
    foreach($nv as $name => $value)
      $this->hidden($name,$value,$attrs);
  }
  public function hiddenVal($name,$propname,$attrs = array())
  {
    $this->inputVal('hidden',$name,$propname,array(),$attrs);
  }
  public function hiddenVals(array $np,array $attrs = array())
  {
    foreach($np as $name => $propname)
      $this->hiddenVal($name,$propname,$attrs);
  }

  public function textbox($name,$value,$label = array(),array $attrs = array())
  {
    $classes = do_filter('text-class',array_key_exists('class',$attrs) ? $attrs['class'] : '');
    if(!empty($classes))
      $attrs['class'] = $classes;

    $this->input('text',$name,$value,$label,$attrs);
  }
  public function textboxVal($name,$propname,$label = array(),array $attrs = array())
  {
    $this->input('text',$name,$this[$propname],$label,$attrs);
  }

  public function textarea($name,$content,array $attrs = array())
  {
    $classes = do_filter('textarea-class',array_key_exists('class',$attrs) ? $attrs['class'] : '');
    if(!empty($classes))
      $attrs['class'] = $classes;

    if(!empty($name))
      $attrs['name'] = $name;

    $this->tag('textarea',$content,$attrs);
  }
  public function textareaVal($name,$propname,array $attrs = array())
  {
    $this->textarea($name,$this[$propname],$attrs);
  }

  public function checkbox($name,$value,$label = array(),$checked = false,$id = '')
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
  public function checkboxVal($name,$propname,$label = array(),$checked = false,$id = '')
  {
    $value = $this->get($propname);
    $this->checkbox($name,$value,$label,$checked,$id);
  }

  //$nv = array( name => array have keys of (value,checked,label,before,after), ....)
  public function checkboxes($name,$values)
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
  public function checkboxesVal($name,$values)
  {
    foreach($values as &$var)
      $var['value'] = $this->get($var['propname']);

    $this->checkboxes($name,$values);
  }

  public function radio($name,$value,$label = array(),$checked = false,$id = '')
  {
    if(empty($id))
      $id = preg_replace('/[\[\]]/','',$name) . '-' . $value;

    if(!is_array($label) && is_string($label))
      $label = array('content' => $label,'for' => $id);

    if(empty($label['for']))
      $label['for'] = $id;

    $classes = do_filter('radio-class','');
    $attrs = array();
    if(!empty($id))
      $attrs['id'] = $id;
    if($checked)
      $attrs['checked'] = 'checked';
    if(!empty($classes))
      $attrs['class'] = $classes;

    $this->input('radio',$name,$value,$label,$attrs);
  }
  public function radioVal($name,$propname,$label = array(),$checked = false,$attrs = array())
  {
    $this->radio($name,$this->get($propname),$label,$checked,$attrs);
  }

  //$nv = array( name => array have keys of (value,label,before,$after), ....)
  public function radioes($name,$values,$checked = '')
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
  public function radioesVal($name,$values,$checked = '')
  {
    foreach($values as &$var)
      $var['value'] = $this->get($var['propname']);

    $this->radioes($name,$values,$checked);
  }

  public function select($name,$options)
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
  public function option($value,$content,$selected = false,$disabled = false)
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
  protected function _button($buttonname,$name,$value,$attrs = array())
  {
    $classes = do_filter('button-class',array_key_exists('class',$attrs) ? $attrs['class'] : '');
    if(!empty($classes))
      $attrs['class'] = $classes;

    $this->input($buttonname,$name,$value,null,$attrs);
  }
  public function button($name,$value,$attrs = array())
  {
    $this->_button('button',$name,$value,$attrs);
  }
  public function buttonVal($name,$propname,$attrs = array())
  {
    $this->button($name,$this[$propname],$attrs);
  }
  public function submit($name = '',$value = '',$attrs = array())
  {
    $this->_button('submit',$name,$value,$attrs);
  }
  public function submitVal($name = '',$propname = '',$attrs = array())
  {
    $this->submit($name,$this[$propname],$attrs);
  }
  public function reset($name = '',$value = '' ,$attrs = array())
  {
    $this->_button('reset',$name,$value,$attrs);
  }
  public function resetVal($name = '',$propname = '' ,$attrs = array())
  {
    $this->reset($name,$this[$propname],$attrs);
  }

  /*------------------------------------------------------------------------------
  任意の範囲(range)の値のoption要素を出力する。

  $start : 開始(INT),
  $end   : 終了(INT),
  $delta : 刻み(INT)
  ------------------------------------------------------------------------------*/
  public function OptionRange($start,$end,$delta = 1,$selected_value = false)
  {
    $selected = array();

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
