<?php
require_once ('admin/controllers/GroupController.php');
class Admin_RoleController extends Admin_GroupController {
	
	function generateHeader() {
		return array('',$this->translate('Title'),$this->translate('Description'));
	}

	function generateList($searchText) 
	{
		$userUtil = new UserUtil();
		$items = $userUtil->getGroups();
		
		$arr = array();
		$i = 1;
		foreach($items as $item)
		{
			if(!$item->is_role)continue;
			
			$role = $item->getRole();
			if(!$role->active)continue;
			
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($item->title,$searchText)===FALSE)
				{
					if(stripos($item->description,$searchText)===FALSE)
					{
						continue;
					}
				}
				
			}
			
			$arr[] = array($i++,'title'=>$item->title,'description'=>$item->description,'id'=>$item->id);
		}
		return $arr;
	}
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item);
		$this->view->pageTitle = $this->getPageTitle();
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		
		$ns = new Zend_Session_Namespace('temp');
		$ns->callback = $this->getRequest()->getBaseUrl()."/admin/group/members/f/add/g_id/{$id}/id/0/t/u";//needed for the user select dialog
		$ns->selectedUsers = array();//clear the user select dialog.
		$ns->selectLabel = $this->translate('New Members');
		$ns->id = $id;
	}
	function getForm($item = null, $viewMode = false) {
		$form = parent::getForm($item,$viewMode);
		$form->removeElement('is_location');
		$form->removeElement('is_department');
		$form->removeElement('is_role');
		
		$form->addElement('hidden', 'is_role', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'value'=>1,
				));
				
		$form->addElement('hidden', 'from_role', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'value'=>1,
				));
				
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/role/submit');	
		return $form;
	}
	
	function getPageTitle() {
	 return $this->translate("Roles");
	}
	
	function getTableName() {
		return PrecurioTableConstants::GROUPS;
	}


}

?>