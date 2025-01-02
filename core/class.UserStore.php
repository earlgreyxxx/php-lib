<?php
/********************************************************************************

  ユーザー管理テーブルのアクセサ・クラス

  $store = new UserStore($dsn,$table,$dbuser,$dbpasswd);

*******************************************************************************/

class UserStore extends UniversalStore
{
  /*--------------------------------------------------------------------------
    Static members.
   --------------------------------------------------------------------------*/

  //テーブルを用意する。
  public static function Prepare($pdoex,$table)
    {
      $columns = array('user_id INTEGER PRIMARY KEY %AUTOINCREMENT%',
                       'user_name VARCHAR(32) UNIQUE NOT NULL',
                       'user_email VARCHAR(255) UNIQUE',
                       'user_phone VARCHAR(16) UNIQUE',
                       'user_zipcode VARCHAR(8)',
                       'user_address VARCHAR(128)',
                       'user_birth DATE',
                       'user_lastupdate DATETIME',
                       'user_regist_time DATETIME');

      return static::CreateTable($pdoex,$table,$columns);
    }

  protected static $Placeholders = array('user_id'          => array('?',PDO::PARAM_INT),
                                         'user_name'        => array('?',PDO::PARAM_STR),
                                         'user_email'       => array('?',PDO::PARAM_STR),
                                         'user_phone'       => array('?',PDO::PARAM_STR),
                                         'user_zipcode'     => array('?',PDO::PARAM_STR),
                                         'user_address'     => array('?',PDO::PARAM_STR),
                                         'user_birth'       => array('?',PDO::PARAM_STR),
                                         'user_lastupdate'  => array('?',PDO::PARAM_STR),
                                         'user_regist_time' => array('?',PDO::PARAM_STR));


  /*--------------------------------------------------------------------------
     Instance members.
  --------------------------------------------------------------------------*/

  public function __construct($dsn,$table_name,$dbuser = '',$dbpasswd = '',$options = array())
    {
      //基本クラスのコンストラクタをコール
      parent::__construct($dsn,$table_name,$dbuser,$dbpasswd,$options);

      //メタキーの定義
      $meta_keys = array('description','gender','digest');

      if(count($meta_keys) > 0)
        $this->meta_keys = array_merge($this->meta_keys,$meta_keys);
    }

  protected function form_to_post($form = null)
    {
      if($form == null)
        $form = &get_post();

      $post = array();

      $post['user_name']        = str_sanitize($form['name']);
      $post['user_email']       = str_sanitize($form['email']);
      $post['user_phone']       = str_sanitize($form['phone']);
      $post['user_zipcode']     = str_sanitize($form['zipcode']);
      $post['user_address']     = str_sanitize($form['address']);
      $post['user_regist_time'] = Now();
      $post['user_lastupdate']  = Now();
      $post['user_birth']       = sprintf('%04d-%02d-%02d',intval($form['by']),intval($form['bm']),intval($form['bd'])); 

      //ここからはメタデータ
      foreach($this->meta_keys as $key)
        {
          if(isset($form[$key]))
            {
              if(is_array($form[$key]))
                {
                  foreach($form[$key] as &$val)
                    $val = str_sanitize($val);

                  $post[$key] = $form[$key];
                }
              else
                {
                  $post[$key] = str_sanitize($form[$key]);
                }
            }

          if(method_exists($this,'meta_form_to_post'))
            $this->meta_form_to_post($key,$form,$post);
        }


      //ここから内容チェック
      if(!validate_email($post['user_email']))
        $post['user_email'] = null;
      if(preg_match('/[^\d\-]/',$post['user_phone']))
        $post['user_phone'] = null;
      if(preg_match('/[^\d\-]/',$post['user_zipcode']))
        $post['user_zipcode'] = null;
      if(preg_match('/[^_%a-z0-9]/i',$post['user_name']))
        $post['user_name'] = null;

      return $post;
    }

  protected function post_to_form($post,$prefix = 'user_')
    {
      $form = array();
      $form['name']     = $post[$prefix.'name'];
      $form['email']    = $post[$prefix.'email'];
      $form['phone']    = $post[$prefix.'phone'];
      $form['zipcode']  = $post[$prefix.'zipcode'];
      $form['address']  = $post[$prefix.'address'];
      $form['regist']   = $post[$prefix.'regist_time'];
      $form['lastupdate'] = $post[$prefix.'lastupdate']; 
      list($form['by'],$form['bm'],$form['bd']) = explode('-',$post[$prefix.'birth']);

      //ここからはメタデータ
      foreach($this->meta_keys as $key)
        {
          $form[$key] = $post[$key];
          if(method_exists($this,'meta_post_to_form'))
            $this->meta_post_to_form($key,$post,$form);
        }

      return $form;
    }
  
  // override parent::update
  public function update($id,$post,array $options = array())
  {
    $options = array_merge($options,array('excludes' => array('user_regist_time')));
    
    return parent::update($id,$post,$options);
  }
}
