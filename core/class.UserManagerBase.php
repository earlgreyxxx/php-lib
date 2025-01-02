<?php
/*******************************************************************************
 *
 * ユーザー管理 基本クラス
 *
*******************************************************************************/
abstract class UserManagerBase extends WebApplicationStub
{
  // statics
  protected static function GetQueryConditionFormat($query)
  {
    $rv = false;
    if(is_int($query))
      $rv = '%s = %d';
    else if(is_string($query) && preg_match('/[a-zA-Z0-9]+/',$query))
      $rv = '%s = %s'; 
  }

  // instances
  protected $response = null;
  protected static $app = null;

  protected function init()
  {
    parent::init();
    $this->getView()->setTitle(_('User management'));
    $this->response = $this->getView()->getResponse();
  }

  // show regist page
  protected function do_regist()
  {
    $this->getView()->setContent(array('user','regist'));
    return true;
  }

  // run regist user process
  protected function do_regist_apply()
  {
    //to do regist user
    $continue = $this->r['continue'] === 1;
    $url = get_route_url('',null,$continue);
    if($continue)
      $url .= 'mode=regist';

    $this->response->redirect($url);
    return false;
  }

  protected function do_modify($query)
  {
    $this->getView()->setContent(array('user','modify'));
    return true;
  }
  protected function do_modify_apply()
  {
    $this->response->redirect($url);
    return false;
  }

  protected function do_remove_confirm()
  {
  }
  protected function do_remove_apply()
  {
  }

  protected function do_find()
  {

  }

  protected function do_valid()
  {
    $user = $this->r['user'];
    $digest = $this->r['digest'];
  }

  // process User property
  protected function do_aquire()
  {
    list($userid) = $this->r['route_params'];

  }
}
