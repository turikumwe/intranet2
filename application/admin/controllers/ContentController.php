<?php
require_once ('user/models/UserUtil.php');
require_once ('admin/controllers/BaseController.php');
class Admin_ContentController extends Admin_BaseController {
	
	function generateHeader() {
		return array('',$this->translate('User'),$this->translate('Featured Articles'),$this->translate('Adverts'),$this->translate('News'),$this->translate('Group Contents'));
	}
	
	function generateList($searchText) {
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::CONTENT_APPROVAL));
		$items = $table->fetchAll($table->select()->where('active=1'));
		
		$arr = array();
		$i = 1;
		foreach($items as $item)
		{
			$user = UserUtil::getUser($item->user_id);
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($user->getFullName(),$searchText)===FALSE)
				{
					continue;
				}
				
			}
			$arr[] = array($i++,'user'=>$user->getFullName(),'featured'=>$item->featured ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'advert'=>$item->advert ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'news'=>$item->news ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'group_conent'=>$item->group_content ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'id'=>$item->id);
		}
		return $arr;
	}
	function postSubmit($params)
	{
		//here we insert into group_settings table
		if($params['group_id']!=0)
		{
			$types = array('featured'=>1,'advert'=>1,'news'=>1);
			$arr = array_intersect_key($params,$types);
			
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUP_SETTINGS));
			$groupSetting = $table->fetchRow($table->select()->where('group_id = ?',$params['group_id']));
			if($groupSetting)
			{
				foreach($arr as $key=>$value)
				{
					if($value)
						$groupSetting[$key] = $value;
				}
				$groupSetting->save();
					
			}
			else
			{
				$table->insert(array(
						'group_id'=>$params['group_id'],
						'content_requires_approval'=>1,
						'featured'=>$arr['featured'],
						'advert'=>$arr['advert'],
						'news'=>$arr['news'],
						'date_created'=>Precurio_Date::now()->getTimestamp()
					));
			}
		}
	}
	function getForm($item = null, $viewMode = false) 
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/content/submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
			
		$userUtil = new UserUtil();
			
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


		$user_id = new Zend_Form_Element_Select('user_id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'User'));
		$select = $table->select()->where('active=1')->order('first_name asc');
		$users = $table->fetchAll($select);
			
		foreach($users as $user)
		{
			$user_id->addMultiOption($user->getId(),$user->getFullName());
		}
		$user_id->setValue($item['user_id']);
		$user_id->setLabel($this->translate('User'));
		$form->addElement($user_id);
		
		
		
		$form->addElement('checkbox', 'featured', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Featured Articles'),
				'value'=>$item == null ? 0 : $item['featured'],
				));
		$form->addElement('checkbox', 'advert', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Adverts'),
				'value'=>$item == null ? 0 : $item['advert'],
				));
		$form->addElement('checkbox', 'news', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('News'),
				'value'=>$item == null ? 0 : $item['news'],
				));
		$form->addElement('checkbox', 'group_content', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Group Contents'),
				'value'=>$item == null ? 0 : $item['group_content'],
				));

		$group_id = new Zend_Form_Element_Select('group_id');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::GROUPS));
		$select = $table->select()->where('active=1')->order('title');
		$groups = $table->fetchAll($select);
		$group_id->addMultiOption(0,'');	
		foreach($groups as $group)
		{
			$group_id->addMultiOption($group->id,$group->title);
		}
		$group_id->setValue($item['group_id']);
		$group_id->setLabel($this->translate('Select Group'));
		$form->addElement($group_id);
				
				
		if($viewMode && $item->group_content == 0)
		{
			$form->removeElement('group_content');
			$form->removeElement('group_id');
		}	
			
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	
	}
	
	function getPageTitle() {
		return $this->translate('Content Approval');
	}
	
	function getTableName() {
		return PrecurioTableConstants::CONTENT_APPROVAL;
	}
}

?>