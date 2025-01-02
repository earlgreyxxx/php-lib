<?php
/*******************************************************************************

  define DataAccess interfaces

  All Written by K.,Nakagawa.
*******************************************************************************/
interface DataAccess
{
  public function getData();
  public function setData(array $data);
  public function clearData();
  public function refreshData();
}
