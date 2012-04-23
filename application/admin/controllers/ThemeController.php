<?php

/**
 * ThemeController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Admin_ThemeController extends Zend_Controller_Action {
	
	public function indexAction() {
		$searchText = $this->getRequest()->getParam('search','');
		
		$this->view->searchText = $searchText;
		$this->view->list =  $this->generateList($searchText);
		$this->view->header = array("",$this->translate("Name"), $this->translate("Author"), $this->translate("Version"));
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->pathToController = '/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/';
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
	}
	function generateList($searchText) {
		$themes = new Precurio_Themes();
		$items = $themes->getAll();
		$arr = array();
		
		$i = 1;
		foreach($items as $theme)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($theme['theme'],$searchText)===FALSE)
				{
					if(stripos($theme['author'],$searchText)===FALSE)
					{
						continue;
					}
				}
			}
			
			$name = $theme['theme'];
			if($name == $themes->getDefaultTheme()) $name .= ' ('.$this->translate('Active').')' ;
			$arr[] = array($i,'name'=>$name,'author'=>$theme['author'],'version'=>$theme['version'],'id'=>$theme['theme']);
			++$i;
		}
		return $arr;
	}
	
	function newAction()
	{
		$this->_helper->layout->disableLayout();
	}
	
	public function deleteAction()
	{
		$ids = $this->getRequest()->getParam("ids");
		
		$themes = new Precurio_themes();
		foreach($ids as $id)
		{
			$themes->delete($id);
		}
		
		return $this->_redirect('/admin/theme');
	}
	public function activateAction()
	{
		$id = $this->getRequest()->getParam("id");
		
		$themes = new Precurio_themes();
		$themes->setDefaultTheme($id);
		
		return $this->_redirect('/admin/theme');
	}
	
	public function installAction()
	{
		set_time_limit(0);
		
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		$themes = new Precurio_themes();
		$themes->install();
	}
	public function viewAction()
	{
		return $this->_redirect('/admin/style/index/id/'.$this->getRequest()->getParam("id"));
	}
	
	function getPageTitle() {
		return $this->translate('Themes');
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