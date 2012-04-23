<?php

/**
 * PostController
 * 
 * @author
 * @version 
 */

require_once('forum/models/Forums.php');
require_once('forum/models/Topics.php');
require_once('forum/models/Posts.php');
class Forum_PostController extends Zend_Controller_Action 
{
		
	
	public function indexAction()
	{
		$this->view->topic_id = $this->getRequest()->getParam('tid');
		$this->view->cpage = $this->getRequest()->getParam('cpage');
	}
	
	public function addAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->topic_id = $this->getRequest()->getParam('tid');
		$this->view->qid = $this->getRequest()->getParam('qid');
	}
	
	public function deleteAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$post_id = $this->getRequest()->getParam('id');
				
		$posts = new Posts();
		echo $posts->deletePost($post_id);	
	}
	
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();	
		
		if (!$this->getRequest()->isPost()) 
			return $this->_forward('index');
		
				
		$values = $this->getRequest()->getParams();
		 if(empty($values['content']))
		 {
		 	echo $this->view->translate('Could not post empty content, please enter a post content.');
			return;
		 }
		
		$posts = new Posts();
		
		if(!isset($values['id']))
		{
			$values['user_id'] = Precurio_Session::getCurrentUserId();
			$values['date_posted'] = time();
								
			$result = $posts->addPost($values);			
			
		}
		else
		{
			$result = $posts->updatePost($values['id'],$values); 
		}
		
		
		echo $result;
	}
	
}
?>