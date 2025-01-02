<?php //-*- mode: php; Encoding: utf8n -*-
/*******************************************************************************

 Router class

  All Written by K.,Nakagawa.

  usage: 
   $route = new Route('/your/app/root/path',array('appdir' => __DIR__));
   $route->add($access_path,$action,$adding_request_parameters);
   .
   .
   .
   $route->invoke(); // or invoke($request_path)

   $action is specified one of bellow ...
     1) string ( @controller_classname::methodname )
     2) array ( [callable,params]  )
     3) instanceof Dispatch
     4) callable
     5) string (include path)

  ex)
  // for non parameter
  $route->add('/path',$action);
    ... route is '/path'.
  
  // for simple parameter
  $route->add('/path/{keyname}',$action);
    ... route is '@/path/keyname'

  // for customized parameter
  $route->add('/path/{keyname@ID(\d{8})::ID%08d',$action);
    ... route is '@/path/keyname'

  // get router path
  $route->getPath('@/path/keyname',array(12345678),$suffix);
    returns '/path/ID12345678'

  $route->getFullPath('/path/keyname',array(12345678),$suffix);
    returns full path that begins https?://

*******************************************************************************/

class Route extends KeyValueCollection
{
  protected static $ROUTE_VALID_PATTERN = '![^\w_\-\./]+!';
  private static function merge_requests(?array $input = null)
  {
    if($input !== null)
    {
      foreach(array('r','f','p','g') as $k)
      {
        if(array_key_exists($k,$input) && is_array($input[$k]))
        {
          switch($k)
          {
          case 'r':
            $_REQUEST = array_merge_unless_exists($_REQUEST,$input[$k]);
            break;
          case 'f':
            $_FILES = array_merge_unless_exists($_FILES,$input[$k]);
            break;
          case 'p':
            $_POST = array_merge_unless_exists($_POST,$input[$k]);
            break;
          case 'g':
            $_GET = array_merge_unless_exists($_GET,$input[$k]);
            break;
          }
        }
      }
    }
  }

  public static function GetInstance($param1,$options = array())
  {
    if(empty($param1))
      $param1 = '/';

    return parent::GetInstance($param1,$options);
  }

  /*------------------------------------------------------------------------------
    Instance members.
  ------------------------------------------------------------------------------*/
  private $appdir = '';
  private $rewrite;
  private $prefix;
  private $current;
  private $cache;
  private $format;
  private $regex;
  private $option;
  private $clear = false;
  private $keyname;

  protected function export()
  {
    return [ 
      'path'   => var_export($this->get_container(),true),
      'regex'  => var_export($this->regex,true),
      'format' => var_export($this->format,true),
      'option' => var_export($this->option,true)
    ];
  }

  protected function import($pathes,$regexes,$formats,$options)
  {
    $this->init_container($pathes);
    $this->regex = $regexes;
    $this->format = $formats;
    $this->option = $options;
  }

  protected function cacheApply()
  {
    if($this->cache && apcu_exists($this->cache))
      call_user_func_array([$this,'import'],apcu_fetch($this->cache,$result));
  }

  protected function cacheStore()
  {
    if(false !== $this->cache)
    {
      $expires = [];
      $path = $this->get_container();
      $regex = $this->regex;
      $format = $this->format;
      $option = $this->option;

      $path = array_filter($path,function($v,$k) use(&$expires) {
        $rv = true;
        if(is_object($v) && ($v instanceof Closure))
        {
          $expires[] = $k;
          $rv = false;
        }
        return $rv;
      },ARRAY_FILTER_USE_BOTH);

      foreach($expires as $v)
        unset($regex[$v],$format[$v],$option[$v]);

      return apcu_store($this->cache,[$path, $regex, $format, $option]);
    }

    return false;
  }

  private function invoke_controller($classname,$param1,$param2)
  {
    $classname::Invoke($param1,$param2);
  }

  private function invoke_dispatcher($dispatch)
  {
    if(count($dispatch) > 1)
    {
      list($callable,$dispatch_params) = $dispatch;
    }
    else
    {
      $callable = $dispatch[0];
      $dispatch_params = [];
    }
    
    if(!is_array($dispatch_params))
      $dispatch_params = [];

    call_user_func_array($callable,$dispatch_params);
  }
  private function invoke_file($disp)
  {
    $prefix = '';
    if($disp[0] !== DIRECTORY_SEPARATOR)
      $prefix = $this->appdir . '/';

    include_once($prefix . $disp);
  }

  protected function match($uri)
  {
    $prefix = $this->prefix;
    if(!empty($prefix))
    {
      if(0 === strpos($uri,$prefix))
        $uri = substr($uri,strlen($prefix));
    }

    if(!preg_match('!^(/[^?]*)!',$uri,$m))
      $m = false;

    return $m;
  }

