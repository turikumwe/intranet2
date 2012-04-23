<?php

/**
 * ViewController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('document/models/Documents.php');
class Document_ViewController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		
	}
	public function downloadAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$content_id = $this->getRequest()->getParam('c_id');
		$document = Documents::getDocument($content_id);
		
		$document->download();
		
	}
}
?>