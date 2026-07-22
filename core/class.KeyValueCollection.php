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
  public static function GetInstance(string $param1,array $options = []) : static
  {
    static $instances = array();
    $classname = get_called_class();

    if (!isset($instances[$classname]))
      $instances[$classname] = [];

    if (empty($param1))
      throw new Exception(_('GetInstance requires non-empty string'));

    if (!isset($instances[$classname][$param1]))
      $instances[$classname][$param1] =  new static($param1, $options);

    if ($classname !== 'Action')
      do_action("$classname::GetInstance", array($classname, $param1));

    return $instances[$classname][$param1];
  }

  /*------------------------------------------------------------------------------
    Instance members.
  ------------------------------------------------------------------------------*/

  //setter
  public function set(int|string|array $k,mixed $v = null,mixed $options = []) : mixed
  {
    if (!is_array($options))
      $options = array($options);

    return $this->_accessor($k, $v, array_merge(array('exec' => 'set'), $options));
  }

  //getter
  public function get(int|string $k, mixed $options = []) : mixed
  {
    if (!is_array($options))
      $options = array($options);

    if (empty($k))
    {
      $options['rv'] = 'iterator';
    }

    return $this->_accessor($k, null, array_merge(array('exec' => 'get'), $options));
  }

  //cleaner
  public function clear(mixed $options = [])
  {
    return $this->_accessor(null, null, array_merge(array('exec' => 'clear'), $options));
  }

  //deleter
  public function delete(int|string $k, mixed $options = []) : mixed
  {
    if (empty($k))
      return false;

    return $this->_accessor($k, null, array_merge(array('exec' => 'delete'), $options));
  }

  public function exists(int|string $k) : bool
  {
    if (empty($k))
      return false;

    $rv = $this->_accessor($k, null, array('exec' => 'exists'));
    return is_bool($rv) ? $rv : false;
  }

  public function isEmpty(int|string $k) : bool
  {
    $v = $this->get($k);
    return empty($v);
  }

  public function keys() : mixed
  {
    return $this->_accessor(null, null, array('exec' => 'keys'));
  }

  protected function _accessor(int|string|array|null $k,mixed $v,mixed $options = []) : mixed
  {
    if (!is_array($options))
    {
      if (empty($options))
        $options = [];
      else
        $options = [$options];
    }

    $rv = false;

    $method_name = 'kv_' . $options['exec'];
    if (method_exists($this, $method_name))
    {
      $rv = call_user_func(array($this, $method_name), $k, $v, $options);
    }

    return $rv;
  }

  // implements ArrayAccess
  public function offsetSet(mixed $offset,mixed $value) : void
  {
    $this->set($offset, $value);
  }

  public function offsetExists(mixed $offset) : bool
  {
    return $this->exists($offset);
  }

  public function offsetUnset(mixed $offset) : void
  {
    $this->delete($offset);
  }

  public function offsetGet(mixed $offset) : mixed
  {
    return $this->get($offset);
  }

  protected function id(?string $id = null) : ?string
  {
    $rv = $this->id;

    if ($id !== null)
      $this->id = $id;

    return $rv;
  }

  protected function &get_container() : mixed
  {
    return $this->kv;
  }

  protected function set_container(mixed &$container)
  {
    $rv = $this->kv;
    $this->kv = &$container;

    return $rv;
  }

  protected function init_container(mixed $container)
  {
    $rv = $this->kv;
    $this->kv = $container;

    return $rv;
  }

  /*------------------------------------------------------------------------------
    sample implementation for standard hash array accessor.
    should not be inherited
  ------------------------------------------------------------------------------*/

  private ?string $id = null;

 //container is a php standard hash array.
  private array $kv = [];

  protected function kv_exists(int|string $k, mixed $v, array $options) : bool
  {
    return array_key_exists($k, $this->kv);
  }

  protected function kv_keys(int|string|null $k, mixed $v, array $options) : array
  {
    return array_keys($this->kv);
  }

  protected function kv_set(int|string|array $k,mixed $v,array $options) : mixed
  {
    if (is_array($k))
    {
      if ($v == null)
        $this->kv = array_merge($this->kv, $k);
      else if (is_array($v))
        $this->kv = array_merge($this->kv, array_combine($k, $v));
      else
        throw new Exception(_('unexpected error in second arguments.'));
    }
    else
    {
      if (array_key_exists('multi', $options) && $options['multi'] === true)
      {
        if (array_key_exists($k, $this->kv))
        {
          if (!is_array($this->kv[$k]))
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
        if (array_key_exists($k, $this->kv))
          $old_value = $this->kv[$k];

        $this->kv[$k] = $v;
      }
    }

    return array_key_exists('rv', $options) && $options['rv'] === 'formerly' ? $old_value : $this;
  }

  protected function kv_get(int|string|array $k,mixed $v,array $options) : mixed
  {
    $rv = '';
    if (!$this->kv)
      return $rv;

    if (is_array($k))
    {
      $rv = array();
      foreach ($k as $k_)
      {
        if (array_key_exists($k_, $this->kv))
          $rv[$k_] = $this->kv[$k_];
      }
    }
    else
    {
      if (array_key_exists('rv', $options) && $options['rv'] === 'iterator')
      {
        $rv = new ArrayIterator($this->kv);
      }
      else
      {
        if (array_key_exists($k, $this->kv))
          $rv = $this->kv[$k];
      }
    }

    return $rv;
  }

  protected function kv_delete(int|string $k,mixed $v,array $options) : mixed
  {
    $rv = $this;
    if (array_key_exists($k, $this->kv))
    {
      if (array_key_exists('rv', $options) && $options['rv'] === 'formerly')
        $rv = $this->kv[$k];

      unset($this->kv[$k]);
      if (is_int($k) || (is_numeric($k) && preg_match('/^\d+$/', $k)))
        array_merge($this->kv);
    }

    return $rv;
  }

  protected function kv_clear(int|string|null $k,mixed $v,array $options) : mixed
  {
    $rv = $this;
    if (array_key_exists('rv', $options) && $options['rv'] === 'formerly')
      $rv = $this->kv;

    $this->kv = array();

    return $rv;
  }
}
