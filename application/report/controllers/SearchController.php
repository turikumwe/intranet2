<?php

/**
 * SearchController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'report/models/Report.php';
require_once 'report/models/AnalyticsReport.php';

class Report_SearchController extends Zend_Controller_Action {
	public function translate($str)
	{
		return $this->view->translate($str);
	}
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		return $this->_redirect('/report/search/phrase');
	}
	public function phraseAction()
	{
		
	}
	public function contentAction()
	{
        
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_REPORT);
		$this->view->page = $this->getRequest()->getActionName();
	}
	public function postDispatch()
	{
		$this->render('index');
	}

}
?>