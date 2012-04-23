<?php

/**
 * UserController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('cms/models/MyContents.php');
class Cms_UserController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() 
	{
		
	}
	public function shareAction()//this action is ONLY called by the user select popup facebox
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$selected_ids = $this->getRequest()->getParam('users');
		$selected_ids = explode(",",$selected_ids);
		$content_id = array_shift($selected_ids);
		
		$contents = new MyContents();
		$content = $contents->getContent($content_id);
		$content->setSharedUsers($selected_ids);
		
		$user_ids = $content->getSharedUsers();
		$ns = new Zend_Session_Namespace('temp');
		$ns->selectedUsers = $user_ids;//array
	}

}
?>