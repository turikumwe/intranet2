<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Employee_IndexController extends Zend_Controller_Action {
	
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$this->_forward('find','staff','employee');
		return;
	}

}
?>