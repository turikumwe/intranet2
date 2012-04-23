<?php

/**
 * ContentController
 * 
 * @author
 * @version 
 */
require_once 'Zend/Controller/Action.php';
require_once ('user/models/UserUtil.php');
require_once ('user/models/vo/Comment.php');
class User_ContentController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
	}
	public function submitcommentAction()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		$params = $this->getRequest()->getParams();
		$is_content = $params['is_content'];
		$user_id = $params['user_id'];
		$comment = $params['comment'];
		
		$activity_id = $is_content ? 0 : $params['id'];
		$content_id = $is_content ? $params['id'] : 0;
		$userUtils = new UserUtil();
		$user = $userUtils->getUser($user_id);
		
		$commentObj = new Comment();
		$comment_id = $commentObj->createNew($user_id,$comment,$activity_id,$content_id);
		$baseUrl = $this->getRequest()->getBaseUrl();
		echo "<div class='commentLine'><a href='$baseUrl/user/profile/view/{$user->getId()}'>{$user->getFullName()}: </a>{$comment}<br /> 
              <span class='textLinks'>".$this->view->translate(PrecurioStrings::FEWSECONDSAGO)."</span>
              <div align='right' ><a href='#' onclick='deleteComment(event,{$comment_id})'>".$this->view->translate('Delete')."</a></div>
              </div>";
	}
	public function deletecommentAction()
	{
		$id = $this->getRequest()->getParam('id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::COMMENTS));
		
		$comment = $table->find($id)->current();
		$comment->delete();
	}

}
?>