<?php

/**
 * ViewController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('cms/models/MyContents.php');
class Cms_ViewController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
		$params = $this->getRequest()->getParams();
		
		$myContent = new MyContents();
		if(isset($params['id']) && $params['id'] != 0)
		{
			$category = Category::getCategory($params['id']);
			$this->view->contents = $category->getContentChildren(true);
			$this->view->category_id = $params['id'];
			
		}
		else
		{
			$this->view->contents = $myContent->getAll();
			$this->view->category_id = 0;
		}
		
		/*
		 * uncomment for grouping content by groups
		
		$publicOnly = $this->getRequest()->getParam('p',0);
		if(isset($params['g_id']) && $params['g_id'] != 0)
		{
			$this->view->contents = $myContent->getGroupContent($params['g_id'],$publicOnly);
			$this->view->group_id = $params['g_id'];
			
		}
		else
		{
			$this->view->contents = $myContent->getAll();
			$this->view->group_id = 0;
		}
		*/
	}
	
	public function detailsAction()
	{
		if($this->getRequest()->isXmlHttpRequest())
		{
			$this->_helper->layout->disableLayout();
		}
		else 
		{
			$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);	
		}
		
		$content_id = $this->getRequest()->getParam('c_id');
		$myContent = new MyContents();
		$content = $myContent->getContent($content_id);
		if(!$content->canAccess(Precurio_Session::getCurrentUserId()))
		{
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
		}
		if(!$content->isActive())//if the content is no more active
		{
			$content = null;
		}

		$this->view->content = $content;
		if($content != null)
		{
			$ns = new Zend_Session_Namespace('temp');
			$ns->id = $content->getId();
			$ns->selectedUsers = $content->getSharedUsers();//array
			$ns->selectLabel = $this->view->translate("Selected");
		}
		
	}
	
	public function loadAction()
	{
		$this->_helper->layout->disableLayout();
		$content_id = $this->getRequest()->getParam('c_id');
		if($content_id == null)return;
		$myContent = new MyContents();
		$content = $myContent->getContent($content_id);
		if($content == null)return ;
		$this->view->content = $content;
		
	}
	
	public function allAction()
	{
		//$this->_helper->viewRenderer->setNoRender(true);
		return $this->_forward('index');
	}
	public function rateAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$content_id = $this->getRequest()->getParam('c_id');
		$value = $this->getRequest()->getParam('value');
		$myContent = new MyContents();
		$content = $myContent->getContent($content_id);
		
		if($content == null)return  $this->_forward('index');
		
		$content->rating  += $value;
		$content->save(); 
		
		$data = array();
		$data['content_id'] = $content_id;
		$data['user_id'] = Precurio_Session::getCurrentUserId();
		$data['value'] = $value;
		$data['date_created'] = Precurio_Date::now()->getTimestamp();
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_RATINGS));
		$row = $table->createRow($data);
		$row->save();
	}
	public function preDispatch()
	{
		$ns = new Zend_Session_Namespace('temp');
		$ns->callback = $this->getRequest()->getBaseUrl()."/cms/user/share";//needed for the user select dialog
		$ns->selectedUsers = null;//clear the user select dialog.
	}
}
?>