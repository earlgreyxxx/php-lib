<?php
/*******************************************************************************

  Response object

  All Written by K.,Nakagawa.

*******************************************************************************/
class Response
{
  protected static Response $Instance;
  public static function GetInstance()
  {
    if(!isset(static::$Instance))
      self::$Instance = new static();

    return static::$Instance;
  }

  protected static array $STATUS = [
    '200' => 'OK',
    '201' => 'Created',
    '202' => 'Accepted',
    '203' => 'Non-Authoritative Information',
    '204' => 'No Content',
    '205' => 'Reset Content',
    '206' => 'Partial Content',
    '207' => 'Multi-Status',
    '208' => 'Already Reported',
    '226' => 'IM Used',

    '300' => 'Multiple Choises',
    '301' => 'Moved Permanently',
    '302' => 'Found',
    '303' => 'See Other',
    '304' => 'Not Modified',
    '305' => 'Use Proxy',
    '306' => 'Unused',
    '307' => 'Temporary Redirect',
    '308' => 'Permanent Redirect',

    '400' => 'Bad Request',
    '401' => 'Unathorized',
    '402' => 'Payment Required',
    '403' => 'Forbidden',
    '404' => 'Not Fount',
    '405' => 'Method Not Allowed',
    '406' => 'Not Acceptable',
    '407' => 'Proxy Authentication Required',
    '408' => 'Request Timeout',
    '409' => 'Conflict',
    '410' => 'Gone',
    '411' => 'Length Required',
    '412' => 'Precondition Failed',
    '413' => 'Payload Too Large',
    '414' => 'URI Too Long',
    '415' => 'Unsupported Media Type',
    '416' => 'Range Not Satisfiable',
    '417' => 'Expectation Failed',
    '418' => 'I\'m a teapot',
    '421' => 'Misdirected Request',
    '423' => 'Locked',
    '424' => 'Failed Dependency',
    '425' => 'Too Early',
    '426' => 'Upgrade Required',
    '428' => 'Precondition Required',
    '429' => 'Too Many Requests',
    '431' => 'Request Header Fields Too Large',
    '451' => 'Unavailable For Legal Reasons',

    '500' => 'Internal Server Error',
    '501' => 'Not Implemented',
    '502' => 'Bad Gateway',
    '503' => 'Service Unavailable',
    '504' => 'Gateway Timeout',
    '505' => 'HTTP Version Not Supported',
    '506' => 'Variant Also Negotiates',
    '507' => 'Insufficient Storage',
    '508' => 'Loop Detected',
    '509' => 'Bandwidth Limit Exceeded',
    '510' => 'Not Extended',
    '511' => 'Network Authentication Required'
  ];

  private function checkHeaderSent(string $headerError = 'HTTP Header was already sent.') : true
  {
    if(headers_sent())
      throw new Exception($headerError);

    return true;
  }

  protected function http_response_code(int|string $statuscode) : void
  {
    header(sprintf('HTTP 1.1 %d %s',$statuscode,self::$STATUS[$statuscode]));
  }

  // wrapper method of header core function for hash array.
  public function headers(array|string $headers,bool $replace = true) : void
  {
    $rv = false;
    $this->checkHeaderSent();
    if(is_array($headers))
    {
      foreach($headers as $name => $value)
      {
        $header_ = is_int($name) ?  $value : sprintf('%s: %s',$name,$value);
        header($header_,$replace);
      }
    }
    else if(is_string($headers))
    {
      header($headers,$replace);
    }
    else
    {
      throw new Exception(sprintf(_('1st parameter "%s" is invalid value type.'),$headers));
    }
  }

  // set CONTENT-TYPE Header
  public function content_type(string $mime_type,bool $replace = true) : void
  {
    $this->headers(['content-type' => $mime_type],$replace);
  }

  // set CONTENT-DISPOSITION Header
  public function content_disposition(string $filename,string $value = 'attachment' ,string $name = '') : void
  {
    $additions = [];
    if(!empty($filename))
    {
      $additions[] = sprintf('filename=%s',$filename);
      $additions[] = sprintf('filename*=UTF-8\'\'%s',rawurlencode($filename));
    }
    if(!empty($name))
      $additions[] = sprintf('name=%s',$name);

    $addition = '';
    if(count($additions) > 0)
      $addition = sprintf('; %s',implode('; ',$additions));

    $this->headers(['content-disposition' => $value . $addition]);
  }

  // set LOCATION header
  public function redirect(string $url,array $params = [],string $suffix = '?') : void
  {
    $params_string = '';
    if(!empty($params))
      $params_string = $suffix . http_build_query($params);

    $this->headers(['location' => sprintf('%s%s',$url,$params_string)]);
  }

  // set let browser chache is restricted.
  public function nocache(bool $replace = true) : void
  {
    $headers = [
      'Cache-Control' => 'no-store',
      'Expires'       => 'Wed, 10 Jan 1990 01:01:01 GMT',
      'Last-Modified' => gmdate("D, d M Y H:i:s") . ' GMT'
    ];

    $this->headers($headers,$replace);
  }

  // set status code
  public function status(int|string $status_code) : void
  {
    if(function_exists('http_response_code'))
      http_response_code($status_code);
    else
      $this->http_response_code($status_code);
  }

  // wrapper for content type of application
  // -------------------------------------------------------------------------------
  protected function application(string $type,string $prefix = 'application') : void
  {
    $this->content_type(sprintf('%s/%s',$prefix,$type));
  }
  public function json() : void
  {
    $this->application('json');
  }
  public function jsonp() : void
  {
    $this->application('javascript');
  }
  public function pdf() : void
  {
    $this->application('pdf');
  }
  public function raw() : void
  {
    $this->application('octed-stream');
  }

  // wrapper for content type of text
  // -------------------------------------------------------------------------------
  protected function text(string $type,string $charset = 'UTF-8') : void
  {
    $this->content_type(
      sprintf(
        'text/%s%s',
        $type,
        empty($charset) ? '' : sprintf('; charset=%s',$charset)
      )
    );
  }
  public function plain(string $charset = 'UTF-8') : void
  {
    $this->text('plain',$charset);
  }
  public function html(string $charset = 'UTF-8') : void
  {
    $this->text('html',$charset);
  }
  public function javascript(string $charset = 'UTF-8') : void
  {
    $this->text('javascript',$charset);
  }
  public function css(string $charset = 'UTF-8') : void
  {
    $this->text('css',$charset);
  }
}
