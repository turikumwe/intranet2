<?php

class Workflow_IndexController extends Zend_Controller_Action 
{

    public function indexAction() 
    {
    	return $this->_forward('index','user');
    }
}
?>