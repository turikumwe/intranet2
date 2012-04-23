<?php

require_once ('admin/controllers/BaseController.php');

class Admin_BugController extends Admin_BaseController  {

	function generateHeader() {
		return array(" ",$this->translate("Report Type"),$this->translate("Component/Module"),$this->translate("Title"),$this->translate('Sent'));
	}
	public function generateList($searchText)
	{
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
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
			$tr = Zend_Registry::get('Zend_Translate');
			$arr[] = array($i++,'type'=>$item->type,'component'=>$item->component,'title'=>$item->title,'sent'=>$item->sent ? $this->translate(PrecurioStrings::YES) : $this->translate(PrecurioStrings::NO),'id'=>$item->id);
		}
		return $arr;
	}

	public function getForm($item = null,$viewMode = false)
	{
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/admin/bug/submit')
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
				
		$type = new Zend_Form_Element_Select('type');
		$type->addMultiOption('',$this->translate('Please Select'));
		$type->addMultiOption('Bug Report',$this->translate('Bug Report'));
		$type->addMultiOption('Feature Request',$this->translate('Feature Request'));
		$type->addMultiOption('Enhancement',$this->translate('Enhancement'));
		$type->setValue($item['type']);

		$type->setLabel($this->translate('Report Type'));
		$type->setRequired(true);
		$form->addElement($type);
		
		
		$component = new Zend_Form_Element_Select('component');
		$component->addMultiOption('',$this->translate('Please Select'));
		$component->addMultiOption('Opinion Poll',$this->translate('Opinion Poll'));
		$component->addMultiOption('Company Links',$this->translate('Company Links'));
		$component->addMultiOption('User Profile',$this->translate('User Profile'));
		$component->addMultiOption('Employee Directory',$this->translate('Employee Directory'));
		$component->addMultiOption('Events',$this->translate('Events'));
		$component->addMultiOption('Contacts Manager',$this->translate('Contacts Manager'));
		$component->addMultiOption('Task Management',$this->translate('Task Management'));
		$component->addMultiOption('Intranet Content',$this->translate('Intranet Content'));
		$component->addMultiOption('News',$this->translate('News'));
		$component->addMultiOption('Others',$this->translate('Others'));

		$component->setLabel($this->translate('Component'));
		$component->setValue($item['component']);
		$component->setRequired(true);
		$form->addElement($component);
		
		$form->addElement('text', 'title', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Title'),
				'value'=>$item['title']
				));
		$form->addElement('textarea', 'description', array(
				'validators' => array(
				),
				'required' => true,
				'col'=>80,
				'rows'=>15,
				'label'=>$this->translate('Detailed Description'),
				'value'=>$item['description']
				));
		if($viewMode)
		{
			$form->addElement('text', 'user', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Added By'),
				'readOnly'=>'readOnly',
				'value'=>UserUtil::getUser($item['user_id'])
				));
				
			$form->addElement('text', 'date', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Date Added'),
				'readOnly'=>'readOnly',
				'value'=>new Precurio_Date($item['date_created'])
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
		return $this->translate("Bug Report / Feature Request");
	}
	
	function getTableName() {
		return PrecurioTableConstants::BUGS;	
	}
	
	public function resendAction()
	{
		$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
		$row = $table->find($this->getRequest()->getParam('id'))->current();
		$this->postSubmit($row);
		return $this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/');
	}
	
	public function postSubmit($values)
	{
		$config = Zend_Registry::get('config');
		

		$user = UserUtil::getUser($values['user_id']);
		$userInfo = $user->getFullName()." (".$user->getEmail().")" ;
		
		$msg = "<h5>A new isse has been posted from $config->company_name</h5>
    	<br/>Issue details<br/>=====================================<br/>
    	Type : {$values['type']} <br/>
    	Component : {$values['component']} <br/>
    	Title : {$values['title']} <br/>
    	Description : {$values['description']} <br/>
    	Reported By : {$userInfo} <br/>
    	<br/>Technical Information<br/>=====================================<br/>
    	".serialize($config->toArray());
    	
		$conf = array();
		if($config->mail->use_auth)
		{
			$conf = array('auth' => $config->mail->auth,
			'username' => $config->mail->username,
			'password' => $config->mail->password);	
			if($config->mail->use_secure)
				$conf['ssl'] = $config->mail->ssl;
		}
		else
		{
			$conf = array();
		}
		
		$conf['port'] = $config->mail->port;
    	$tr = new Zend_Mail_Transport_Smtp($config->mail->server,$conf);
		
		$mail = new Zend_Mail();
		$mail->addTo('support@kleindevort.com','Support Team');
    	$mail->setBodyHtml($msg);
    	$mail->setFrom($user->getFullName(),$user->getEmail());
		$mail->setSubject('New issue report from '.$config->company_name.'. Issue ID #'.$values['id']);
		try
		{
			$mail->send($tr);	
			$table = new Zend_Db_Table(array('name'=>$this->getTableName()));
			$row = $table->find($values['id'])->current();
			$row->sent = 1;
			$row->save();
		}
		catch(Exception $e)
		{
			$this->_helper->FlashMessenger($this->translate('Your mail server settings may be incorrect.'));
			$this->_helper->FlashMessenger($this->translate('Error Message').':');
			$this->_helper->FlashMessenger($e->getMessage());
			$baseUrl = $this->getRequest()->getBaseUrl();
			$this->_helper->FlashMessenger("<a href='$baseUrl/admin/setting/mail'>".$this->translate('Click here')."</a> ".$this->translate("to change your mail settings"));
		}
	}
}
?>