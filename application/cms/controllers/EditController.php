<?php

/**
 * EditController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('task/models/Tasks.php');
require_once ('task/models/vo/Task.php');
require_once ('cms/models/MySettings.php');
require_once ('cms/models/MyContents.php');
class Cms_EditController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$this->_redirect('/cms/view/index/');	
	}
	public function editAction()
	{
		$myContent = new MyContents();
		
		$content_id = $this->getRequest()->getParam('c_id',0);
		
		if($content_id == 0)
		{
			$this->_redirect('/cms/view/index/');
			return;
		}
		
		$this->view->content = $myContent->getContent($content_id);
	}
	

}
?>