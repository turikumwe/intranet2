<?php

/**
 * StyleController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Admin_StyleController extends Zend_Controller_Action {
	private $theme;
	public function indexAction() 
	{
		$searchText = $this->getRequest()->getParam('search','');
		$this->view->searchText = $searchText;
		
		$theme = $this->getRequest()->getParam('id','');
		$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_USER);
		if($theme == '')
		{
			$theme = $ns->theme;
		}
		{
			$ns->theme = $theme;
		}
		$ns->setExpirationSeconds(600,'theme');//expire in 10mins
		
		$this->theme = $theme;
		
		$themes = new Precurio_Themes();
		$items = $themes->getThemeStyles($theme);
		$arr = array();
		
		$i = 1;
		foreach($items as $key=>$style)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($style,$searchText)===FALSE)
				{
					continue;
				}
			}
			if($style == $themes->getDefaultThemeStyle($this->theme))$style .=' ('.$this->translate('Default').')'; 
			$arr[] = array($i,'name'=>$style,'id'=>$key);
			++$i;
		}		
		
		
		$this->view->theme = $theme;
		$this->view->list =  $arr;
		$this->view->header = array("",$this->translate("Name"));
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->pathToController = '/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/';
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
	}
	public function deleteAction()
	{
		$ids = $this->getRequest()->getParam("ids");
		
		$themes = new Precurio_themes();
		foreach($ids as $id)
		{
			$themes->deleteStyle($this->theme,$id);
		}
		
		return $this->_redirect('/admin/style');
	}
	public function defaultAction()
	{
		$id = $this->getRequest()->getParam("id");
		
		$themes = new Precurio_themes();
		$themes->setDefaultThemeStyle($this->theme,$id);

		return $this->_redirect('/admin/style');
	}
	function newAction()
	{
		$this->_helper->layout->disableLayout();
		$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_USER);
		$this->view->theme = $ns->theme;
	}
	public function installAction()
	{
		set_time_limit(0);
		
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$themes = new Precurio_themes();
		$themes->installStyle($this->theme);
	}
	function getPageTitle() {
		return $this->translate('Theme')." ($this->theme)";
	}
	public function preDispatch()
	{
		$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_USER);
		if(!isset($ns->theme))
		{
			if($this->getRequest()->getActionName() != 'index')
			{
				return $this->_redirect('/admin/theme/');
			}
		}
		else
		{
			$this->theme = $ns->theme;
		}
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ADMIN);
	}
	public function translate($str)
	{
		return $this->view->translate($str);
	}

}
?>