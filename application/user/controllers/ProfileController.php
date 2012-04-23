<?php

/**
 * UserController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'Precurio/Upload.php';
require_once ('user/models/UserUtil.php');
class User_ProfileController extends Zend_Controller_Action {
	
	public function preDispatch()
	{
		$this->_helper->layout->setLayout(PrecurioLayoutConstants::MAIN_C);
		$router = Zend_Controller_Front::getInstance()->getRouter();
        $fake_route = new Zend_Controller_Request_Http();
        $fake_route->setRequestUri('/');
        $router->route($fake_route);
	}
	public function indexAction() 
	{
		$this->_forward('view');
		
	}
	public function editAction()
	{
		
	}
	public function viewAction()
	{
		$this->_helper->layout->setLayout(PrecurioLayoutConstants::MAIN_C);
		$user_id = $this->getRequest()->getParam('id');
		if($user_id == Precurio_Session::getCurrentUserId() || $user_id == 0)
		{ 
			$this->view->isMyPage = 1;
		}
		else
		{
			$this->view->isMyPage = 0;
		}
		$utilFn = new UserUtil();
		$params = $this->getRequest()->getParams();
		$this->view->user = $utilFn->getUser($params['id']);
		$this->view->page = $params['page'];
		$this->view->param1 = $params['param1'];
		$this->view->param2 = $params['param2'];
		$this->view->cpage = $params['cpage'];
		
		//Precurio_Utils::debug($params);
	}
	public function updateAction()
	{
		$user = UserUtil::getUser(Precurio_Session::getCurrentUserId());
		$params = $this->getRequest()->getParams();
		if(isset($_FILES['profile_pic']) && $_FILES['profile_pic']['size'] > 0)
		{
			$filePath = Content::addPhoto('profile_pic',true);
	        $picId = $user->newProfilePic($filePath);
	        $params['profile_picture_id']=$picId;
		}
		$user->update($params);
		$this->_redirect('/user/profile/edit');
		return;
	}
	
	public function uploadpicAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		if(!isset($_FILES['profile_pic']))
		{
			$this->_redirect('/user/profile/edit');
			return;
		}
		
		$filePath = Content::addPhoto('profile_pic',true);
		$user = UserUtil::getUser(Precurio_Session::getCurrentUserId()); 
        $picId = $user->newProfilePic($filePath);
        $user->update(array('profile_picture_id'=>$picId));
		$this->_redirect('/user/profile/edit');
		return;
	}

}
?>