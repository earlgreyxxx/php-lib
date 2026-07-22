<?php
/*******************************************************************************
 *
 *  Base class for database rows
 *
 *   All written by Kenji, Nakagawa.
 *   許可なく配布もしくは使用はできません。
 *
******************************************************************************/

class ReadonlyDatabaseRows extends DatabaseRows
{
  protected function initialize() : void
  {
    parent::initialize();
    $this->setRowClass('ReadonlyDatabaseRow');
  }

  final public function offsetSet(mixed $offset,mixed $value) : void
  {
    throw new RuntimeException(_('this object is read only attribute'));
  }
  final public function offsetUnset(mixed $offset) : void
  {
    throw new RuntimeException(_('this object is read only attribute'));
  }

}
