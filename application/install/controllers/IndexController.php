<?php


class Install_IndexController extends Zend_Controller_Action {

	public function indexAction() 
	{
		if(!Precurio_Utils::isUpgradeInstallation())
		{
			$this->_redirect('/install/install');
		}
	}
	public function preDispatch()
	{
		$this->_helper->layout->disableLayout();
	}
}
?>