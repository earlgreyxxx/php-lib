<?php
/*******************************************************************************

  define DataAccess interfaces

  All Written by K.,Nakagawa.
*******************************************************************************/
interface DataAccess
{
  public function getData() : ?array;
  public function setData(array $data) : true;
  public function clearData() : void;
  public function refreshData() : void;
}
