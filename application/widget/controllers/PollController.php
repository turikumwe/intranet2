<?php

/**
 * PollController
 * 
 * This the controller for the Polls widget. No need to define new actions here, we simply
 * extend the existing Poll_IndexController. Please note that once Polls becomes a standalone
 * module, we will move the contents of Poll_IndexController here.
 * 
 * @author Brain
 * @version 2.1 
 */

require_once 'Zend/Controller/Action.php';
require_once ('poll/models/Polls.php');
require_once ('widget/controllers/IndexController.php');
class Widget_PollController extends  Widget_IndexController{
/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$poll = Polls::getActivePoll();
		$this->view->poll = $poll;
		$this->render();
	}
	public function voteAction()
	{
		$id = $this->getRequest()->getParam('id');
		$this->view->poll = Polls::getPoll($id);
		$this->render();
	}
	public function resultAction()
	{
		$id = $this->getRequest()->getParam('id');
		$this->view->poll = Polls::getPoll($id);
		$this->render();
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
		$this->render();
	}
}
?>

