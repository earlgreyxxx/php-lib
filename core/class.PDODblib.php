<?php
/*******************************************************************************

  This class is derivered from PDO(PHP INTERNAL CORE CLASS).
   Aattach database handle(PDO Instance) to Store Object,
     and solution for differences of PDO drivers. 

   This Class is for PDO - SQLServer(PDO-DBLIB)

   ALL WRITTEN BY K,NAKAGAWA.

*******************************************************************************/

class PDODblib extends PDOSqlserver
{
  protected static $DBM = 'dblib';

  /*------------------------------------------------------------------------------
    Instances
  ------------------------------------------------------------------------------*/


  /*------------------------------------------------------------------------------
    Constructor
  ------------------------------------------------------------------------------*/
  public function __construct($dsn,$username='',$password='',$options = array())
    {
      if(!preg_match('/^dblib:/',$dsn))
        throw new Exception(_('Invalid dsn... This PDO Extension require "dblib:" prefix'));

      $this->dsn = $dsn;
      if(!isset($options[PDO::ATTR_EMULATE_PREPARES]))
        $options[PDO::ATTR_EMULATE_PREPARES] = false;

      parent::__construct($dsn,$username,$password,$options);
    }

  public function getPlaceholder($param_int = false)
    {
      $nPrefix = 'N';
      if($param_int === PDOExtension::PARAM_IS_INT)
        $nPrefix = '';

      return $nPrefix . '?';
    }
}

