<?php
/*******************************************************************************

  This class is derivered from PDO(PHP INTERNAL CORE CLASS).
   Aattach database handle(PDO Instance) to Store Object,
     and solution for differences of PDO drivers. 

  This Class is for PDO - SQLServer(PDO-MSSQL -windows-)

   ALL WRITTEN BY K,NAKAGAWA.

*******************************************************************************/

class PDOSqlsrv extends PDOSqlserver
{
  /*------------------------------------------------------------------------------
    Statics
  ------------------------------------------------------------------------------*/
  protected static $DBM = 'sqlsrv';

  /*------------------------------------------------------------------------------
    Instances
  ------------------------------------------------------------------------------*/


  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($dsn,$username='',$password='',$options = array())
    {
      if(!preg_match('/^sqlsrv:/',$dsn))
        throw new Exception(_('Invalid dsn... This PDO Extension require "sqlsrv:" prefix'));

      $this->dsn = $dsn;
      if(!isset($options[PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE]))
        $options[PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE] = true;

      if(!isset($options[PDO::SQLSRV_ATTR_QUERY_TIMEOUT]))
        $options[PDO::SQLSRV_ATTR_QUERY_TIMEOUT] = static::$QUERY_TIMEOUT;

      parent::__construct($dsn,$username,$password,$options);
    }
}
