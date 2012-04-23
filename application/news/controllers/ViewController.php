<?php

/**
 * ViewController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('news/models/MyNews.php');
class News_ViewController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		// TODO Auto-generated ViewController::indexAction() default action
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
	}
	
	public function detailsAction()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
		$content_id = $this->getRequest()->getParam('c_id');
		if($content_id == null)return $this->_forward('index');
		$news = MyNews::getNews($content_id);
		if($news == null)return $this->_forward('index');
		$this->view->news = $news;
		
		$ns = new Zend_Session_Namespace('temp');
		$ns->id = $news->getId();
		$ns->selectedUsers = $news->getSharedUsers();//array
	}
	
	public function allAction()
	{
		//$this->_helper->viewRenderer->setNoRender(true);
		return $this->_forward('index');
	}
	public function companyAction()
  	{
  		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
  		
  	}
	public function preDispatch()
	{
		$ns = new Zend_Session_Namespace('temp');
		$ns->callback = $this->getRequest()->getBaseUrl()."/cms/user/share";//needed for the user select dialog
		$ns->selectedUsers = null;//clear the user select dialog.
	}
}
?>