<?php
/*******************************************************************************

  可逆暗号化 【デフォルトはAES-256-CBC】

  ex.)
  $cipher = new ReversibleEncryption('43u2jrlewjrlw');
  $encoded = $cipher->encrypt('Test Plain Text');
  $decoded = $cipher->decrypt($encoded);

  All Written by K.,Nakagawa.

*******************************************************************************/
class ReversibleEncryption
{
  const DEFAULT_ALGORITHM = 'aes-128-cbc';

  /*------------------------------------------------------------------------------
    INSTANCE MEMBERS
  ------------------------------------------------------------------------------*/
  private $iv;
  private $key;
  private $algorithm;

  private function CreateInitializingVector()
  {
    $rv = random_bytes(openssl_cipher_iv_length($this->algorithm));
    return $rv !== false ? $rv : '';
  }

  /*------------------------------------------------------------------------------
    CONSTRUCTOR
  ------------------------------------------------------------------------------*/
  public function __construct($key,$algorithm = self::DEFAULT_ALGORITHM)
    {
      $algos = openssl_get_cipher_methods(true);
      if(false === array_search($algorithm,$algos))
        throw new Exception(_('algorithm is not valid'));

      $this->algorithm = $algorithm;
      $this->key = openssl_digest($key,'sha256',true);
    }

  /*------------------------------------------------------------------------------
    暗号化  ( 平文 , 暗号文をBASE64エンコードするか？)
  ------------------------------------------------------------------------------*/
  public function encrypt($plain,$base64encode = true)
    {
      $iv = $this->CreateInitializingVector(); 
      $encrypted = openssl_encrypt(
        $plain,
        $this->algorithm,
        $this->key,
        OPENSSL_RAW_DATA,
        $iv
      );

      if(!empty($iv))
        $encrypted = $iv . $encrypted;

      return $base64encode ? base64_encode($encrypted) : $encrypted;
    }

  /*------------------------------------------------------------------------------
    復号化  (暗号文 , 指定した暗号文はBASE64エンコードされているか？)
  ------------------------------------------------------------------------------*/
  public function decrypt($encrypted,$base64decode = true)
    {
      $decrypted = '';
      if(empty($encrypted))
        return $decrypted;

      if($base64decode)
        $encrypted = base64_decode($encrypted);

      if(0 < ($ivlen = openssl_cipher_iv_length($this->algorithm)))
      {
        $iv = substr($encrypted,0,$ivlen);
        $encrypted = substr($encrypted,$ivlen);
      }

      $decrypted = openssl_decrypt(
        $encrypted,
        $this->algorithm,
        $this->key,
        OPENSSL_RAW_DATA,
        $iv
      );

      return $decrypted;
    }
}