  protected function invoker($key)
  {
    $err = '';
    if($key !== '/')
      $key = chop($key,'/');

    $r = &get_request();
    foreach($this->keys() as $n)
    {
      $rv = false;
      $regstr = $n;
      if($n[0] === '@')
        $regstr = $this->getPattern($n);

      $regstr = sprintf('/^%s$/',str_replace("/","\\/",$regstr));
      if(preg_match($regstr,$key,$m))
      {
        $this->current = $n;
        $params = array();
        if(count($m) > 1)
          $params = array_slice($m,1);

        $option = $this->getOption($n);
        $disp = parent::get($n);

        // when parameters was given by Route object,
        //   store this to $_REQUEST with key 'route_params' array.
        $r['route_params'] = $params;

        // xxxx::yyyy means use instance of WebControllerBase
        //   otherwise instance of WebApplicationStub
        if(is_string($disp) && preg_match('/^@(\w+)\:\:(\w+)$/',$disp,$md))
        {
          if(is_array($option))
            self::merge_requests($option);

          $this->invoke_controller($md[1],$md[2],$params);
        }
        else if(is_array($disp) && is_callable($disp[0]))
        {
          if(is_array($option))
            self::merge_requests($option);

          $this->invoke_dispatcher($disp);
        }
        else if(is_object($disp) && ($disp instanceof Dispatch))
        {
          if(is_array($option))
            self::merge_requests($option);

          $disp->invoke();
        }
        else if(is_callable($disp))
        {
          call_user_func_array($disp,$params);
        }
        else if(is_string($disp))
        {
          $this->invoke_file($disp);
        }
        else
        {
          $err = _('router error: can not invoke type');
          break;
        }
        return true;
      }
    }


    if(php_sapi_name() === 'cli')
    {
      if(empty($err))
        $err = _('can not resolve route path.');

      throw new Exception($err);
    }

    if(empty($err))
      $err = sprintf(_('You don\'t have permission to access %s on this server.'),$key);

    $view = ViewBase::CreateInstance();
    $view->getTemplate()->set('server_error',$err);
    $view->error404();  // call exit in method error404

    // no controll comes here...
  }

  // Constructor
  public function __construct($prefix,array $options = array())
  {
    static $default = array('rewritable' => true,'keyname' => 'energize');

    $options = array_merge($default,$options);
    $this->prefix = rtrim($prefix,'/');
    $this->rewrite = $options['rewritable'];
    $this->appdir =  array_key_exists('appdir',$options) ? rtrim($options['appdir'],'/') : '';
    $this->cache = isset($options['cache']) ? $options['cache'] : false;
    $this->keyname = $options['keyname'];

    $this->format = array();
    $this->regex = array();
    if($this->cache && function_exists('apcu_exists'))
      $this->cacheApply();
  }

  public function __destruct()
  {
    if($this->cache !== false && $this->clear)
      apcu_delete($this->cache);
    else if($this->cache !== false && !$this->hasCached())
      $this->cacheStore();
  }

  public function clearCache()
  {
    if($this->cache !== false && $this->hasCached())
      $this->clear = true;
  }

  public function hasCached()
  {
    return !empty($this->cache) && apcu_exists($this->cache);
  }

  public function rewritable($b = null)
  {
    if($b === null)
    {
      $rv = $this->rewrite;
    }
    else if(is_bool($b))
    {
      $this->rewrite = $b;
      $rv =  $this;
    }

    return $rv;
  }

  public function setPrefix($prefix)
  {
    $this->prefix = rtrim($prefix,'/');
  }

  public function getDispatcher($route)
  {
    $rv = false;
    if($this->exists($route))
      $rv = parent::get($route);

    return $rv;
  }

  protected function setPattern($route,$pattern)
  {
    $this->regex[$route] = $pattern;
  }
  public function getPattern($route)
  {
    $rv = false;
    if($route[0] !== '@')
      $route = '@' . $route;

    if(isset($this->regex[$route]))
      $rv = $this->regex[$route];

    return $rv;
  }
  protected function setFormat($route,$format)
  {
    $this->format[$route] = $format;
  }
  public function getFormat($route)
  {
    $rv = false;
    if($route[0] !== '@')
      $route = '@' . $route;

    if(isset($this->format[$route]))
      $rv = $this->format[$route];

    return $rv;
  }
  public function setOption($route,$option)
  {
    $this->option[$route] = $option;
  }
  public function getOption($route)
  {
    if(array_key_exists($route,$this->option))
      return $this->option[$route];

    return null;
  }

  public function current()
  {
    return $this->current;
  }

  public function adds(array $params)
  {
    foreach($params as $param)
    {
      if(!is_array($param))
        continue;

      switch(count($param))
      {
      case 3:
        list($path,$dispatcher,$option) = $param;
        break;
      case 2:
        list($path,$dispatcher) = $param;
        $option = null;
        break;
      default:
        throw new RuntimeException(_('add route error has occured'));
      }
      if(false === $this->add($path,$dispatcher,$option))
        throw new RuntimeException(_('add route error has occured'));
    }

    return $this;
  }

