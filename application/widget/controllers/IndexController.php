<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Widget_IndexController extends Zend_Controller_Action {

	public function indexAction() {
		$this->render();
	}
	public function updateAction() {
		$params = array_diff($this->getRequest()->getParams(),$this->getRequest()->getUserParams());
		$userSetting = UserUtil::getUser()->getSettings();
		$name  = $this->getRequest()->getParam('name');
		unset($params['name']);
		$userSetting->updateWidget($name,$params);
		unset($userSetting);
		unset($params);
	}
	
	public function closeAction()
	{
		$name  = $this->getRequest()->getParam('name');
		$userSetting = UserUtil::getUser()->getSettings();
		$userSetting->removeWidget($name);
	}
	
	public function configureAction()
	{
		$name  = $this->getRequest()->getControllerName();
		$userSetting = UserUtil::getUser()->getSettings();
		$this->view->widget = $userSetting->getWidget($name);
		$this->renderScript('configure.phtml');
	}
	public function submitconfigAction()
	{
		$name  = $this->getRequest()->getControllerName();
		$formData = array_diff($this->getRequest()->getParams(),$this->getRequest()->getUserParams());
		if(isset($formData['submit_name']))unset($formData['submit']);
		if(isset($formData['widget_name']))unset($formData['widget_name']);
		$userSetting = UserUtil::getUser()->getSettings();
		$userSetting->updateWidget($name,$formData);
		return $this->_redirect('/index/home');
	}
	public function preDispatch()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
	}

}
?>

