<?php
/********************************************************************************
  CLI functions
********************************************************************************/
// echo for STDERR 
function erro(...$outs) : int|false
{
  return fputs(STDERR,implode('',$outs));
}

// wrapper
function read_user(bool $hidden = false) : string
{
  return read('dbuser: ',$hidden);
}

// wrapper
function read_passwd(bool $hidden = false) : string
{
  return read('password: ',$hidden);
}

// readline wrapper.
// if $hidden is true, no echo to input text like password
function read(?string $prompt,bool $hidden = false) : string
{
  $line = '';
  if($hidden === true)
  {
    fputs(STDERR,$prompt);
    system('stty -echo');
    $line = rtrim(fgets(STDIN),"\r\n");
    system('stty echo');
    fputs(STDERR,PHP_EOL);
  }
  else
  {
    $line = readline($prompt);
  }

  return $line;
}

function UserPass(?string $user = null,?string $pass = null) : array
{
  if(empty($user))
    $user = read_user();

  if(empty($pass))
    $pass = read_passwd(true);

  return [$user,$pass];
}


// prompt input
//   arguments:
//     display text(string),
//     no echo(bool:false),
//     need re-type(bool:false),
//     loop(int:3)
// ----------------------------------------------------------------------------------
function promptAndRequire(string $prompt,bool $hidden = false,bool $confirm = false,int $loop = 3) : ?string
{
  $rv = null;
  $count = $loop > 0 ? $loop : 3;
  do {
    $rv = read(sprintf('%s: ',$prompt),$hidden);

    $rv = mb_trim($rv);
    if(mb_strlen($rv) <= 0)
      continue;

    if($confirm)
    {
      if($rv !== mb_trim(read('retype same: ',$hidden)))
        $rv = '';
    }

  } while(mb_strlen($rv) <= 0 && --$count > 0);

  if(mb_strlen($rv) <= 0)
    throw new RuntimeException('falied to required input....');

  return $rv;
}

// confirm key input 'y' or 'no'
// ----------------------------------------------------------------------------------
function confirm(string $prompt,string $addition = ' .... is it OK?(y/N)') : bool
{
  $line = readline($prompt . $addition);
  return !(empty($line) || preg_match('/^n/i',$line) || !preg_match('/^y$/i',$line));
}


// return terminal cols 
// ----------------------------------------------------------------------------------
function tputcols() : int
{
  $cols = shell_exec('/usr/bin/env tput cols');
  return intval($cols);
}

// return terminal lines
// ----------------------------------------------------------------------------------
function tputlines() : int
{
  $lines = shell_exec('/usr/bin/env tput lines');
  return intval($lines);
}

function tputsize() : array
{
  list($lines,$cols) = preg_split('/\s+/',shell_exec('/usr/bin/env stty size'));
  return [intval($lines),intval($cols),'lines' => intval($lines),'cols' => intval($cols)];
}

// line
// ----------------------------------------------------------------------------------
function echoline(string $char = '-',int $repeat = -1) : void
{
  if($repeat < 0)
    $repeat = tputcols() - 1;

  echo str_repeat($char,$repeat),PHP_EOL;
}

function erroline(string $char = '-',int $repeat = -1) : void
{
  if($repeat < 0)
    $repeat = tputcols() - 1;

  erro(str_repeat($char,$repeat),PHP_EOL);
}

// colorize forground and background terminal text
// specify color string ex... black,red,green,yellow,blue,magenta,cyan,white and japanese kana or kanji
// -----------------------------------------------------------------------------------------------------
function tcolor(string $text,string $fgcolor,string $bgcolor = '',bool $high = false) : string
{
  if (strlen($text) == 0)
    return '';

  switch (strtolower($fgcolor))
  {
    case '黒':
    case 'ブラック':
    case 'black':
      $fgcolor_index = 30;
      break;

    case '赤':
    case 'レッド':
    case 'red':
      $fgcolor_index = 31;
      break;

    case '緑':
    case 'グリーン':
    case 'green':
      $fgcolor_index = 32;
      break;

    case '黄':
    case 'イエロー':
    case 'yellow':
      $fgcolor_index = 33;
      break;

    case '青':
    case 'ブルー':
    case 'blue':
      $fgcolor_index = 34;
      break;

    case 'マゼンタ':
    case 'magenta':
      $fgcolor_index = 35;
      break;

    case 'シアン':
    case 'cyan':
      $fgcolor_index = 36;
      break;

    case '白':
    case 'ホワイト':
    case 'white':
      $fgcolor_index = 37;
      break;

    case '':
      $fgcolor_index = 0;
      break;

    default:
      throw new RuntimeException('not support color string');
  }

  switch (strtolower($bgcolor))
  {
    case '黒':
    case 'ブラック':
    case 'black':
      $bgcolor_index = 40;
      break;

    case '赤':
    case 'レッド':
    case 'red':
      $bgcolor_index = 41;
      break;

    case '緑':
    case 'グリーン':
    case 'green':
      $bgcolor_index = 42;
      break;

    case '黄':
    case 'イエロー':
    case 'yellow':
      $bgcolor_index = 43;
      break;

    case '青':
    case 'ブルー':
    case 'blue':
      $bgcolor_index = 44;
      break;

    case 'マゼンタ':
    case 'magenta':
      $bgcolor_index = 45;
      break;

    case 'シアン':
    case 'cyan':
      $bgcolor_index = 46;
      break;

    case '白':
    case 'ホワイト':
    case 'white':
      $bgcolor_index = 47;
      break;

    case '':
      $bgcolor_index = 0;
      break;

    default:
      throw new RuntimeException('not support background color string');
  }

  if($high && $fgcolor > 0)
    $fgcolor+=60;
    
  if($high && $bgcolor > 0)
    $bgcolor+=60;

  return tescseq($text,$fgcolor_index, $bgcolor_index);
}

// return escape sequensed text
// 0:reset,1:bold,2:dim,4:underline,5:blink,7:inverse,8:hidden
// -----------------------------------------------------------------------------------------------------
function tescseq(string $text,int ...$escseq) : string
{
  if (strlen($text) == 0)
    return '';
  
  $escapeseq = implode(';', array_filter($escseq));
  return empty($escapeseq) ? $text : sprintf("\033[%sm%s\033[0m", $escapeseq, $text);
}
