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
  final public function save(?array $columns = null) : string|bool
  {
    throw new RuntimeException(_('read only object'));
  }

  final public function delete() : bool
  {
    throw new RuntimeException(_('read only object'));
  }

  final public function __set(string $name,mixed $value) : void
  {
    throw new RuntimeException(_('read only object'));
  }

  final public function __unset(string $name) : void
  {
    throw new RuntimeException(_('can not unset property'));
  }
}
