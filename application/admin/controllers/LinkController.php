<?php
require_once ('cms/models/MyContents.php');
require_once ('admin/controllers/BaseController.php');
class Admin_LinkController extends Admin_BaseController {
	
	function generateHeader() {
		return array(" ",$this->translate("Title"),$this->translate("Url"));
	}
	
	public function generateList($searchText)
	{
		$myContents = new MyContents();
		$links = $myContents->getLinks();
		
		$arr = array();
		$i = 1;
		foreach($links as $link)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($link->title,$searchText)===FALSE)
				{
					if(stripos($link->url,$searchText)===FALSE)
					{
						continue;
					}
				}
				
			}
			
			$arr[] = array($i++,'title'=>$link->title,'url'=>$link->url,'id'=>$link->id);
		}
		return $arr;
	}
	
	
	public function getForm($link = null,$viewMode = false)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/link/submit')
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
				'value'=>$link['id'],
				));
		$form->addElement('hidden', 'user_id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$link == null ? Precurio_Session::getCurrentUserId() : $link['user_id'],
				));
		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$link == null ? Precurio_Date::now()->getTimestamp() : $link['date_created'],
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
				'value'=>$link['title']
				));
		$form->addElement('text', 'url', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('URL'),
				'value'=>$link['url']
				));
		if($viewMode)
		{
			$form->addElement('text', 'user', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Added By'),
				'readOnly'=>'readOnly',
				'value'=>UserUtil::getUser($link['user_id'])
				));
				
			$form->addElement('text', 'date', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Date Added'),
				'readOnly'=>'readOnly',
				'value'=>new Precurio_Date($link['date_created'])
				));
		}
		
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
			
		return $form;
	}
	
	function getPageTitle() {
		return $this->translate("Links");
	}
	
	function getTableName() {
		return PrecurioTableConstants::LINKS;	
	}
}

?>