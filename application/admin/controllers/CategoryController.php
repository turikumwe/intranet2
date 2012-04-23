<?php 
require_once ('admin/controllers/BaseController.php');
require_once ('cms/models/vo/Content.php');
require_once ('cms/models/vo/Category.php');
class Admin_CategoryController extends Admin_BaseController {
	
	public function generateList($searchText)
	{
		$table = new Zend_Db_Table(array('name'=>$this->getTableName(),'rowClass'=>'Category'));
		$items = $table->fetchAll('active = 1','date_created desc');
		
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
			
			$arr[] = array($i++,'title'=>$item->title,'description'=>$item->description,'user'=>$item->getOwnersName(),'access_type'=>$item->getAccessType(),'date'=> $item->getDateCreated() ,'id'=>$item->id);
		}
		return $arr;
	}
	public function getTableName()
	{
		return PrecurioTableConstants::CATEGORYS;
	}
	public function generateHeader()
	{
		return array(" ",$this->translate("Name"),$this->translate("Description"),$this->translate("Created By"),$this->translate("Access Type"),$this->translate("Date Created"));
	}
	public function getPageTitle()
	{
		return $this->translate("Content Category(s)");
	}
	public function viewAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$this->view->category = Category::getCategory($id);
		$this->view->pageTitle = $this->getPageTitle();
	}
	public function deleteAction()
	{
		$ids = $this->getRequest()->getParam("ids");
		$f = $this->getRequest()->getParam("f",0); //$f=>force delete
		$table = new Zend_Db_Table(array('name'=>$this->getTableName(),'rowClass'=>'Category'));
		$items = $table->find($ids);
		
		$nonEmpty = array();//should contain categorys that are not empty
		//first loop through each category to confirm they don't contain any data
		foreach($items as $obj)
		{
			if(count($obj->getContentChildren())>0 || count($obj->getCategoryChildren())>0)
			{
				$nonEmpty[] = $obj;
			}
		}
		//if there is a category that is not empty, and user has not forced delete, warn the user
		if(count($nonEmpty) > 0 && !$f)
		{
			$this->view->nonEmptyCategorys = $nonEmpty;
			$this->view->ids = $ids; //for reposting
			$this->view->pageTitle = $this->getPageTitle();
			return;
		}
		//ok, we are clear to delete.
		foreach($items as $obj)
		{
			$obj->deActivateCategory();
		}
		return $this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/');
	}
	public function removeAction()
	{
		$content_id = $this->getRequest()->getParam('c_id');
		$category_id = $this->getRequest()->getParam('id');
		
		$category = Category::getCategory($category_id);
		$category->removeContents($content_id);
		
		$this->_redirect('admin/category/view/id/'.$category_id);
	}
	public function getForm($item = null,$viewMode = false)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/category/submit')
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
				'label'=>$this->translate('Name'),
				'value'=>$item['title']
				));
		$form->addElement('textarea', 'description', array(
				'validators' => array(
				),
				'required' => false,
				'col'=>80,
				'rows'=>4,
				'label'=>$this->translate('Description'),
				'value'=>$item['description']
				));
				
		$parent_id = new Zend_Form_Element_Select('parent_id');
		$parent_id->addMultiOption(0,'['.$this->translate('None').']');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CATEGORYS));
		$select = $table->select()->where('active=1')->order('title asc');
		$allCategorys = $table->fetchAll($select);
			
		foreach($allCategorys as $category)
		{
			$parent_id->addMultiOption($category->id,$category->title);
		}
		$parent_id->setLabel($this->translate('Parent Category'));
		$parent_id->setValue($item['parent_id']);
		$form->addElement($parent_id);
		
		$form->addElement('select', 'access_type', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Access Type'),
				'value'=>$item['access_type'],
				'multiOptions'=>array(
					Category::ACCESS_PUBLIC=>Category::getAccessStr(Category::ACCESS_PUBLIC),
					Category::ACCESS_PRIVATE=>Category::getAccessStr(Category::ACCESS_PRIVATE),
					Category::ACCESS_SHARED=>Category::getAccessStr(Category::ACCESS_SHARED)
				)));		
		if($viewMode)
		{
			$form->addElement('text', 'user', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Created By'),
				'readOnly'=>'readOnly',
				'value'=>UserUtil::getUser($item['user_id'])
				));
				
			$form->addElement('text', 'date', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Date Created'),
				'readOnly'=>'readOnly',
				'value'=>new Precurio_Date($item['date_created'])
				));
		}
		else
		{
			$user_id = new Zend_Form_Element_Select('user_id');
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
			$select = $table->select()->where('active=1')->order('first_name asc');
			$users = $table->fetchAll($select);
				
			foreach($users as $user)
			{
				$user_id->addMultiOption($user->getId(),$user->getFullName());
			}
			$user_id->setValue($item == null ? Precurio_Session::getCurrentUserId() : $item['user_id']);
			$user_id->setLabel($this->translate('Created By'));
			$form->addElement($user_id);
		}
		
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
			
		return $form;
		
	}
	function postSubmit($params)
	{
		if(empty($params['editop']))//'editop is always set if it was an edit operation'
		{
			
		}
	}
	public function addcontentAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$this->view->category = Category::getCategory($id);
		$this->_helper->layout->disableLayout();
	}
	public function submitaddcontentAction()
	{
		$ids = $this->getRequest()->getParam('ids');
		$category_id = $this->getRequest()->getParam('category_id');
		
		$category = Category::getCategory($category_id);
		$category->addContents($ids);
		$this->_redirect('admin/category/view/id/'.$category_id);
	}
	public function groupsAction()
	{
		$this->view->category_id = $this->getRequest()->getParam('id');
		$this->view->searchText = $this->getRequest()->getParam('search','');
	}
	public function addgroupAction()
	{
		$id = $this->getRequest()->getParam('id',0);
		
		$this->view->category = Category::getCategory($id);
		$this->_helper->layout->disableLayout();
	}
	public function submitaddgroupAction()
	{
		$ids = $this->getRequest()->getParam('ids');
		$category_id = $this->getRequest()->getParam('category_id');
		
		$category = Category::getCategory($category_id);
		$category->addGroups($ids);
		$this->_redirect('admin/category/view/id/'.$category_id);
	}
	public function removegroupAction()
	{
		$group_id = $this->getRequest()->getParam('g_id');
		$category_id = $this->getRequest()->getParam('id');
		
		$category = Category::getCategory($category_id);
		$category->removeGroups($group_id);
		
		$this->_redirect('admin/category/view/id/'.$category_id);
	}
}

?>