<?php
require_once ('user/models/UserUtil.php');
require_once ('admin/controllers/BaseController.php');
class Admin_GroupController extends Admin_BaseController {
	
	function generateHeader() {
		return array('',$this->translate('Title'),$this->translate('Description'),$this->translate('Is Location'),$this->translate('Is Department'),$this->translate('Is Role'));
	}
	
	function generateList($searchText) 
	{
		$userUtil = new UserUtil();
		$items = $userUtil->getGroups();
		
		$arr = array();
		$i = 1;
		foreach($items as $item)
		{
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
			
			$arr[] = array($i++,'title'=>$item->title,'description'=>$item->description,'is_location'=>$item->is_location ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'is_department'=>$item->is_department ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'is_role'=>$item->is_role ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'id'=>$item->id);
		}
		return $arr;
	}
	public function deleteAction()
	{
//		if($this->getRequest()->getControllerName() != 'group')
//		{
//			parent::deleteAction();
//			return;
//		}
		
		$ids = $this->getRequest()->getParam("ids");
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS,'rowClass'=>'Group'));
		$items = $table->find($ids);
		
		foreach($items as $obj)
		{
			switch ($this->getRequest()->getControllerName())//perform some sort of polymorphism
			{
				case 'group':
					$obj->deactivate();//this will also deactivate corresponding roles, departments or locations
					break;
				case 'role':
					$obj->deactivateRole();
					break;
				case 'location':
					$obj->deactivateLocation();
					break;
				case 'department':
					$obj->deactivateDepartment();
					break;
			}
		}
		
		return $this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/');
	}
	function getForm($item = null, $viewMode = false) 
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/group/submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		
			
		$form->addElement('hidden', 'id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$item['id'],
				));

		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$item == null ? Precurio_Date::now()->getTimestamp() : $item['date_created'],
				));
		$form->addElement('hidden', 'active', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>1,
				));
				
				
		$form->addElement('text', 'title', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Title'),
				'value'=>$item['title']
				));
		$form->addElement('text', 'description', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Description'),
				'value'=>$item['description']
				));
		
		$form->addElement('checkbox', 'is_location', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Is Location'),
				'value'=>$item == null ? 0 : $item['is_location'],
				));
		$form->addElement('checkbox', 'is_department', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Is Department'),
				'value'=>$item == null ? 0 : $item['is_department'],
				));
		$form->addElement('checkbox', 'is_role', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Is Role'),
				'value'=>$item == null ? 0 : $item['is_role'],
				));

		$userUtil = new UserUtil();
		$form->addElement('select', 'parent_id', array(
				'required' => false,
				'label' => $this->translate('Parent Group'),
				'multiOptions'=> Precurio_FormElement::getOptionsArray($userUtil->getGroups(),'id','title',1),
		        'value'=>$item['parent_id']
				));
		if($viewMode && $item->parent_id == 0)
			$form->removeElement('parent_id');

		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	}
	
	function getPageTitle() {
		return $this->translate('Groups');
	}
	
	function getTableName() {
		return PrecurioTableConstants::GROUPS;
	}
	public function editAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item);
		$this->view->pageTitle = $this->getPageTitle() ." : ". $this->translate("Edit");
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		
		$ns = new Zend_Session_Namespace('temp');
		$ns->callback = $this->getRequest()->getBaseUrl()."/admin/group/members/f/add/g_id/{$id}/id/0/t/u";//needed for the user select dialog
		$ns->selectedUsers = array();//clear the user select dialog.
		$ns->selectLabel = $this->translate('New Members');
		$ns->id = $id;
		$this->render('view');
	}
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$item = $table->find($id)->current();
		
		if($item == null)return $this->_forward('index');
		
		$this->view->form = $this->getForm($item,true);
		$this->view->pageTitle = $this->view->translate($this->getPageTitle());
		$this->view->cpage = $this->getRequest()->getParam('cpage',1);
		$this->view->searchText = $this->getRequest()->getParam('search','');
		
		$ns = new Zend_Session_Namespace('temp');
		$ns->callback = $this->getRequest()->getBaseUrl()."/admin/group/members/f/add/g_id/{$id}/id/0/t/u";//needed for the user select dialog
		$ns->selectedUsers = array();//clear the user select dialog.
		$ns->selectLabel = $this->translate('New Members');
		$ns->id = $id;
	}
	function postSubmit($params)
	{
		$data = array(
				'title'=>$params['title'],
				'group_id'=>$params['id'],
				'date_created'=>$params['date_created'],
				'active'=>1
				);
		if(isset($params['is_location']))
		{
			try
			{
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::LOCATIONS));
				if(isset($params['editop']))
				{
					$row = $table->fetchRow($table->select()->where('group_id = ?',$params['id']));
					if(Precurio_Utils::isNull($row))
					{
						if($params['is_location'])
							$row = $table->createRow();
						else
							throw new Exception('Null');
					}
					$row->setFromArray($data);
				}
				else
				{
				 	if($params['is_location'])$row = $table->createRow($data);
				}
				if($row)$row->save();
			}
			catch (Exception $e)
			{
				
			}
		}
		
		if(isset($params['is_department']))
		{
			try
			{
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::DEPARTMENTS));
				if(isset($params['editop']))
				{
					$row = $table->fetchRow($table->select()->where('group_id = ?',$params['id']));
					if(Precurio_Utils::isNull($row))
					{
						if($params['is_department'])
							$row = $table->createRow();
						else
							throw new Exception('Null');
					}
					$row->setFromArray($data);
				}
				else
				{
				 	if($params['is_department'])$row = $table->createRow($data);
				}
				if($row)$row->save();
			}
			catch (Exception $e)
			{
				
			}
		}
		if(isset($params['is_role']))
		{
			try
			{
				
				$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
				if(isset($params['editop']))
				{
					$row = $table->fetchRow($table->select()->where('group_id = ?',$params['id']));
					if(Precurio_Utils::isNull($row))
					{
						if($params['is_role'])
							$row = $table->createRow();
						else
							throw new Exception('Null');
					}
					$row->setFromArray($data);
				}
				else
				{
				 	if($params['is_role'])$row = $table->createRow($data);
				}
				if($row)$row->save();
			}
			catch (Exception $e)
			{
				
			}
		}

	}
	public function membersAction()
	{
		//$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$params = $this->getRequest()->getParams();
		
		$group_id = $params['g_id'];
		$fn = $params['f'];
		
		$group = UserUtil::getGroup($group_id);
		
		$id = $params['id'];
		$type = $params['t'];
		
		switch($fn)
		{
			case 'remove':
				if($type === 'u')
					$group->removeUserMember($id);
				if($type === 'g')
					$group->removeGroupMember($id);
				break;
			case 'add':
				$this->_helper->layout->disableLayout();
				$str = $this->getRequest()->getParam('users');
				$users = explode(",",$str);
				array_shift($users);
				foreach($users as $id)
				{
					$group->addUserMember($id);
				}
				echo "location.reload()";
				return;
				break;
				
		}
		return $this->refreshView($group_id);
	}
	protected function refreshView($id)
	{
		$this->_redirect('/admin/group/view/id/'.$id);
		//$this->_forward('view','group','admin',array('id'=>$id));
	}
	
}

?>