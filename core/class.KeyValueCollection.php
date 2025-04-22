<?php
/*******************************************************************************

  define KV data(Key-Value Hash) accessor class.(abstract)

    All Written by K.,Nakagawa.

*******************************************************************************/
abstract class KeyValueCollection implements ArrayAccess
{
  /*------------------------------------------------------------------------------
    [Static] manage for singleton instance by key

    arguments : [1] => string(required),  [2] => array(option)
  ------------------------------------------------------------------------------*/
  public static function GetInstance($param1,$options = array())
    {
      static $instances = array();
      $classname = get_called_class();

      if(!isset($instances[$classname]))
        $instances[$classname] = array();

      if(empty($param1) || !is_string($param1))
        throw new Exception(_('GetInstance requires non-empty string'));

      if(!isset($instances[$classname][$param1]))
        $instances[$classname][$param1] =  new static($param1,$options);

      if($classname !== 'Action')
        do_action("$classname::GetInstance",array($classname,$param1));

      return $instances[$classname][$param1];
    }

  /*------------------------------------------------------------------------------
    Instance members.
  ------------------------------------------------------------------------------*/

  //setter
  public function set($k,$v = null,$options = [])
    {
      if(!is_array($options))
        $options = array($options);

      return $this->_accessor($k,$v,array_merge(array('exec' => 'set'),$options));
    }

  //getter
  public function get($k,$options = array())
    {
      if(!is_array($options))
        $options = array($options);

      if(empty($k))
        {
          $options['rv'] = 'iterator';
        }

      return $this->_accessor($k,null,array_merge(array('exec' => 'get'),$options));
    }

  //cleaner
  public function clear($options = array())
    {
      return $this->_accessor(null,null,array_merge(array('exec' => 'clear'),$options));
    }

  //deleter
  public function delete($k,$options = array())
    {
      if(empty($k))
        return false;

      return $this->_accessor($k,null,array_merge(array('exec' => 'delete'),$options));
    }

  public function exists($k)
    {
      if(empty($k))
        return false;

      return $this->_accessor($k,null,array('exec' => 'exists'));
    }

  public function isEmpty($k)
    {
      $v = $this->get($k);
      return empty($v);
    }

  public function keys()
    {
      return $this->_accessor(null,null,array('exec' => 'keys'));
    }


  protected function _accessor($k,$v,$options = array())
    {
      if(!is_array($options))
        {
          if(empty($options))
            $options = array();
          else
            $options = array($options);
        }

      $rv = false;

      $method_name = 'kv_' . $options['exec'];
      if(method_exists($this,$method_name))
        {
          $rv = call_user_func(array($this,$method_name),$k,$v,$options);
        }

      return $rv;
    }

  // implements ArrayAccess
  #[\ReturnTypeWillChange]
  public function offsetSet($offset,$value)
    {
      $this->set($offset,$value);
    }

  #[\ReturnTypeWillChange]
  public function offsetExists($offset)
    {
      return $this->exists($offset);
    }

  #[\ReturnTypeWillChange]
  public function offsetUnset($offset)
    {
      $this->delete($offset);
    }

  #[\ReturnTypeWillChange]
  public function offsetGet($offset)
    {
      return $this->get($offset);
    }

  protected function id($id = null)
    {
      $rv = $this->id;

      if($id !== null)
        $this->id = $id;

      return $rv;
    }

  protected function &get_container()
    {
      return $this->kv;
    }

  protected function set_container(&$container)
    {
      $rv = $this->kv;
      $this->kv = &$container;

      return $rv;
    }

  protected function init_container($container)
    {
      $rv = $this->kv;
      $this->kv = $container;

      return $rv;
    }

  /*------------------------------------------------------------------------------
    sample implementation for standard hash array accessor.
    should not be inherited
  ------------------------------------------------------------------------------*/

  private $id;

 //container is a php standard hash array.
  private $kv = array();

  protected function kv_exists($k,$v,array $options)
    {
      return array_key_exists($k,$this->kv);
    }

  protected function kv_keys($k,$v,array $options)
    {
      return array_keys($this->kv);
    }

  protected function kv_set($k,$v,array $options)
    {
      if(is_array($k))
        {
          if($v == null)
            $this->kv = array_merge($this->kv,$k);
          else if(is_array($v))
            $this->kv = array_merge($this->kv,array_combine($k,$v));
          else
            throw new Exception(_('unexpected error in second arguments.'));
        }
      else
        {
          if(array_key_exists('multi',$options) && $options['multi'] === true)
            {
              if(array_key_exists($k,$this->kv))
                {
                  if(!is_array($this->kv[$k]))
                    $this->kv[$k] = array($this->kv[$k]);

                  $this->kv[$k][] = $v;
                }
              else
                {
                  $this->kv[$k] = array($v);
                }
            }
          else
            {
              if(array_key_exists($k,$this->kv))
                $old_value = $this->kv[$k];

              $this->kv[$k] = $v;
            }
        }

      return array_key_exists('rv',$options) && $options['rv'] === 'formerly' ? $old_value : $this;
    }

  protected function kv_get($k,$v,array $options)
    {
      $rv = '';
      if(!$this->kv)
        return $rv;

      if(is_array($k))
        {
          $rv = array();
          foreach($k as $k_)
            {
              if(array_key_exists($k_,$this->kv))
                $rv[$k_] = $this->kv[$k_];
            }
        }
      else
        {
          if(array_key_exists('rv',$options) && $options['rv'] === 'iterator')
            {
              $rv = new ArrayIterator($this->kv);
            }
          else
            {
              if(array_key_exists($k,$this->kv))
                $rv = $this->kv[$k];
            }
        }

      return $rv;
    }

  protected function kv_delete($k,$v,array $options)
    {
      $rv = $this;
      if(array_key_exists($k,$this->kv))
        {
          if(array_key_exists('rv',$options) && $options['rv'] === 'formerly')
            $rv = $this->kv[$k];

          unset($this->kv[$k]);
          if(is_int($k) || (is_numeric($k) && preg_match('/^\d+$/',$k)))
            array_merge($this->kv);
        }

      return $rv;
    }

  protected function kv_clear($k,$v,array $options)
    {
      $rv = $this;
      if(array_key_exists('rv',$options) && $options['rv'] === 'formerly')
        $rv = $this->kv;

      $this->kv = array();

      return $rv;
    }
}
