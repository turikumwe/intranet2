<?php
/**
 * Precurio, your complete solution to intranet productivity.
 * 
 * @author  Klein Devort Ltd
 * @version 2
 */
require_once 'Zend/Controller/Plugin/Abstract.php';
require_once 'Zend/Controller/Front.php';
require_once 'Zend/Controller/Request/Abstract.php';
require_once 'Zend/Controller/Action/HelperBroker.php';
require_once "Zend/Loader.php"; 
require_once 'Zend/Loader/Autoloader.php';
require_once 'Zend/Config/Ini.php';
require_once 'Zend/Registry.php';
require_once 'user/models/UserUtil.php';

/**
 * 
 * Initializes configuration depndeing on the type of environment 
 * (test, development, production, etc.)
 *  
 * This can be used to configure environment variables, databases, 
 * layouts, routers, helpers and more
 *   
 */
class Initializer extends Zend_Controller_Plugin_Abstract
{
    /**
     * @var string Current environment
     */
    protected $_env;

    /**
     * @var string Path to application root
     */
    protected $_root;
	
    /**
     * Constructor
     *
     * Initialize environment, root path, and configuration.
     * 
     * @param  string $env 
     * @param  string|null $root 
     * @return void
     */
    public function __construct($env, $root = null)
    {
    	$this->_setEnv($env);
    	$this->_root = Zend_Registry::get('root');
    	
       	$front = Zend_Controller_Front::getInstance();
        //Zend_Loader_Autoloader::autoload('Zend');//::registerAutoload();
        $autoloader = Zend_Loader_Autoloader::getInstance();
		$autoloader->setFallbackAutoloader(true);
        
        if(!Precurio_Session::sessionExists())$this->initSession();//start session 
        
        // set the test environment parameters

        if ($env == 'test') {
			// Enable all errors so we'll know when something goes wrong. 
			error_reporting(E_ALL | E_STRICT);  
			ini_set('display_startup_errors', 1);  
			ini_set('display_errors', 1); 

			$front->throwExceptions(true);  
        }
    }

    /**
     * Initialize environment
     * 
     * @param  string $env 
     * @return void
     */
    protected function _setEnv($env) 
    {
		$this->_env = $env;    	
    }

	/**
     * Initialize Session
     * 
     * @return void
     */
    public function initSession()
    {
    	Zend_Session::start(array('save_path'=>$this->_root.'/application/tmp/',
        	'name'=>'PRECURIOSESSID'
        ));
    }
  
