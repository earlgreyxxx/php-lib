<?php
/*******************************************************************************
 *
 *  Base class for a read only row 
 *
 *   All written by Kenji, Nakagawa.
 *   許可なく配布もしくは使用はできません。
 *
******************************************************************************/

class ReadonlyDatabaseRow extends DatabaseRow
{
  final public function save(?array $columns = null)
  {
    throw new RuntimeException(_('read only object'));
  }

  final public function delete()
  {
    throw new RuntimeException(_('read only object'));
  }

  final public function __set($name,$value)
  {
    throw new RuntimeException(_('read only object'));
  }

  final public function __unset($name)
  {
    throw new RuntimeException(_('can not unset property'));
  }
}
