<?php

/**
 * IndexController - The default controller class
 * 
 * @author
 * @version 
 */

class Event_IndexController extends Zend_Controller_Action 
{

    public function indexAction() 
    {
    	$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
    	$this->_forward('view','my');
    	return;
    }
  
}
