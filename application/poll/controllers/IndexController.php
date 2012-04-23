<?php
require_once 'Zend/Controller/Action.php';
require_once ('poll/models/Polls.php');
/**
 * IndexController
 * 
 * @author
 * @version 
 */

class Poll_IndexController extends  Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$poll = Polls::getActivePoll();
		$this->view->poll = $poll;
	}
	public function voteAction()
	{
		$id = $this->getRequest()->getParam('id');
		$this->view->poll = Polls::getPoll($id);
	}
	public function resultAction()
	{
		$id = $this->getRequest()->getParam('id');
		$this->view->poll = Polls::getPoll($id);
	}
	public function submitvoteAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$params =  $this->getRequest()->getParams();
		$poll_id = $params['poll_id'];
		$poll = Polls::getPoll($poll_id);
		foreach($params as $key=>$value)
		{
			if(substr($key,0,6)=='option')
			{
				$poll->newVote($value);	
			}
		}
		
		echo $this->view->translate("Thank you for voting");
	}
	
	public function preDispatch()
	{
		$this->_helper->layout->disableLayout();
		//$this->_helper->viewRenderer->setNoRender();
	}

}
?>