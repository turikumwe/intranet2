<?php

/**
 * BackupController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Admin_BackupController extends Zend_Controller_Action {

	public function indexAction() {
		$this->view->list =  $this->generateList();
		$this->view->header = array("",$this->translate("File name"), $this->translate("Size"), $this->translate("Date Created"));
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->pathToController = '/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/';
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
	}
	function generateList() {
		$backup = new Precurio_Backup();
		$items = $backup->getAll();
		$arr = array();
		
		$i = 1;
		foreach($items as $item)
		{
			$a = explode("/",$item)	;
			$filename = $a[count($a)-1];
			$fileDate = new Precurio_Date(filemtime($item));
			$fileSize = $backup->fsize(filesize($item));
			$arr[] = array($i,'file_name'=>$filename,'size'=>$fileSize,'date_created'=>$fileDate,'id'=>$filename);
			++$i;
		}
		return $arr;
	}
	
	function newAction()
	{
		$this->_helper->layout->disableLayout();
	}
	
	
	function backupAction()
	{
		Precurio_Session::getLicense()->proFeature();
		set_time_limit(0);
		
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		try
		{
			$params = $this->getRequest()->getParams();
			
			$backup = new Precurio_Backup();
			$backup->doBackup($params['title']);
			
			echo $this->translate("Backup operation was successful");
		}
		catch(Exception $e)
		{
			echo $this->translate("Backup operation was not successful");
			$log = Zend_Registry::get('log');
			$log->err($e);
		}
	}
	
	function downloadAction()
	{
		set_time_limit(0);
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$backup = new Precurio_Backup();
		$backup->download($this->getRequest()->getParam('id'));
	}
	public function deleteAction()
	{
		$ids = $this->getRequest()->getParam("ids");
		
		$backup = new Precurio_Backup();
		foreach($ids as $id)
		{
			$backup->delete($id);
		}
		
		return $this->_forward('index');
	}
	public function restoreAction()
	{
		$this->_helper->layout->disableLayout();
		$this->view->id = $this->getRequest()->getParam('id');
	}
	public function dorestoreAction()
	{
		Precurio_Session::getLicense()->proFeature();
		set_time_limit(0);
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		try
		{
			$backup = new Precurio_Backup();
			$backup->doRestore($this->getRequest()->getParam('id'));
			
			echo $this->translate("Restore operation was successful");
		}
		catch(Exception $e)
		{
			echo $this->translate("Restore operation was not successful");
			$log = Zend_Registry::get('log');
			$log->err($e);
		}
	}
	function getPageTitle() {
		return $this->translate("Backup").' / '.$this->translate("Restore");
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ADMIN);
	}
	public function translate($str)
	{
		return $this->view->translate($str);
	}
}
?>