    private function analyticLog(Zend_Controller_Request_Abstract $request)
    {
    	//now do analytics log
    	if($request->getActionName() == 'remind')return;//skip task remind action
    	try
		{
			$user_id = Precurio_Session::getCurrentUserId();
		}
    	//if this exception is not caught, it propogates to the ErrorController, in this 
		//situation, we dont want that.
		catch (Precurio_Exception $e)//i.e there was a problem retrieving the session user
		{
			$user_id = 0;
		}
		try
		{
			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC));
			$id = $table->insert(array(
			'user_id'=>$user_id,
			'session_id'=>Zend_Session::getId(),
			'module'=>$request->getModuleName(),
			'controller'=>$request->getControllerName(),
			'action'=>$request->getActionName(),
			'params'=>base64_encode(serialize($request->getParams())),
			'date_created'=>Precurio_Date::now()->getTimestamp()
			));
		}
		catch(Exception $e)
		{
			
		}
    }
    private function performAcl(Zend_Controller_Request_Abstract $request)
    {
    	Zend_Registry::set('ignoreAcl',false);//do not ignore acl. until there is no acl :)
    	$action = $request->getActionName();
   	 	if( $action == 'logout' || $action == 'login' || $action == 'mypage'){
   	 		Zend_Registry::set('ignoreAcl',true);
   	 		return;
   	 	}
    	try
		{
			
			$user = UserUtil::getUser(Precurio_Session::getCurrentUserId());
			if(Precurio_Utils::isNull($user))
			{
				Zend_Registry::set('ignoreAcl',true);
				return;
			}
		}
    	catch (Zend_Session_Exception $e)//i.e there was a problem retrieving the session user
		{
			Zend_Registry::set('ignoreAcl',true);
			return;
		}
    	//if this exception is not caught, it propogates to the ErrorController, in this 
		//situation, we dont want that.
		catch (Exception $e)//i.e there was a problem retrieving the session user
		{
			Zend_Registry::set('ignoreAcl',true);
			return;
		}
		
		if($request->getModuleName() == 'cms')//the cms module has its controller has the privilege
		{
			$resource = new Precurio_Resource('cms_index');
    		$privilege = new Precurio_Privilege($request->getControllerName());	
		}
		else
		{
			$resource = new Precurio_Resource($request->getModuleName().'_'.$request->getControllerName());
    		$privilege = new Precurio_Privilege($request->getActionName());	
		}
		
	    if(!$privilege->isValid())$privilege = null;
		
		$userRoles = $user->getRoles();
    	
    	$acl = Precurio_Acl::getInstance();
    	$acl->initialise(array($resource),$userRoles);
    	$tr = Zend_Registry::get('Zend_Translate');
    	try 
		{
			if(!$acl->has($resource))//it is not a resource
	    	{
	    		//Zend_Registry::set('ignoreAcl',true);
	    		return;//what user is trying to access is not a resource. so allow.	
	    	}
	    	
	    	
	    	$granted = false; //access granted is false
	    	
		  	foreach($userRoles as $role)
		  	{
			  	if (!$acl->hasRole($role)) {
			  	    $error = new Precurio_Exception($tr->translate(PrecurioStrings::INVALIDUSERROLE),Precurio_Exception::EXCEPTION_INVALID_ROLE);									
			  	}
			  	if ($acl->isAllowed($role, $resource, (string)$privilege))
			  	{
			  		$granted  = true;//if the user belongs to a role that is allowed access, then he can
			  		//access the resource even if he belongs to roles that have been denied access
			  		break;	
			  	}
//		    	if (!$acl->isAllowed($role, $resource, (string)$privilege)) 
//				{
//					$error = new Precurio_Exception($tr->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
//					break;//if the user belongs to a role that has been denied access, then 
//						//he becomes denied even if he also belongs to a role that has access.,
//		    	}
		  	}
		  	
		  	if(!$granted)//no role was allowed access
		  	{
		  		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ROLES));
		  		if($table->fetchAll()->count() > 1)//if there is more than one available role, then set error
		  		{
		  			//ok, the administrator has created atleast 2 roles, let's check if he has assign rules to them
		  			$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::RULES));
			  		if($table->fetchAll()->count() > 0)//atleast, a rule has been set on one of the roles, then throw acess error
			  		{
		  				$error = new Precurio_Exception($tr->translate(PrecurioStrings::CANNOTACCESSRESOURCE),Precurio_Exception::EXCEPTION_NO_ACCESS);
			  		}
			  		else
			  		{
			  			Zend_Registry::set('ignoreAcl',true);
			  		}
		  		}//else, allow access to all resources since there aren't enough roles or rules to successfully implement the role based acess control.
		  		else
		  		{
		  			Zend_Registry::set('ignoreAcl',true);
		  		}	
		  	}	
		}
		catch (Zend_Acl_Exception $e)
		{
			$error = new Precurio_Exception($e->getMessage(),Precurio_Exception::EXCEPTION_NO_ACCESS,$e->getCode());
		}
    	
    	if(isset($error))
    	{
    		$err = new stdClass();
    		$err->exception = $error;
    		$request->setModuleName('index');
    		$request->setControllerName('error');
    		$request->setActionName('error');
    		$request->setParam('error_handler',$err);
    		$request->setDispatched(false);
    	}
    
    }
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
    	$this->performAcl($request);
    	$this->analyticLog($request);
    	//this is very important, as it makes the root directory available to all controllers
    	//$request->setParam('root',$this->_root);
    	
    	
    }
}
ini_set("memory_limit","128M");
?>
