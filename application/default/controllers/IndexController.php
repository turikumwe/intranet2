<?php

/**
 * IndexController - The default controller class
 * 
 * @author
 * @version 
 */

class IndexController extends Zend_Controller_Action 
{
	/**
	 */
	public function preDispatch()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
	}
    public function indexAction() 
    {
    	$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
    	
    	$this->_forward('login','login');
    	return;
    }
    public function homeAction()
    {
    	Precurio_Session::getLicense()->validate();
    	$this->view->activityLimit = $this->getRequest()->getParam('l',10);
    	$this->_helper->layout->setLayout(PrecurioLayoutConstants::START);
    }
}
