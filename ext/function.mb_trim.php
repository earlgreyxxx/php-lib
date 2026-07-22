<?php /*------------------------------------------------------------------------
  multi byte trim (add zenkaku space)
------------------------------------------------------------------------------*/
function mb_trim(string $str) : string
{
  return preg_replace('/\A[\x00\s]++|[\x00\s]++\z/u', '', $str);
}