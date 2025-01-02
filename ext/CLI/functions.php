<?php
/********************************************************************************
  CLI functions
********************************************************************************/
// echo for STDERR 
function erro(...$outs)
{
  return fputs(STDERR,implode('',$outs));
}

// wrapper
function read_user($hidden = false)
{
  return read('dbuser: ',$hidden);
}

// wrapper
function read_passwd($hidden = false)
{
  return read('password: ',$hidden);
}

// readline wrapper.
// if $hidden is true, no echo to input text like password
function read($prompt,$hidden = false)
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

function UserPass($user = null,$pass = null)
{
  if(empty($user))
  { 
    $user = read_user();
    $pass = read_passwd(true);
  }
  else
  {
    if(empty($pass))
      $pass = read_passwd(true);
  }

  return array($user,$pass);
}


// prompt input
//   arguments:
//     display text(string),
//     no echo(bool:false),
//     need re-type(bool:false),
//     loop(int:3)
// ----------------------------------------------------------------------------------
function promptAndRequire(string $prompt,bool $hidden = false,bool $confirm = false,int $loop = 3)
{
  $rv = null;
  $count = $loop > 0 ? $loop : 3;
  do {
    $rv = read(sprintf('%s: ',$prompt),$hidden);

    if(empty($rv))
      continue;

    if($confirm)
    {
      if($rv !== read('retype same: ',$hidden))
        $rv = null;
    }

  } while(empty($rv) && --$count > 0);

  if(empty($rv))
    throw new RuntimeException('falied to required input....');

  return $rv;
}

// confirm key input 'y' or 'no'
// ----------------------------------------------------------------------------------
function confirm(string $prompt,string $addition = ' .... is it OK?(y/N)')
{
  $line = readline($prompt . $addition);
  return !(empty($line) || preg_match('/^n/i',$line) || !preg_match('/^y$/i',$line));
}


// return terminal cols 
// ----------------------------------------------------------------------------------
function tputcols()
{
  $cols = `/usr/bin/env tput cols`;
  return intval($cols);
}

// return terminal lines
// ----------------------------------------------------------------------------------
function tputlines()
{
  $lines = `/usr/bin/env tput lines`;
  return intval($lines);
}

function tputsize()
{
  list($lines,$cols) = preg_split('/\s+/',`/usr/bin/env stty size`);
  return [intval($lines),intval($cols),'lines' => intval($lines),'cols' => intval($cols)];
}

// line
// ----------------------------------------------------------------------------------
function echoline(string $char = '-',int $repeat = -1)
{
  if($repeat < 0)
    $repeat = tputcols() - 1;

  echo str_repeat($char,$repeat),PHP_EOL;
}

function erroline(string $char = '-',int $repeat = -1)
{
  if($repeat < 0)
    $repeat = tputcols() - 1;

  erro(str_repeat($char,$repeat),PHP_EOL);
}
