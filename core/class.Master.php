<?php
/*******************************************************************************

  Master table management ... fetch and cache to APC or others
    assume that table has column ( id,name,value0,value1,value2,..... )

    - Constructor parameters ($dsn,$serializeType)

    - Usage :
       $master = new Master(GetPdoInstance('sqlite:./zipcode.sqlite'),'zipcode');

  All Written by K.,Nakagawa.
*******************************************************************************/

class Master extends MasterBase implements DataAccess,ArrayAccess,IteratorAggregate
{
  // Instance members
  private $data;
  private $offsetColumnIndex = 1;

  protected function init()
  {
    $obj = $this;
    $setDataFunc = function() use($obj) { $res = $obj->setData(); };
    $action = $this->action;
    $action->add('insert-done',$setDataFunc);
    $action->add('update-done',$setDataFunc);
    $action->add('delete-done',$setDataFunc);

    $this->setData();
  }

  protected function getID($column_value,$column_index = 1)
  {
    $tableColumns = $this->getColumns();
    $rv = false;
    if((false !== ($row = $this->getValue($column_value,$column_index))) && is_numeric($row[$tableColumns[0]]))
      $rv = intval($row[$tableColumns[0]]);

    return $rv;
  }

  // Constructor
  public function __construct(PDO $pdo,$tablename,array $options =  array())
  {
    if(array_key_exists('offset-column-index',$options) && is_numeric($options['offset-column-index']))
      $this->offsetColumnIndex = $options['offset-column-index'];

    parent::__construct($pdo,$tablename,$options);
  }

  public function setOffsetColumn($columnIndex)
  {
    if(!is_numeric($columnIndex))
      throw new RuntimeException(_('parameter 1st is invalid type'));
    if($columnIndex >= count($this->getColumns()))
      throw new RuntimeException(_('parameter 1st is out of range'));

    $rv = $this->offsetColumnIndex;
    $this->offsetColumnIndex = $columnIndex;

    return $rv;
  }

  // Insert row with each columns and values array.
  //   if not specified columns( = null), assume values has all column data
  public function append($values,?array $columns = null)
  {
    if($columns === null)
      $columns = array_slice($this->getColumns(),1);

    if(empty($columns) || empty($values))
      return false;

    $colnum = count($columns);
    $valnum = count($values);
    if($colnum > $valnum)
      $values = array_merge($values,array_fill(0,$colnum - $valnum,NULL));
    else if($colnum < $valnum)
      $values = array_slice($values,0,$colnum);

    return $this->inserter(array_combine($columns,$values));
  }

  // Insert row with column-value hash array
  public function add(array $cv)
  {
    return $this->inserter($cv);
  }

  // Update row
  public function modify($column_value,array $cv,$column_index = 1)
  {
    if($column_index == 0)
    {
      $id = $column_value;
    }
    else
    {
      if(false === ($id = $this->getID($column_value,$column_index)))
        return false;
    }
    return $this->updater($id,$cv);
  }

  // Delete row
  public function remove($column_value,$column_index = 1)
  {
    if($column_index == 0)
    {
      $id = $column_value;
    }
    else
    {
      if(false === ($id = $this->getID($column_value)))
        return false;
    }

    return $this->deleter($id);
  }

  // Implementation of DataAccess Interface
  public function getData()
  {
    if($this->data === null)
      $this->data = $this->selector();

    return $this->data;
  }

  public function setData(?array $data = null)
  {
    $this->data = is_array($data) ? $data : $this->selector();
    return true;
  }

  public function clearData()
  {
    $this->data = null;
  }

  public function refreshData()
  {
    $this->clearData();
    $this->setData();
  }

  // Implementation of ArrayAccess Interface
  #[\ReturnTypeWillChange]
  public function offsetExists($offset)
  {
    return false !== $this->offsetGet($offset);
  }

  #[\ReturnTypeWillChange]
  public function offsetGet($offset)
  {
    return $this->getValue($offset,$this->offsetColumnIndex);
  }

  #[\ReturnTypeWillChange]
  public function offsetSet($offset,$value)
  {
    $tableColumns = $this->getColumns();

    if($this->offsetExists($offset))
    {
      $rv = $this->modify($offset,$value,$this->offsetColumnIndex);
    }
    else
    {
      $rv = $this->add($value);
    }

    if($rv === false)
      throw new Exception(_(''));
  }

  #[\ReturnTypeWillChange]
  public function offsetUnset($offset)
  {
    return $this->remove($offset,$this->offsetColumnIndex);
  }

  // Implementation of IteratorAggregate Interface
  #[\ReturnTypeWillChange]
  public function getIterator()
  {
    return new ArrayIterator($this->getData());
  }

  public function getValue($name,$key_column_index = 1)
  {
    $rv = false;
    $data = $this->getData();
    if(empty($data))
      return $rv;

    $tableColumns = $this->getColumns();

    if(!is_int($key_column_index))
      $key_column_index = intval($key_column_index);

    $column_name = $tableColumns[$key_column_index];

    foreach($data as $row)
    {
      if($row[$column_name] === $name)
      {
        $rv = $row;
        break;
      }
    }

    return $rv;
  }

  public function getAll()
  { 
    if(false === ($rv = $this->getData()))
      $rv = array();

    return $rv;
  }

  public function id($column,$column_index = 1)
  {
    return $this->getID($column,$column_index);
  }

  public function columns($index = 1)
  {
    $tableColumns = $this->getColumns();
    return array_column($this->getData(),$tableColumns[$index]);
  }

  public function keys()
  {
    return $this->columns();
  }

  public function ids()
  {
    return $this->columns(0);
  }

  public function search($word,$column_index = 1)
  {
    $rv = array();
    $columns = $this->getColumns();
    $target = $columns[$column_index];
    foreach($this->getData() as $row)
    {
      if(false !== strpos($row[$target],$word))
        $rv[] = $row;
    }
    return $rv;
  }

  public function match($re,$column_index = 1)
  {
    $rv = array();
    $columns = $this->getColumns();
    $target = $columns[$column_index];
    foreach($this->getData() as $row)
    {
      if(preg_match($re,$row[$target],$m))
        $rv[] = $row;
    }
    return $rv;
  }

  public function identify($condition,$retIdOnly = false)
  {
    if(empty($condition))
      return false;

    $pdo = $this->getHandle();
    $db = 
      DB::CreateInstance($pdo)
        ->select()
        ->from($this->getTable())
        ->where($condition);

    if(false === ($sth = $db->query()))
      throw new RuntimeException(_('Database access failed'));

    $rv = $retIdOnly === true ? $sth->fetchColumn() : $sth->fetch(PDO::FETCH_NUM);
    $sth->closeCursor();
    $sth = null;

    return $rv;
  }
}

