<?php

/**
 * IndexController - The default controller class
 * 
 * @author
 * @version 
 */

class Cms_IndexController extends Zend_Controller_Action 
{

    public function indexAction() 
    {
    	$this->_helper->layout->setLayout(PrecurioLayoutConstants::MAIN_C);
    	$this->_forward('index','view') ;
    }
  
}
