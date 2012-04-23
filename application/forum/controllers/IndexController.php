<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once('forum/models/Forums.php');
class Forum_IndexController extends Zend_Controller_Action 
{
		
	
	public function indexAction()
	{
		$this->view->cpage = $this->getRequest()->getParam('cpage');
	}
	
	public function addAction()
	{
		$this->_helper->layout->disableLayout();
	}
	
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();	
		
		if (!$this->getRequest()->isPost()) 
			return $this->_forward('index');
		
				
		$values = $this->getRequest()->getParams();
		
		
		$forums = new Forums();
		
		if(!isset($values['id']))
		{
			$values['creator'] = Precurio_Session::getCurrentUserId();
			$values['date_created'] = time();
			$result = $forums->addForum($values);			
			
		}
		else
		{
			$result = $forums->updateForum($values['id'],$values); 
		}
		
		
		echo $result;
	}
	
}
?>