  //add routing
  public function add($path,$dispatcher,?array $option = null)
  {
    if($this->exists($path))
      return false;

    if(preg_match('!/\{.+\}/?!',$path))
    {
      $regex = array();
      $fmt = array();
      foreach(explode('/',$path) as $el)
      {
        if(!empty($el) && preg_match('/^\{(\w+)(?:@(.+?)\:\:(.+?))?\}$/',$el,$m))
        {
          $keys[] = $m[1];
          if(count($m) > 2)
          {
            $regex[] = $m[2];
            $fmt[] =  $m[3];
          }
          else
          {
            $regex[] = '(\w+)';
            $fmt[] = '%s';
          }            
          $m = null;
        }
        else
        {
          $keys[] = $el;
          $regex[] = $el;
          $fmt[] = $el;
        }
      }
      $ka = implode('/',$keys);
      $re = implode('/',$regex);
      $fm = implode('/',$fmt);

      $rv = $this->addre($ka,$re,$fm,$dispatcher,$option);
      $this->setOption('@' . $ka,$option);
    }
    else
    {
      $rv = $this->addstr($path,$dispatcher,$option);
      $this->setOption($path,$option);
    }

    return $rv;
  }
  protected function addstr($path,$dispatcher,$option = null)
  {
    if($this->exists($path))
      return false;

    return parent::set($path,$dispatcher);
  }
  protected function addre($key,$path_re,$path_format,$dispatcher)
  {
    $key = '@' . $key;
    if($this->exists($key))
      return false;

    $this->setPattern($key,$path_re);
    $this->setFormat($key,$path_format);
    return parent::set($key,$dispatcher);
  }

  public function run($keyname)
  {
    $keyname = preg_replace(static::$ROUTE_VALID_PATTERN,'',$keyname ?? '');
    if(!empty($keyname))
      return $this->invoker($keyname);

    if(empty($keyname))
      return $this->invoker('/');

    return false;
  }

  //read front end php file or invoke function...
  public function invoke($request_uri = '')
  {
    if($this->rewrite)
    {
      $request_path = preg_replace(static::$ROUTE_VALID_PATTERN,'',get_request_path($request_uri));
      if(false !== ($match = $this->match($request_path)))
        return $this->run($match[1]);
    }
    else
    {
      $r = get_request();
      if(true === $this->run($r[$this->keyname] ?? ''))
        return;
    }

    $redirect_url = get_base_url() . '/';
    if($_SERVER['QUERY_STRING'])
    {
      $g = &get_get();
      unset($g[$this->keyname]);
      if(!empty($g))
        $redirect_url = $redirect_url . '?' . http_build_query($g);
    }
    header('Location: ' . $redirect_url);
  }

  //set new dispatcher and returns old dispatcher.
  public function set($route,$dispatcher = null,$options = [])
  {
    if($dispatcher === null || !is_callable($dispatcher) || !$this->exists($route))
      return false;

    return parent::set($route,$dispatcher,$options);
  }

  public function getre($route,$params = array(),$suffix = false)
  {
    throw new Exception(_('Can not call this: ').__METHOD__);
  }
  public function get($k,$options = null)
  {
    throw new Exception(_('Can not call this: ').__METHOD__);
  }

  public function getFullPath($route = null,?array $params = null,$suffix = false)
  {
    $query_string = '';
    if(is_string($suffix))
    {
      $query_string = $suffix;
      $suffix = true;
    }
    return $this->prefix . $this->getPath($route,$params,$suffix) . $query_string;
  }

  public function getPath($route = null,?array $params = null,$suffix = false)
  {
    if(empty($route))
      $route = $this->current();

    if($params === null)
      $params = array();

    $rv = '/';
    if($this->exists($route))
    {
      $rv = $route;
      if($route[0] === '@')
      {
        if(empty($params))
          throw new RuntimeException(_('if route with arguments,parameters can not be empty'));

        $rv = vsprintf($this->getFormat($route),$params);
        if(empty($rv))
          return '';
      }
    }
    return $this->getPathImp($rv,$suffix);
  }

  // getPath implementation
  private function getPathImp($route,$suffix = false)
  {
    $rv = $route;
    if(!$this->rewrite)
    {
      $marker = '&';
      if(!empty($rv) && $rv !== '/')
        $rv = sprintf('/?%s=%s',$this->keyname,$rv);

      if($rv === '/')
        $marker = '?';

      if($suffix)
        $rv .= $marker;
    }
    else
    {
      /* if(substr($route,-1) !== '/') */
      /*   $rv .= '/'; */

      if($suffix)
        $rv .= '?';
    }

    return $rv;
  }

  public function getKeyname()
  {
    return $this->keyname;
  }
}
