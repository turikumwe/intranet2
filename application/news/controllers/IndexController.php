<?php

/**
 * IndexController - The default controller class
 * 
 * @author
 * @version 
 */

class News_IndexController extends Zend_Controller_Action 
{

    public function indexAction() 
    {
    	$this->_helper->layout->setLayout(PrecurioLayoutConstants::MAIN_C);
    	return $this->_forward('index','view');
    }
  	public function indexrssAction()
  	{
  		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
  		$dict = new Precurio_Search();
  		$dict->indexContent($this->getRequest()->getParam('id'));
  	}
  	
}
