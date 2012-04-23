<?php
require_once ('cms/models/MyContents.php');
require_once ('admin/controllers/BaseController.php');
class Admin_RssController extends Admin_BaseController {
	
	function generateHeader() {
		return array(" ",$this->translate("Title"),$this->translate("Url"));
	}
	
	public function generateList($searchText)
	{
		$myContents = new MyContents();
		$rsss = $myContents->getRss();
		
		$arr = array();
		$i = 1;
		foreach($rsss as $rss)
		{
			if(!Precurio_Utils::isNull($searchText))
			{
				if(stripos($rss->title,$searchText)===FALSE)
				{
					if(stripos($rss->url,$searchText)===FALSE)
					{
						continue;
					}
				}
				
			}
			
			$arr[] = array($i++,'title'=>$rss->title,'url'=>$rss->url,'id'=>$rss->id);
		}
		return $arr;
	}
	
	
	public function getForm($rss = null,$viewMode = false)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/rss/submit')
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
				'value'=>$rss['id'],
				));
		$form->addElement('hidden', 'user_id', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$rss == null ? Precurio_Session::getCurrentUserId() : $rss['user_id'],
				));
		$form->addElement('hidden', 'date_created', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper',
				'FormElements'
				),
				'required' => false,
				'value'=>$rss == null ? Precurio_Date::now()->getTimestamp() : $rss['date_created'],
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
				'value'=>$rss['title']
				));
		$form->addElement('text', 'url', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('URL'),
				'value'=>$rss['url']
				));
		
		if($viewMode)
		{
			$form->addElement('text', 'user', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Added By'),
				'readOnly'=>'readOnly',
				'value'=>UserUtil::getUser($rss['user_id'])
				));
				
			$form->addElement('text', 'date', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Date Added'),
				'readOnly'=>'readOnly',
				'value'=>new Precurio_Date($rss['date_created'])
				));
				
			$date = new Precurio_Date($rss['last_updated']);	
			$form->addElement('text', 'last', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Last Updated'),
				'readOnly'=>'readOnly',
				'value'=>$date
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
		return $this->translate("RSS Sources");
	}
	
	function getTableName() {
		return PrecurioTableConstants::RSS;	
	}
}

?>