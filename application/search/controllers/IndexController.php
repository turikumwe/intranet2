<?php

/**
 * SearchController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Search_IndexController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() 
	{
		$query = $this->getRequest()->getParam('qry','');
		
		$query .= strlen($query) >= 3 ? '*' : ''; 
		$page = $this->getRequest()->getParam('cpage',0);
		$this->view->cpage = $page;
		$this->view->query = $query;
		if($query == '')
		{
			
			$this->view->hits = array();
			return;
			
		}
		if($page == 0)
			$this->view->cpage = 1;
			
		$dict = new Precurio_Search();
		$hits = $dict->search($query);
		$this->view->hits = $hits;
		
		$ns = new Zend_Session_Namespace('temp');
		$ns->searchResult = $this->view->hits;
		
		//log search query
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SEARCH));
		$id = $table->insert(array(
		'user_id'=>Precurio_Session::getCurrentUserId(),
		'query'=>$query,
		'hit_count'=>count($hits),
		'date_created'=>Precurio_Date::now()->getTimestamp()
		));
	}
	public function optimiseAction()
	{
		$dict = new Precurio_Search();
		$dict->optimize();
		echo $this->view->translate("Search Indexes have been optimised");
		$this->_helper->viewRenderer->setNoRender();
	}

}
?>