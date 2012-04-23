<?php
require_once ('admin/controllers/BaseController.php');
class Admin_NotificationController extends Admin_BaseController {
	
	function generateHeader() {
		return array('',$this->translate('Type'),$this->translate('Feed'),$this->translate('Email'));
	}
	private function getTypeDesc($type)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NOTIFICATION_TYPE));
		$row = $table->fetchRow($table->select()->where('type = ?',$type));
		return $row->type_desc;
	}
	function generateList($searchText) {
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NOTIFICATION_RULE));
		$items = $table->fetchAll($table->select()->where('active=1'));
		
		$arr = array();
		$i = 1;
		foreach($items as $item)
		{
			$type_desc = $this->getTypeDesc($item->type);
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($type_dec,$searchText)===FALSE)
				{
					continue;
				}
				
			}
			
			$arr[] = array($i++,'type'=>$type_desc,'feed'=>$item->feed ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'email'=>$item->email ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'id'=>$item->id);
		}
		return $arr;
	}
	
	function getForm($item = null, $viewMode = false) 
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/notification/submit')
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
		$form->addElement('hidden', 'user_id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$item == null ? Precurio_Session::getCurrentUserId() : $item['user_id'],
				));		
				
		$type = new Zend_Form_Element_Select('type');
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::NOTIFICATION_TYPE));
		$types = $table->fetchAll($table->select()->where('id>0')->order('type_desc'));
		foreach($types as $aType)
		{
			$type->addMultiOption($aType->type,$aType->type_desc);
		}
		$type->setLabel($this->translate('Notification type'));
		$type->setValue($item['type']);
		$type->setRequired(true);
		$form->addElement($type);

			
		$form->addElement('checkbox', 'feed', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('By Feeds'),
				'value'=>$item == null ? 0 : $item['feed'],
				));
		$form->addElement('checkbox', 'email', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('By Emails'),
				'value'=>$item == null ? 0 : $item['email'],
				));
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
		return $form;
	}
	
	function getPageTitle() {
		return $this->translate("Notification Settings");
	}
	
	function getTableName() {
		return PrecurioTableConstants::NOTIFICATION_RULE;
	}
}

?>