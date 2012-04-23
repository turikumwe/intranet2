<?php

/**
 * ErrorController - The default error controller class
 * 
 * @author
 * @version 
 */

require_once 'Zend/Controller/Action.php';

class ErrorController extends Zend_Controller_Action
{

    /**
     * This action handles  
     *    - Application errors
     *    - Errors in the controller chain arising from missing 
     *      controller classes and/or action methods
     */
    public function errorAction()
    {
    	$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_C);
  		try
		{
			$user_id = Precurio_Session::getCurrentUserId();
		}
		catch(Exception $e)
		{
	    	$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ERROR);
	    	$this->view->isSessionError = true;//this is necessary so we dont thank the user for having to relogin
		}
		
    	  
        $errors = $this->_getParam('error_handler');
        if(isset($errors->exception))$errors = $errors->exception;
        //Precurio_Utils::debug($errors);
        if(!isset($errors->type)) //this is for Zend_Exceptions
        {
        	$errors->type = $errors->getCode() == 0 ? 
        		Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER : $errors->getCode();
        }
        if(!isset($errors->exception))//this is for Zend_Exceptions
        	$errors->exception = $errors->getMessage();
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found             
                $this->view->title = 'HTTP/1.1 404 Not Found';
                $this->view->message = $this->view->translate(PrecurioStrings::INVALIDPAGEACCESS);
                break;
            case Precurio_Exception::EXCEPTION_SESSION_EXPIRED:
            	//you could implement automatic redirect or something.
            	$this->view->message = $errors->exception;
            	break;	
           	case Precurio_Exception::EXCEPTION_NO_ACCESS:
           		$this->view->showThankYou = false;//just so that the thank you message does not show
            	$this->view->message = $errors->exception;
            	break;
            	
            case Precurio_Exception::EXCEPTION_DATABASE_CONNECTION:
           		$this->_helper->layout()->setLayout(PrecurioLayoutConstants::MAIN_ERROR);
            	$this->view->showThankYou = false;//just so that the thank you message does not show
            	$this->view->message = $errors->exception;
            	break;
            	
            case Precurio_Exception::EXCEPTION_INVALID_PROCESS_CONFIG:
            	$this->view->message = $errors->exception;
            	break;	
            case Precurio_Exception::EXCEPTION_NO_CONFIG_FILE:
           		$this->_redirect('/install/install');
            	break;
            default://Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
                if($errors instanceof Zend_db_Exception )
                {
                	$this->view->message = $this->view->translate(PrecurioStrings::DATABASEERROROCCURED);
                	break;
                }
        		if($errors instanceof Zend_Config_Exception)
                {
                	$this->view->message = $this->view->translate(PrecurioStrings::CONFIGFILENOTFOUND);
                	break;
                }
       			if($errors instanceof Zend_Controller_Dispatcher_Exception)
                {
	                $this->view->message = $this->view->translate(PrecurioStrings::INVALIDPAGEACCESS);
	                $this->view->showThankYou = false;//just so that the thank you message does not show
                	break;
                }
                $this->view->message = $errors->exception;
		        $this->view->message = $this->view->message.' '.($errors->getCode()==0 ? '' :'<br/>'.$this->view->translate('Error code').' = '.$errors->getCode());
                break;
        }
        if($this->getRequest()->isXmlHttpRequest())
        {
			$this->_helper->layout->disableLayout();
			$this->_helper->viewRenderer->setNoRender();
			echo $this->view->message;
        }
        $this->getRequest()->setDispatched(true);
        $log = Zend_Registry::get('log');
        $log->err($errors);
    }

}
