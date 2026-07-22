<?php
/*******************************************************************************

  Base Controller for command line interface

  All Written by K.,Nakagawa.

*******************************************************************************/

// Controller for command line interface
class CliControllerBase extends ControllerBase
{
  protected array $arguments;

  //Constructor
  public function __construct(array $define = [])
  {
    $this->arguments = $define;
  }

  protected function createView() : ?ViewBase
  {
    return null;
  }

  protected function getView() : ViewBase
  {
    return $this->view;
  }
}
