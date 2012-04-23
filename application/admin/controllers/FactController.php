<?php

/**
 * FactController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('cms/models/MyContents.php');
require_once ('admin/controllers/BaseController.php');

class Admin_FactController extends Admin_BaseController {
	/**
	 * The default action - show the home page
	 */
	
	public function getPageTitle()
	{
		return $this->translate("Facts");
	}
	public function getTableName()
	{
		return PrecurioTableConstants::FACTS;
	}
	public function generateHeader()
	{
		return array(" ",$this->translate("Title"),$this->translate("Url"));
	}
	public function generateList($searchText)
	{
		$myContents = new MyContents();
		$facts = $myContents->getFacts();
		
		$arr = array();
		$i = 1;
		foreach($facts as $fact)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($fact->title,$searchText)===FALSE)
				{
					if(stripos($fact->url,$searchText)===FALSE)
					{
						continue;
					}
				}
				
			}
			
			$arr[] = array($i++,'title'=>$fact->title,'url'=>$fact->url,'id'=>$fact->id);
		}
		return $arr;
	}
	
	
	public function getForm($fact = null,$viewMode = false)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/fact/submit')
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
				'value'=>$fact['id'],
				));
		$form->addElement('hidden', 'user_id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$fact == null ? Precurio_Session::getCurrentUserId() : $fact['user_id'],
				));
		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$fact == null ? Precurio_Date::now()->getTimestamp() : $fact['date_created'],
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
				'value'=>$fact['title']
				));
		$form->addElement('text', 'url', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('URL'),
				'value'=>$fact['url']
				));
		if($viewMode)
		{
			$form->addElement('text', 'user', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Added By'),
				'readOnly'=>'readOnly',
				'value'=>UserUtil::getUser($fact['user_id'])
				));
				
			$form->addElement('text', 'date', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Date Added'),
				'readOnly'=>'readOnly',
				'value'=>new Precurio_Date($fact['date_created'])
				));
		}
		
		if(!$viewMode)
			$form->addElement('submit', 'submit', array(
					'class'=>'standout',
					'label'=>$this->translate('Submit'),
					));
		
			
			
		return $form;
	}

}
?>