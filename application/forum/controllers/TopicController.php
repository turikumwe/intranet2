<?php

/**
 * TopicController
 * 
 * @author
 * @version 
 */

require_once('forum/models/Forums.php');
require_once('forum/models/Topics.php');
class Forum_TopicController extends Zend_Controller_Action 
{
		
	
	public function listAction()
	{
		$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_USER);
		//if fid was not passed, check session. the only time this happens is if user navigates to topics via breadcrumbs
		$this->view->forum_id = $this->getRequest()->getParam('fid',$ns->forum_id);
		$ns->forum_id = $this->view->forum_id;
		$this->view->cpage = $this->getRequest()->getParam('cpage');
	}
	
	public function addAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->forum_id = $this->getRequest()->getParam('fid');
	}
	
	public function editAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->topic_id = $this->getRequest()->getParam('tid');
	}
	
	public function deleteAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$topic_id = $this->getRequest()->getParam('id');
				
		$topics = new Topics();
		echo $topics->deleteTopic($topic_id);	
	}
	
	public function submitAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();	
		
		if (!$this->getRequest()->isPost()) 
			return $this->_forward('index');
		
				
		$values = $this->getRequest()->getParams();
		
		//data validation
		if(empty($values['title']))
		{
			echo $this->view->translate('Could not create topic, please enter a topic title.');
			return;
		}
		if(isset($values['post']) && empty($values['post']))
		{
			echo $this->view->translate('Could not create topic, please enter a new post for your topic.');
			return;
		}
		$topics = new Topics();
		
		if(!isset($values['id']))
		{
			$values['creator'] = Precurio_Session::getCurrentUserId();
			$values['date_created'] = time();
			$result = $topics->addTopic($values);			
			
		}
		else
		{
			$result = $topics->updateTopic($values['id'],$values); 
		}
		
		
		echo $result;
	}
	
}
?>