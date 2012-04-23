<?php

/**
 * IndexController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class Chat_IndexController extends Zend_Controller_Action {
	public function integrateAction()
	{
		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ERROR);
		$this->_helper->viewRenderer->setNoRender(false);

		$config = Zend_Registry::get('config');
		
		if ($config->auth_mech  == 'LdapAuth')
		{
			$this->view->message =  $this->view->translate("You are using LDAP Authentication, please integrate Precurio with Openfire from the Openfire Administrator Console");
			return;
		}
		try
		{
			$table = new Zend_Db_Table(array('name'=>'ofProperty'));
    		
			$row = $table->fetchRow("name = 'provider.auth.className'");
    		if(!$row)
    		{
    			$row = $table->createRow(array('name'=>'provider.auth.className','propValue'=>'org.jivesoftware.openfire.auth.JDBCAuthProvider'));
    		}
    		else
    		{
    			$row->propValue = 'org.jivesoftware.openfire.auth.JDBCAuthProvider';
    		}
    		$row->save();
    		
			
			$row = $table->fetchRow("name = 'provider.user.className'");
    		if(!$row)
    		{
    			$row = $table->createRow(array('name'=>'provider.user.className','propValue'=>'org.jivesoftware.openfire.user.JDBCUserProvider'));
    		}
    		else
    		{
    			$row->propValue = 'org.jivesoftware.openfire.user.JDBCUserProvider';
    		}
    		$row->save();
    		
    		
    		
    		$row = $table->fetchRow("name = 'provider.group.className'");
    		if(!$row)
    		{
    			$row = $table->createRow(array('name'=>'provider.group.className','propValue'=>'org.jivesoftware.openfire.group.JDBCGroupProvider'));
    		}
    		else
    		{
    			$row->propValue = 'org.jivesoftware.openfire.group.JDBCGroupProvider';
    		}
    		$row->save();
    		
    		
    		$this->view->message = $this->view->translate('Openfire integration parameters have been successfully set.').'<br/><a href="'.$this->getRequest()->getBaseUrl().'/">'.$this->view->translate('Click here to login').'</a>';
			return;
		}
		catch(Exception $e)
		{
			$this->view->message = $e->getMessage();
			return;
		}
		
	}
	public function receiveAction()
	{
		$items = '';
		$ns = Precurio_Session::getChatSession();
		
		$from = $this->getRequest()->getParam('from');
		$message = $this->getRequest()->getParam('message');
		
		$message = $this->sanitize($message);
		
		$items .= <<<EOD
{'s': '0','f': '{$from}','m': '{$message}'},
EOD;
		
		if(!isset($ns->chatHistory[$from]))
			$ns->chatHistory[$from] ='';
		
		$ns->chatHistory[$from] .= <<<EOD
{'s': '0','f': '{$from}','m': '{$message}'},
EOD;
		unset($ns->tsChatBoxes[$from]);
		$time = date('Y-m-d H:i:s', time());
		$ns->openChatBoxes[$from] = $time;
		
		
		foreach ($ns->openChatBoxes as $chatbox => $time) 
		{
			if (isset($ns->tsChatBoxes[$chatbox]))continue; 
			$now = time()-strtotime($time);
			$time = date('g:iA M dS', strtotime($time));
	
			$message = "Sent at $time";
			if ($now > 180) 
			{
				$items .= <<<EOD
{'s': '2','f': '$chatbox','m': '{$message}'},
EOD;
				if(!isset($ns->chatHistory[$chatbox]))
					$ns->chatHistory[$chatbox] = '';
			
				$ns->chatHistory[$chatbox] .= <<<EOD
{'s': '2','f': '$chatbox','m': '{$message}'},
EOD;
				$ns->tsChatBoxes[$from] = 1;//this is the only place it is being set.
			}
		}
		
		if ($items != '') 
		{
			$items = substr($items, 0, -1);
		}
		
		$result = new stdClass();
		$result->items = $items;
		echo Zend_Json::encode($result);
	}
	public function startAction()
	{
		$items = '';
		$ns = Precurio_Session::getChatSession();
		
		if(!empty($ns->openChatBoxes))
		{
			foreach($ns->openChatBoxes as $chatbox=>$void)
			{
				$items .= $this->chatBoxSession($chatbox);
			}
		}
		if ($items != '') 
		{
			$items = substr($items, 0, -1);
		}
		
		$str = <<<EOD
{"username": '{$ns->username}',"items": [{$items}]}
EOD;
		echo $str;
		return;
		
	}
	public function sendAction()
	{
		$ns = Precurio_Session::getChatSession();
		//if(!isset($ns->username))$ns->username = UserUtil::getUser(Precurio_Session::getCurrentUserId())->getUsername();
		
		$to = $this->getRequest()->getParam('to');
		$message = $this->getRequest()->getParam('message');
		
		$ns->openChatBoxes[$to] = date('Y-m-d H:i:s', time());
		
		$messagesan = $this->sanitize($message);
		
		if(!isset($ns->chatHistory[$to]))
			$ns->chatHistory[$to] = '';
			
		$ns->chatHistory[$to] .= <<<EOD
{'s': '1','f': '{$to}','m': '{$messagesan}'},
EOD;
		unset($ns->tsChatBoxes[$to]);
		echo "1";
		
		//Precurio_Utils::debug($ns->openChatBoxes);
		
		return;	
		
	}
	public function closeAction()
	{
		$ns = Precurio_Session::getChatSession();
	//	if(!isset($ns->username))$ns->username = UserUtil::getUser(Precurio_Session::getCurrentUserId())->getUsername();
		
		$chatBox = $this->getRequest()->getParam('chatbox');
		
		unset($ns->openChatBoxes[$chatBox]);
	}
	private function sanitize($text) 
	{
		$text = htmlspecialchars($text, ENT_QUOTES);
		$text = str_replace("\n\r","\n",$text);
		$text = str_replace("\r\n","\n",$text);
		$text = str_replace("\n","<br>",$text);
		return $text;
	}
	private function chatBoxSession($chatbox) 
	{
		$ns = Precurio_Session::getChatSession();
		$items = '';
		
		if (isset($ns->chatHistory[$chatbox])) {
			$items = $ns->chatHistory[$chatbox];
		}
		
		return $items;
	}
	public function preDispatch()
	{
		$this->_helper->layout->disableLayout();
		$this->_helper->viewRenderer->setNoRender();
	}
	
}
?>