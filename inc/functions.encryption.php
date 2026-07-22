<?php /*************************************************************************

  可逆暗号/ダイジェストを扱う関数

  All Written by K.,Nakagawa.

*******************************************************************************/

function get_cipher(string $key,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : ReversibleEncryption
{
  static $ciphers = [];
  if(!isset($ciphers[$key]))
    $ciphers[$key] = new ReversibleEncryption($key,$algo);

  return $ciphers[$key];
}

function str_encrypt(string $plain,string $key,bool $base64encode = true,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : string|false
{
  if(empty($key))
    throw new Exception(_('key length must not be zero.'));

  return get_cipher($key,$algo)->encrypt($plain,$base64encode);
}

function str_decrypt(string $encrypted,string $key,bool $base64decode = true,string $algo = ReversibleEncryption::DEFAULT_ALGORITHM) : string|false
{
  if(empty($key))
    throw new Exception(_('key length must not be zero.'));

  return get_cipher($key,$algo)->decrypt($encrypted,$base64decode);
}

// generate uniqid by time slicing(decrypt and encrypt key is time based string)
function get_time_slice_uniqid() : string
{
  static $rv = null;
  static $times = 0;
  $delta = defined('FILE_TIME_SLICE') ? FILE_TIME_SLICE : 30 * 60;
  $current = floor(time() / $delta);

  if (empty($rv) || $times !== $current)
  {
    $times = $current;
    $seed = defined('FILE_SEED') ? FILE_SEED : openssl_digest(get_version(), 'sha256', true);
    if (!empty($_SERVER['HTTP_HOST']))
      $seed = $_SERVER['HTTP_HOST'] . $seed;

    $seed .= $current;

    $rv = md5($seed);
  }
  
  return $rv;
}

function str_encrypt_ts(string $plain,bool $is_hex = true) : string
{
  $key = get_time_slice_uniqid();
  $rv = str_encrypt($plain,$key,false);
  return $is_hex ? bin2hex($rv) : $rv;
}

function str_decrypt_ts(string $encrypted,bool $is_hex = true) : string|false
{
  $key = get_time_slice_uniqid();

  if($is_hex)
    $encrypted = function_exists('hex2bin') ? hex2bin($encrypted) : pack("H*",$encrypted);

  return str_decrypt($encrypted,$key,false);
}

/*------------------------------------------------------------------------------
 blowfish 与えられた文字列とコストからcrypt関数で使用するBlowfishハッシュを返す
------------------------------------------------------------------------------*/
function blowfish(string $plain, int $cost = 4) : string
{
  // Blowfishのソルトに使用できる文字種
  static $chars = null;
  if ($chars === null)
    $chars = array_merge(range('a', 'z'), range('A', 'Z'), array('.', '/'));

  // ソルトを生成（上記文字種からなるランダムな22文字）
  $salt = '';
  for ($i = 0; $i < 22; $i++)
  {
    $salt .= $chars[mt_rand(0, count($chars) - 1)];
  }

  // コストの前処理
  $cost = intval($cost);
  if ($cost < 4)
    $cost = 4;
  elseif ($cost > 31)
    $cost = 31;

  return crypt($plain, sprintf('$2a$%02d$%s', $cost, $salt));
}

function crypt_blowfish(string $plain,int $cost = 4) : string
{
  return blowfish($plain,$cost);
}


/*------------------------------------------------------------------------------
  二つの引数($hint1,$hint2)に依存するキーを作成します。
  $callable：キー生成アルゴリズム関数。指定しない場合はダイジェスト関数。
------------------------------------------------------------------------------*/
function create_key(string $hint1,string $hint2,?callable $callable = null) : string
{
  $rv = '';
  $hint = sprintf('%s:%s',$hint1,$hint2);

  if (!empty($callable))
  {
    if ($callable === 'crypt')
      return crypt_blowfish($hint);
    else if (is_callable($callable))
      return call_user_func($callable, $hint1, $hint2);
  }

  return sha1($hint);
}

/*------------------------------------------------------------------------------
  数字をスクランブルする。
-------------------------------------------------------------------------------*/
function scramble(int $seed) {
  $hash_keys = array(0xb47fa8c6, 0xa8c81029);

  $value = $seed;
  foreach($hash_keys as $hash) {
    $hash = ($hash & 0x7fffffff | 0x1);
    $value = ($value * $hash) & 0x7fffffff;
  }

  return $value;
}

/*------------------------------------------------------------------------------
  uniqid(string $prefix = "", bool $more_entropy = false) : string 関数の代替
-------------------------------------------------------------------------------*/
function str_uniqid(string $prefix = '',bool $dummy = false) : string
{
  return $prefix . sha1(random_bytes(32));
}


/*------------------------------------------------------------------------------
  uniqid(string $prefix = "", bool $more_entropy = false) : string 関数の代替
-------------------------------------------------------------------------------*/
function sha256(string $str,bool $is_bin = false)
{
  return hash('sha256', $str, $is_bin);
}
