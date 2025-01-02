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
  protected function initialize()
  {
    parent::initialize();
    $this->setRowClass('ReadonlyDatabaseRow');
  }

  final public function offsetSet($offset,$value)
  {
    throw new RuntimeException(_('this object is read only attribute'));
  }
  final public function offsetUnset($offset)
  {
    throw new RuntimeException(_('this object is read only attribute'));
  }

}
