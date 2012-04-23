<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Helpdesk_IndexController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$this->_forward('form');
	}
	public function fillAction()
	{
		$this->_helper->layout->disableLayout();
	}
	public function formAction()
	{
		
	}
}
?>