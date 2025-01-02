<?php
/*******************************************************************************

  Base Controller for command line interface

  All Written by K.,Nakagawa.

*******************************************************************************/

// Controller for command line interface
class CliControllerBase extends ControllerBase
{
  protected $arguments;

  //Constructor
  public function __construct(array $define = array())
  {
    $this->arguments;
  }

  protected function createView()
  {
    return null;
  }

  protected function getView()
  {
    return $this->view;
  }
}
