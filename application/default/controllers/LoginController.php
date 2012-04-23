<?php

/**
 * LoginController
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';
require_once ('user/models/UserUtil.php');

class LoginController extends Zend_Controller_Action {
	/**
	 * The default action - show the home page
	 */
	public function indexAction() {
		$this->_forward('login');
	}
	public function preDispatch()
	{
		$this->_helper->viewRenderer->setNoRender();
		$this->_helper->layout->disableLayout();
	}
	public function loginAction() {
		if(Bootstrap::isHosted())
		{
			$config = Zend_Registry::get('config');
			if(!$config->license->active)
				throw new Exception($this->view->translate('Your intranet subscription has been deactivated. Please contact Precurio support team.'));
		}
		
		try
		{
			$db = Zend_Registry::get('db');
			$db->getConnection();//force a database connection test.
		}
		catch (Zend_Db_Exception $e)//i.e there was a problem connecting to the database
		{
			//if it is a new installation
			if(Precurio_Utils::isNewInstallation())
			{
				$this->_redirect('/install/install');
			}
			throw new Precurio_Exception($this->view->translate(PrecurioStrings::DATABASECONNECTIONERROR),Precurio_Exception::EXCEPTION_DATABASE_CONNECTION);	
		}
		
		
		try
		{
			$user_id = Precurio_Session::getCurrentUserId();
			$user = UserUtil::getUser($user_id);
			if($user->isAnonymous())
			{
				Precurio_Session::logOut();
				throw new Precurio_Exception($this->view->translate(PrecurioStrings::SESSIONEXPIRED),Precurio_Exception::EXCEPTION_SESSION_EXPIRED);
			}
			if($user->getPercentageComplete() >= 40)
			{
				$this->_redirect('/index/home');
			}
			else
			{
				$this->_redirect('/user/profile/edit');
			}
		}
		//if this exception is not caught, it propogates to the ErrorController, in this 
		//situation, we dont want that.
		catch (Precurio_Exception $e)//i.e there was a problem retrieving the session user
		{
			//simply do nothing and continue execution
		}
		$this->_helper->layout->setLayout('login');
	}
	public function submitAction()
	{
		$this->_helper->layout->setLayout('login');
		$params = $this->getRequest()->getParams();
		if(!isset($params['username']))
		{
			$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_USER);
			if(!isset($ns->currentUser))//throw a re-login error because session has expired
			{
				throw new Precurio_Exception($this->view->translate(PrecurioStrings::SESSIONEXPIRED),Precurio_Exception::EXCEPTION_SESSION_EXPIRED);
			}
			
			$user = $ns->currentUser;
			$params['username'] = $user->identity;
			$params['password'] = $user->credential;
		}
		
		$config = Zend_Registry::get('config');
		
		$auth = $this->_helper->getHelper($config->auth_mech);
		try
		{
			$this->view->isValid = $auth->validate($params['username'],$params['password']);
		}
		catch(Zend_Auth_Adapter_Exception  $err)
		{
			Precurio_Exception::dispatchError($err,$this->getRequest());
		}
		if($auth->getCode() != Zend_Auth_Result::SUCCESS)
		{
			
			$this->view->loginError = $auth->getErrorMessage();
		}
		else
		{
			$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_USER);
			Precurio_Session::setCurrentUser($auth->getUser());
			if(isset($ns->lastRequest))
			{
				$uri = unserialize($ns->lastRequest);
				
				$baseUrl = $this->getRequest()->getBaseUrl();
				if($baseUrl != "")
				{
					$uri = substr($uri,strlen($baseUrl));
				}
				$log = Zend_Registry::get('log');
				$log->info($uri);
				
				return $this->_redirect($uri);
			}
			else
			{
				$user_id = Precurio_Session::getCurrentUserId();
				$user = UserUtil::getUser($user_id);
				if($user->getPercentageComplete() >= 40)
				{
					$this->_redirect('/index/home');
				}
				else
				{
					$this->_redirect('/user/profile/edit');
				}
			}
		}
	}
	public function logoutAction()
	{
		Precurio_Session::logOut();
		return $this->_redirect('/login');
	}
}
?>