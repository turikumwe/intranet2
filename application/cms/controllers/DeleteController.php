<?php

/**
 * DeleteController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('cms/models/MyContents.php');
class Cms_DeleteController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		// TODO Auto-generated DeleteController::indexAction() default action
	}
	public function deleteAction()
	{
		$myContent = new MyContents();
		
		$content_id = $this->getRequest()->getParam('c_id',0);
		
		if($content_id == 0)
		{
			$this->_redirect('/cms/view/index/');	
			return;
		}
		
		$this->_helper->viewRenderer->setNoRender();
		
		$content = $myContent->getContent($content_id);
		$content->active = 0;
		$content->save();
		
		$dict = new Precurio_Search();
		$dict->unIndexContent($content_id);
	}
}
?>