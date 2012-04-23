<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Report_IndexController extends Zend_Controller_Action{
	/**
	 * The default action - show the home page
	 */
	public function indexAction() 
	{
		Precurio_Session::getLicense()->validate();
	}
	
}
?>