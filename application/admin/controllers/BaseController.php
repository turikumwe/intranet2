<?php

/**
 * BaseController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once 'admin/models/CRUDInterface.php';
abstract class Admin_BaseController extends Zend_Controller_Action implements CRUDInterface
{
	/**
	 * The default action - show the home page
	 */
	public function indexAction() 
	{
		$searchText = $this->getRequest()->getParam('search','');
		
		$this->view->searchText = $searchText;
		$this->view->list =  $this->generateList($searchText);
		$this->view->header = $this->generateHeader();
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->pathToController = $this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/';
		$this->view->table = $this->getTableName();
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->errorMessages = implode('<br/>',$this->_helper->flashMessenger->getMessages());
	}
	
	public function addAction()
	{
		$this->view->form = $this->getForm();
		$this->view->pageTitle = $this->getPageTitle() ." : ". $this->translate("Add new");
		$this->renderScript('form.phtml');
	}
	
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item,true);
		$this->view->pageTitle = $this->getPageTitle();
		$this->renderScript('form.phtml');
	}
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item);
		$this->view->pageTitle = $this->getPageTitle() ." : ". $this->translate("Edit");
		$this->renderScript('form.phtml');
	}
	
	public function deleteAction()
	{
		$ids = $this->getRequest()->getParam("ids");
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$items = $table->find($ids);
		
		foreach($items as $obj)
		{
			$obj->active = 0;
			$obj->save();
		}
		
		return $this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/');
	}
	
	public function submitAction()
	{
		$params = $this->getRequest()->getParams();
		if (!$this->getRequest()->isPost()) {
			return $this->_forward('index');
		}
		
		$form = $this->getForm();
		
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->pageTitle = $this->getPageTitle();
			$this->view->form = $form;
			return $this->renderScript('form.phtml');
		}
		
		$values = $form->getValues();
		
		$this->preSubmit($values);
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		if(Precurio_Utils::isNull($values['id']))
		{
			unset($values['id']);
			$data = $table->createRow($values);
			$id = $data->save();
			$values['id'] = $id;
		}
		else
		{
			$row = $table->find($values['id'])->current();
			$row->setFromArray($values);
			$row->save();
			$values['editop'] = 1;//editop i.e edit operation should be set, to differentiate from a add operation
		}
		$this->postSubmit($values);
		return $this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/');
	}
	
	/* (non-PHPdoc)
	 * @see application/admin/models/CRUDInterface#preSubmit($params)
	 */
	function preSubmit(&$params)
	{
		
	}
	/* (non-PHPdoc)
	 * @see application/admin/models/CRUDInterface#postSubmit($params)
	 */
	function postSubmit($params)
	{
		
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ADMIN);
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->pathToController = $this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/';
		
	}
	
	public function translate($str)
	{
		return $this->view->translate($str);
	}

}
?>