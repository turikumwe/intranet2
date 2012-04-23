<?php

/**
 * ListController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once('task/models/Tasks.php'); 
require_once('task/models/vo/Task.php');
require_once('cms/models/vo/Content.php');
class Task_ListController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
	}
	public function allAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->type = $this->getRequest()->getParam('type');
		$this->view->page = $this->getRequest()->getParam('page');
	}
	


}
?>