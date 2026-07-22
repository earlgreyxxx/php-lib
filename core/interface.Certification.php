<?php
/*******************************************************************************

  define Certification interfaces

  All Written by K.,Nakagawa.
*******************************************************************************/
interface Certification
{
  public function certify(mixed $data,array $params) : array|bool;
}
