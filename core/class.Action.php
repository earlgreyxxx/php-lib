<?php
/*******************************************************************************

 ●アクションの登録、定義、実行を行うクラスです。

  All Written by K.,Nakagawa.

*******************************************************************************/
class Action extends KeyValueCollection
{
  private $id;

  //コンストラクタ
  public function __construct($actionID = '',$params = array())
    {
      $this->id(empty($actionID) ? str_uniqid() : $actionID);

      if(!empty($params))
        $this->set_container($params);
    }

  public function add($name,$callback)
    {
      if(is_callable($callback))
        $this->set($name,$callback,array('multi' => true));
    }

  public function adds(array $callbacks)
    {
      $rv = 0;
      foreach($callbacks as $name => $callback)
        {
          $this->add($name,$callback);
          $rv++;
        }

      return $rv;
    }

  public function fire($name,array $params = array())
    {
      $rv = null;
      $action = $this->get($name);

      if(!empty($action))
        {
          if(!is_array($action))
            $action = array($action);

          $results = array();
          $count = 1;
          foreach($action as $callback)
            {
              if(is_callable($callback,true))
                {
                  $callback_name = $callback;
                  if(is_array($callback))
                    {
                      $callback_name = sprintf('%s::%s',
                                               is_string($callback[0]) ? $callback[0] : spl_object_hash($callback[0]),
                                               $callback[1]);
                    }
                  else if(is_object($callback) && $callback instanceof Closure)
                    {
                      $callback_name = sprintf('closure_%02d',$count++);
                    }

                  $results[$callback_name] = call_user_func_array($callback,$params);
                }
            }

          if(!empty($results))
            $rv = $results;
        }

      return $rv;
    }
}


