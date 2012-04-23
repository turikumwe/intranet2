<?php

/**
 * UpgradeController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Admin_UpgradeController extends Zend_Controller_Action {

	public function indexAction() {
	}
	public function upgradeAction()
	{
		set_time_limit(0);
		$upgrade = new Precurio_Upgrade();
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
		
		$target_dir = $upgrade->checkDir('/uploads/tmp/'); 
		if(!($_FILES['file']['size']))
		{
			echo ($this->translate('No file selected'));
			return;
		}
		$file = $_FILES['file'];
		//first handle file upload
		if (is_uploaded_file($file['tmp_name'])) 
		{
	    	$filename = $file['name'];
			$basefilename = preg_replace("/(.*)\.([^.]+)$/","\\1", $filename);
			$ext = preg_replace("/.*\.([^.]+)$/","\\1", $filename);
			if(strtolower($ext) !== 'zip')
			{
				echo ($this->translate('Incorrect file format'));
				return;
			}
			if (!move_uploaded_file($file['tmp_name'], $target_dir.'/'.$filename)) 
			{
			    echo($this->translate('Cannot upload file'));
			    return;
			}
			$upgradeFile = ($target_dir.'/'.$filename);	
		}
		$upgrade->upgrade($upgradeFile);

	}
	public function preDispatch()
	{
		$this->_helper->layout->disableLayout();
		//$this->_helper->viewRenderer->setNoRender();
	}
public function translate($str)
	{
		return $this->view->translate($str);
	}
}
?>