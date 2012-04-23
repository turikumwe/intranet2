<?php

/**
 * SettingController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
class Admin_SettingController  extends Zend_Controller_Action {
	public function translate($str)
	{
		return $this->view->translate($str);
	}
	public function indexAction()
	{
		return $this->_forward('general');
	}
	public function submitAction()
	{
		$rootPath = Zend_Registry::get('root');
		$config = Zend_Registry::get('config');
		
		$params = $this->getRequest()->getParams();
		$section = $params['section'];

		$form = Precurio_Session::getCurrentForm();

		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			return $this->_forward($section,$this->getRequest()->getControllerName(),$this->getRequest()->getModuleName());
		}
		
		$values = $form->getValues();

		if($section == 'mysql')
		{
			$config = $this->setDatabaseConfig($config,$values);
		}
		else if($section == 'ldap')
		{
			$config = $this->setLdapConfig($config,$values);
		}
		else
		{
			$config = $this->setConfig($config,$values,$section);
		}
		
		$item = new Precurio_ConfigWriter(array('config'   => $config,
                                           'filename' => $rootPath.'/application/configs/precurio.ini'));
		
		$item->write();
		
		if(!empty($params['allow_anonymous_user']))
		{
			UserUtil::activateGuestUser();
		}
		
		$this->gotoPage($section);
	}
	protected function gotoPage($section)
	{
		$this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.$section);
	}
	/**
	 * @param $config Zend_Config_Ini
	 * @param $values Array
	 * @param $section string
	 * @return Zend_Config_Ini
	 */
	protected function setConfig($config,$values,$section)
	{
		foreach($values as $key=>$value)
		{
			if($section == NULL)
			{
				if(isset($config->{$key}))
					$config->{$key} = $value;
			}
			else
			{
				if(isset($config->{$section}->{$key}))
					$config->{$section}->{$key} = $value; 
			}
		}
		return $config;
	}
	/**
	 * This function specifically sets the database portion of the config file
	 * @param $config Zend_Config_Ini
	 * @param $values Array
	 * @return Zend_Config_Ini
	 */
	protected function setDatabaseConfig($config,$values)
	{
		foreach($values as $key=>$value)
		{
			if(isset($config->mysql->database->{$key}))
				$config->mysql->database->{$key} = $value;  
		}
		return $config;
	}
	/**
	 * This function specifically sets the ldap portion of the config file
	 * @param $config Zend_Config_Ini
	 * @param $values Array
	 * @return Zend_Config_Ini
	 */
	protected function setLdapConfig($config,$values)
	{
		foreach($values as $key=>$value)
		{
			if(isset($config->mysql->database->{$key}))
				$config->ldap->ldap->server1->{$key} = $value;  
		}
		return $config;
	}
	public function generalAction()
	{
		$config = Zend_Registry::get('config');
		$item = $config;
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.'submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
		
		$form->addElement('hidden', 'section', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>NULL
				));	
		
		$form->addElement('text', 'company_name', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Company Name'),
				'value'=>$item->get('company_name')
				));	
		$form->addElement('text', 'base_url', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Web URL'),
				'value'=>$item->get('base_url')
				));	
		$form->addElement('text', 'currency_symbol', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Currency Symbol'),
				'value'=>$item->get('currency_symbol')
				));	
				
		$auth_mech = new Zend_Form_Element_Select('auth_mech');
		$auth_mech->addMultiOption('DatabaseAuth',$this->translate('Database'));
		$auth_mech->addMultiOption('LdapAuth',$this->translate('LDAP (Active Directory)'));
		$auth_mech->setValue($item->get('auth_mech'));
		$auth_mech->setLabel($this->translate('Authentication Mechanism'));
		$form->addElement($auth_mech);
				
		$form->addElement('text', 'domain', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Domain'),
				'value'=>$item->get('domain')
				));
		$form->addElement('text', 'email_domain', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Email Domain'),
				'value'=>$item->get('email_domain')
				));
				
		$locale = new Zend_Form_Element_Select('default_locale');
		$languages = Bootstrap::getLanguages();
		foreach($languages as $lang)
		{
			$locale->addMultiOption($lang->getLocaleString(),$this->translate($lang->getLabel()));
		}
		$locale->setValue($item->get('default_locale'));
		$locale->setLabel($this->translate('Default Language'));
		$form->addElement($locale);
		
		$form->addElement('checkbox', 'content_requires_approval', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Content Requires Approval'),
				'value'=>$item->get('content_requires_approval')
				));	
		$form->addElement('checkbox', 'allow_anonymous_user', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Allow Guest Users'),
				'value'=>$item->get('allow_anonymous_user')
				));	
				
		$form->addElement('text', 'welcome_text', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Welcome Text'),
				'value'=>$item->get('welcome_text')
				));
				
				
		$this->view->form = $form;
		$this->render('index');
		Precurio_Session::setCurrentForm($form);
	}
	public function databaseAction()
	{
		if(Bootstrap::isHosted())
			$this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/module');
		$config = Zend_Registry::get('config');
		$item = $config;
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.'submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
		
		$form->addElement('hidden', 'section', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>'mysql'
				));	
		
		$form->addElement('text', 'host', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Host'),
				'value'=>$item->mysql->database->host
				));	
				
		$form->addElement('text', 'port', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Port'),
				'value'=>$item->mysql->database->port
				));	
				
		$form->addElement('text', 'username', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Username'),
				'value'=>$item->mysql->database->username
				));	
				
		$form->addElement('password', 'password', array(
				'validators' => array(
				),
				'required' => false,
				'renderPassword'=>true,
				'label'=>$this->translate('Password'),
				'value'=>$item->mysql->database->password
				));	
				
		$form->addElement('text', 'dbname', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Database'),
				'value'=>$item->mysql->database->dbname
				));	
				
		$this->view->form = $form;
		$this->render('index');
		Precurio_Session::setCurrentForm($form);
	}
	public function ldapAction()
	{
		$config = Zend_Registry::get('config');
		$item = $config;
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.'submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
		
		$form->addElement('hidden', 'section', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>'ldap'
				));	
		
		$form->addElement('text', 'host', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Host'),
				'value'=>$item->ldap->ldap->server1->host
				));	
				
		$form->addElement('text', 'port', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Port'),
				'value'=>$item->ldap->ldap->server1->port
				));	
				
		$useSsl = new Zend_Form_Element_Select('useSsl');
		$useSsl->addMultiOption(1,$this->translate(PrecurioStrings::YES));
		$useSsl->addMultiOption(0,$this->translate(PrecurioStrings::NO));
		$useSsl->setValue($item->ldap->ldap->server1->useSsl);
		$useSsl->setLabel($this->translate('Use SSL'));
		$useSsl->setRequired(false);
		//$useSsl->setAttrib('class','oneHalfSelect'); 
		$form->addElement($useSsl);
		
		$form->addElement('text', 'baseDn', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Base DN'),
				'value'=>$item->ldap->ldap->server1->baseDn
				));
		
		$form->addElement('text', 'accountDomainName', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Account Domain Name'),
				'value'=>$item->ldap->ldap->server1->accountDomainName
				));
		$form->addElement('text', 'username', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Username'),
				'value'=>$item->ldap->ldap->server1->username
				));	
				
		$form->addElement('password', 'password', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Password'),
				'renderPassword'=>true,
				'value'=>$item->ldap->ldap->server1->password
				));	
				
		$bindRequiresDn = new Zend_Form_Element_Select('bindRequiresDn');
		$bindRequiresDn->addMultiOption(1,$this->translate(PrecurioStrings::YES));
		$bindRequiresDn->addMultiOption(0,$this->translate(PrecurioStrings::NO));
		$bindRequiresDn->setValue($item->ldap->ldap->server1->bindRequiresDn);
		$bindRequiresDn->setLabel($this->translate('Bind Required DN'));
		//$useSsl->setAttrib('class','oneHalfSelect'); 
		$form->addElement($bindRequiresDn);
				
		$this->view->form = $form;
		$this->render('index');
		Precurio_Session::setCurrentForm($form);
	}
	public function liveAction()
	{
		if(Bootstrap::isHosted())
			$this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/module');
		$config = Zend_Registry::get('config');
		$item = $config;
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.'submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
		
		$form->addElement('hidden', 'section', array(
				'validators' => array(
				),
				'decorators'=>array(
				'ViewHelper'
				),
				'required' => false,
				'value'=>'live'
				));	
				
		$form->addElement('text', 'domain', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Host'),
				'value'=>$item->live->domain
				));	
				
		$form->addElement('text', 'service', array(
				'validators' => array(
				),
				'required' => true,
				'label'=>$this->translate('Service'),
				'value'=>$item->live->service
				));	
								
		$this->view->form = $form;
		$this->render('index');
		Precurio_Session::setCurrentForm($form);
	}
	public function mailAction()
	{
		if(Bootstrap::isHosted())
			$this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/module');
		$config = Zend_Registry::get('config');
		$item = $config;
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.'submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
		
		$form->addElement('hidden', 'section', array(
				'validators' => array(
				),
				'required' => false,
				'value'=>'mail'
				));	
		
		$form->addElement('text', 'server', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Server'),
				'value'=>$item->mail->server
				));	
				
		$form->addElement('text', 'port', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Port'),
				'value'=>$item->mail->port
				));	
				
		$form->addElement('text', 'admin_email', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Administrator Email'),
				'value'=>$item->mail->admin_email
				));		

		$form->addElement('text', 'admin_name', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate("Administrator's Full Name"),
				'value'=>$item->mail->admin_name
				));	
				
		$use_auth = new Zend_Form_Element_Select('use_auth');
		$use_auth->addMultiOption(1,$this->translate(PrecurioStrings::YES));
		$use_auth->addMultiOption(0,$this->translate(PrecurioStrings::NO));
		$use_auth->setValue($item->mail->use_auth);
		$use_auth->setLabel($this->translate('Use SMTP Authentication'));
		$use_auth->setRequired(false);
		$form->addElement($use_auth);

		$auth = new Zend_Form_Element_Select('auth');
		$auth->addMultiOption('login','LOGIN');
		$auth->addMultiOption('plain','PLAIN');
		$auth->addMultiOption('crammd5','CRAM-MD5');
		$auth->setValue($item->mail->auth);
		$auth->setLabel($this->translate('Authentication Type'));
		$auth->setRequired(false);
		$form->addElement($auth);
		
		$form->addElement('text', 'username', array(
				'validators' => array(
				),
				'required' => false,
				'label'=>$this->translate('Username'),
				'value'=>$item->mail->username
				));	
				
		$form->addElement('password', 'password', array(
				'validators' => array(
				),
				'required' => false,
				'renderPassword'=>true,
				'label'=>$this->translate('Password'),
				'value'=>$item->mail->password
				));	
				
		$use_secure = new Zend_Form_Element_Select('use_secure');
		$use_secure->addMultiOption(1,$this->translate(PrecurioStrings::YES));
		$use_secure->addMultiOption(0,$this->translate(PrecurioStrings::NO));
		$use_secure->setValue($item->mail->use_secure);
		$use_secure->setLabel($this->translate('Use Secure Protocol'));
		$use_secure->setRequired(false);
		$form->addElement($use_secure);
		
		$ssl = new Zend_Form_Element_Select('ssl');
		$ssl->addMultiOption('ssl',$this->translate('SSL'));
		$ssl->addMultiOption('tls',$this->translate('TLS'));
		$ssl->setValue($item->mail->ssl);
		$ssl->setLabel($this->translate('Protocol'));
		$ssl->setRequired(false);
		$form->addElement($ssl);
				
				
		$this->view->form = $form;
		$this->render('index');
		Precurio_Session::setCurrentForm($form);
	}
	public function moduleAction()
	{
		
		$form = new Zend_Form();
		$form->setAction($this->getRequest()->getBaseUrl().'/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.'submit')
			->setMethod('post')
			->setAttrib('id','form')
			->setAttrib('name','addForm')
			->setAttrib('enctype', 'multipart/form-data');
		
		$form->addElement('hidden', 'section', array(
				'validators' => array(
				),
				'decorators' => array(
					'ViewHelper'
				),
				'required' => false,
				'value'=>'module'
				));

		$modules = Bootstrap::getModules(false);		
		foreach($modules as $module)
		{
			
			$form->addElement('checkbox', 'mod_'.$module->getName(), array(
				'validators' => array(
				),
				'required' => false,
				'value'=>$module->getStatus(),
				'label'=>$module->getShortTitle(),
				));
		}		
				
				
		$this->view->form = $form;
		$this->render('index');
		Precurio_Session::setCurrentForm($form);
		
	}
	
	function getPageTitle() {
		return $this->translate('System Settings');
	}
	public function preDispatch()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ADMIN);
		$this->view->page = $this->getRequest()->getActionName();
		$this->view->pageTitle = $this->view->translate($this->getPageTitle());
		
	}
	public function __call($methodName, $args)
	{
		if($methodName == 'mysqlAction')
		{
			$this->_redirect('/'.$this->getRequest()->getModuleName().'/'.$this->getRequest()->getControllerName().'/'.'database');
			return;
		}
		parent::__call($methodName,$args);
	}
	public function testldapAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$form = Precurio_Session::getCurrentForm();
		
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			echo $this->translate("Invalid LDAP Settings");
		}
		
		$values = $_POST;
		
		$params = array(
			'server1'=>array(
				'host'=>$values['host'],
				'port'=>$values['port'],
				'useSsl'=>$values['useSsl'],
				'accountDomainName'=>$values['accountDomainName'],
				'baseDn'=>$values['baseDn'],
				'username'=>$values['username'],
				'password'=>$values['password'],
				'bindRequiresDn'=>$values['bindRequiresDn']
			)
		);

		$ldap = new Zend_Auth_Adapter_Ldap($params,$values['username'],$values['password']);
		
		$result = $ldap->authenticate();
		$messages = $result->getMessages();
		if($result->isValid())
		{
			echo $this->translate("Success");
		}
		else
		{
			echo $messages[1];//implode("<br/>",$messages)	;
		}
	}
	public function testdbAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$form = Precurio_Session::getCurrentForm();
		
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			echo $this->translate("Invalid Database Settings");
		}
		
		$values = $form->getValues();
		$adapter = 'Pdo_Mysql';
		
    	$params = array(
    		'host'=>$values['host'],
    		'port'=>$values['port'],
    		'dbname'=>$values['dbname'],
    		'username'=>$values['username'],
    		'password'=>$values['password']
    	);

		$db = Zend_Db::factory($adapter,$params);
		try 
		{
			$db->getConnection();
		}
		catch(Zend_Db_Exception $e)
		{
			echo ($e->getMessage());
			return;
		}
		catch(Zend_Exception $e)
		{
			echo ($e->getMessage());
			return;
		}
		$db->closeConnection();
		
		echo $this->translate("Success");
	}
	public function testmailAction()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
		
		$form = Precurio_Session::getCurrentForm();
		
		if (!$form->isValid($_POST))
		{
			// Failed validation; redisplay form
			$this->view->form = $form;
			echo $this->translate("Invalid LDAP Settings");
		}
		
		$values = $_POST;
		
		$mail = new Zend_Mail();
		
		if($values['use_auth'])
		{
			$config = array('auth' => $values['auth'],
			'username' => $values['username'],
			'password' => $values['password']);	
			if($values['use_secure'])
				$config['ssl'] = $values['ssl'];
		}
		else
		{
			$config = array();
		}
		$config['port'] = $values['port'];
    	$tr = new Zend_Mail_Transport_Smtp($values['server'],$config);
    	
    	$mail->setFrom($values['admin_email'],$values['admin_name']);
    	
    	$mail->setSubject('Precurio : '.$this->translate('Mail Server Setup'));
    	
    	$yesno = array($this->translate(PrecurioStrings::NO),$this->translate(PrecurioStrings::YES));
    	
    	$msg = "<h5>".$this->translate("You have successfully setup your mail server on Precurio using the configurations below.")."</h5>
    	<br/>".$this->translate("Please do not delete this message, so you can use it as a reference for future mail setups")."
    	<br/><br/>".$this->translate('Configuration')."<br/>=====================================<br/>
    	".$this->translate('Server')." : {$values['server']} <br/>
    	".$this->translate('Port')." : {$values['port']} <br/>
    	".$this->translate('Administrator Email')." : {$values['admin_email']} <br/>
    	".$this->translate('Administrator Name')." : {$values['admin_name']} <br/>
    	".$this->translate('Use SMTP Authentication')." : {$yesno[$values['use_auth']]} <br/>
    	".$this->translate('Authentication Type')." : {$values['auth']} <br/>
    	".$this->translate('Username')." : {$values['username']} <br/>
    	".$this->translate('Password')." : {$values['password']} <br/>
    	".$this->translate('Use Secure Protocol')." : {$yesno[$values['use_secure']]} <br/>
    	".$this->translate('Protocol')." : {$values['ssl']} <br/>";
    	
    	$mail->addTo($values['admin_email'],$values['admin_name']);
		$mail->setBodyHtml($msg);
    	
    	$mail->send($tr);
    	echo $this->translate("If the mail settings are correct, you should recieve a test mail at").' '.$values['admin_email'];
	}
	public function anonymousinfoAction()
	{
		$this->_helper->layout->disableLayout();
	}
}

?>