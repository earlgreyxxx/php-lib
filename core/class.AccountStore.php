<?php
/********************************************************************************

  アカウントテーブルのアクセサ・クラス

  $store = new AccountStore($dsn,$table,$dbuser,$dbpasswd);

*******************************************************************************/

class AccountStore extends UniversalStore
{
  /*--------------------------------------------------------------------------
    Static members.
   --------------------------------------------------------------------------*/

  //テーブルを用意する。
  public static function Prepare($pdoex,$table)
    {
      $columns = array('account_id INTEGER PRIMARY KEY %AUTOINCREMENT%',
                       'account_user_id INTEGER NOT NULL',
                       'account_name VARCHAR(32) UNIQUE NOT NULL',
                       'account_digest VARCHAR(255) NOT NULL',
                       'account_type INTEGER',
                       'account_period DATETIME',
                       'account_lastupdate DATETIME',
                       'account_regist_time DATETIME');

      $indexes = array('account_user_id');

      return static::CreateTable($pdoex,$table,$columns,$indexes);
    }

  protected static $Placeholders = array('account_id'          => array('?',PDO::PARAM_INT),
                                         'account_user_id'     => array('?',PDO::PARAM_INT),
                                         'account_name'        => array('?',PDO::PARAM_STR),
                                         'account_digest'      => array('?',PDO::PARAM_STR),
                                         'account_type'        => array('?',PDO::PARAM_INT),
                                         'account_period'      => array('?',PDO::PARAM_STR),
                                         'account_lastupdate'  => array('?',PDO::PARAM_STR),
                                         'account_regist_time' => array('?',PDO::PARAM_STR));

  /*--------------------------------------------------------------------------
     Instance members.
  --------------------------------------------------------------------------*/

  public function __construct($dsn,$table_name,$dbuser = '',$dbpasswd = '',$options = array())
    {
      //基本クラスのコンストラクタをコール
      parent::__construct($dsn,$table_name,$dbuser,$dbpasswd,$options);

      //メタキーの定義
      $meta_keys = array('email','secret_query','secret_answer');

      if(count($meta_keys) > 0)
        $this->meta_keys = array_merge($this->meta_keys,$meta_keys);
    }

  protected function form_to_post($form = null)
    {
      if($form == null)
        $form = &get_post();

      $post = array();

      $post['account_user_id']     = intval($form['uid']);
      $post['account_name']        = str_sanitize($form['name']);
      $post['account_email']       = str_sanitize($form['email']);
      $post['account_digest']      = crypt_blowfish(str_sanitize($form['passwd']));
      $post['account_type']        = intval($form['type']);
      $post['account_period']      = sprintf('%04d-%02d-%02d',intval($form['py']),intval($form['pm']),intval($form['pd']));
      $post['account_regist_time'] = Now();
      $post['account_lastupdate']  = Now();

      //ここからはメタデータ
      foreach($this->meta_keys as $key)
        {
          if(isset($form[$key]))
            {
              if(is_array($form[$key]))
                {
                  foreach($form[$key] as &$val)
                    $val = htmlspecialchars($val,ENT_QUOTES,'UTF-8');

                  $post[$key] = $form[$key];
                }
              else
                {
                  $post[$key] = htmlspecialchars($form[$key],ENT_QUOTES,'UTF-8');
                }
            }

          if(method_exists($this,'meta_form_to_post'))
            $this->meta_form_to_post($key,$form,$post);
        }

      //ここから内容チェック
      if(!validate_email($post['account_email']))
        $post['account_email'] = null;
      if(preg_match('/[^_%a-z0-9]/i',$post['account_name']))
        $post['account_name'] = null;

      return $post;
    }

  protected function post_to_form($post,$prefix = 'account_')
    {
      $form = array();
      $form['uid']        = $post[$prefix.'user_id'];
      $form['name']       = $post[$prefix.'name'];
      $form['email']      = $post[$prefix.'email'];
      $form['digest']     = '';
      $form['type']       = $post[$prefix.'type'];
      list($form['py'],$form['pm'],$form['pd']) = explode('-',$post['period']);
      $form['regist']     = $post[$prefix.'regist_time'];
      $form['lastupdate'] = $post[$prefix.'lastupdate'];

      //ここからはメタデータ
      foreach($this->meta_keys as $key)
        {
          $form[$key] = $post[$key];
          if(method_exists($this,'meta_post_to_form'))
            $this->meta_post_to_form($key,$post,$form);
        }

      return $form;
    }

  /* return userid value from username */
  protected function get_userid($username)
  {
    $rv = false;
    $this->set_filter('and',sprintf('account_name = %s',$this->dbh->quote($username)));
    if(count($rows = $this->gets(1)) == 1)
    {
      $row = $rows[0];
      $rv = $row['account_username'];
    }
    $this->set_filter('and','');
    return $rv;
  }

  // override parent::update
  public function update($id,$post,array $options = array())
  {
    $options = array_merge($options,array('excludes' => array('account_regist_time')));

    return parent::update($id,$post,$options);
  }


  public function get_digest($username)
  {
    $rv = false;
    $this->set_filter('and',sprintf('account_name = %s',$this->quote($username)));
    $rows = $this->gets(0);
    if(false !== $rows && count($rows) > 0)
    {
      $row = array_shift($rows);
      $rv = $rv['account_digest'];
    }

    return $rv;
  }

  public function get_values($id = '',$conv = true ,$tr = false)
  {
    if(empty($id))
      return false;

    $aid = false;
    $rv = false;

    $aid = is_int($id) ? $id : $this->get_userid($id);
    if($aid !== false)
      $rv = parent::parent($id,$conv);

    return $rv;
  }
}
