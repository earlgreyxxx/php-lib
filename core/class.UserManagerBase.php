<?php
/*******************************************************************************
 *
 * ユーザー管理 基本クラス
 *
*******************************************************************************/
abstract class UserManagerBase extends WebApplicationStub
{
  // statics
  protected static function GetQueryConditionFormat(int|string $query) : string|false
  {
    $rv = false;
    if(is_int($query))
      $rv = '%s = %d';
    else if(is_string($query) && preg_match('/[a-zA-Z0-9]+/',$query))
      $rv = '%s = %s'; 

    return $rv;
  }

  // instances
  protected ?Response $response = null;
  protected static mixed $app = null;

  protected function init() : void
  {
    parent::init();
    $this->getView()->setTitle(_('User management'));
    $this->response = $this->getView()->getResponse();
  }

  // show regist page
  protected function do_regist() : bool
  {
    $this->getView()->setContent(array('user','regist'));
    return true;
  }

  // run regist user process
  protected function do_regist_apply() : bool
  {
    //to do regist user
    $continue = $this->r['continue'] === 1;
    $url = get_route_url('',null,$continue);
    if($continue)
      $url .= 'mode=regist';

    $this->response->redirect($url);
    return false;
  }

  protected function do_modify(string $query) : bool
  {
    $this->getView()->setContent(['user','modify']);
    return true;
  }
  protected function do_modify_apply() : bool
  {
    $url = get_route_url('');
    $this->response->redirect($url);
    return false;
  }

  protected function do_remove_confirm() : bool
  {
    return false;
  }
  protected function do_remove_apply() : bool
  {
    return false;
  }

  protected function do_find() : bool
  {
    return false;
  }

  protected function do_valid() : bool
  {
    $user = $this->r['user'];
    $digest = $this->r['digest'];

    return false;
  }

  // process User property
  protected function do_aquire() : bool
  {
    list($userid) = $this->r['route_params'];
    return false;
  }
}
