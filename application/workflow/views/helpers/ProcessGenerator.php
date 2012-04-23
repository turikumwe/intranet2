<?php
/**
 *
 * @author Brain
 * @version 
 */
require_once 'Zend/View/Interface.php';

/**
 * ProcessGenerator helper
 *
 * @uses viewHelper Zend_View_Helper
 */
class Workflow_View_Helper_ProcessGenerator {
	
	/**
	 * @var Zend_View_Interface 
	 */
	public $view;
	/**
	 * Identifies the current state of the process
	 * @var ProcessState
	 */
	public $processState;
	/**
	 * @var Zend_Controller_Request_Abstract Current Request
	 */
	public $request;
	/**
	 * Gotten by calling the info() method on Zend_Db_Table
	 * @var Array 
	 */
	public $processInfo;
	/**
	 * Process Schema 
	 * @var Array 
	 */
	public $processSchema;
	
	/**
	 * @var Zend_Form
	 */
	protected  $_form;
	/**
	 * the header part of the form
	 * @var String 
	 */
	protected $_header;
	
	/**
	 * the footer part of the formthe footer part of the form
	 * @var String 
	 */
	protected $_footer;
	/**
	 * @param ProcessState $processState
	 * @param Simple_Xml_Element $processSchema
	 * @param Array $processInfo Table Structure/property
	 * @param Zend_Controller_Request_Abstract $request
	 * @return Workflow_View_Helper_ProcessGenerator Object
	 */
	public function processGenerator($processState,$processSchema,$request) { 
		$this->processState = $processState;
		$this->request = $request;
//		$this->processInfo = $processInfo;
		$converter = new Precurio_XmlToArray();
		$this->processSchema = $converter->GetXMLTree($processSchema->asXML());
		$this->processSchema = $this->processSchema['process'];
		$this->_header = $this->generateHeader();
		$this->_form = $this->generateForm($this->_form);
		$this->_footer = $this->generateFooter();
		
		return $this;
	}
	
	/**
	 * @return the $_form
	 */
	public function getForm() {
		return $this->_form;
	}

	private function generateHeader()
	{
		$labelArr = $this->processSchema['header']['label'];
		$headerLabel = "";
		foreach($labelArr as $label)
		{
			$headerLabel .= $this->view->formLabel(null,$label['value']). "<br/>";
		}
		return $headerLabel;
	}
	private function generateFooter()
	{
		
	}

	private function generateForm()
	{
		$fields = $this->getFields();
		
		$form = new Zend_Form(array('disableLoadDefaultDecorators' => true));
		$form->setAction($this->getRequest()->getBaseUrl().'/workflow/process/submitnew')
			->setMethod('post');
		
		$processIdField = new Zend_Form_Element_Hidden('process_id');
		$processIdField->setValue($this->processState->process_id);
		$form->addElement($processIdField);
		
		$stateIdField = new Zend_Form_Element_Hidden('state_id');
		$stateIdField->setValue($this->processState->id);
		$form->addElement($stateIdField);
		
		
		foreach($fields as $field)
		{
			$precurioElement = new Precurio_FormElement($field);
			$signatureFound = false;
			if($field['type']=='signature')
			{
				$signatureFound = true;
				$precurioElement->processApprovalButtons();
				$form->addElements($precurioElement->formButtons);
			}
			else
			{
				$precurioElement->processSchema();
				$form->addElement($precurioElement->formElement);
			}
		}
		if(!$signatureFound)//signature element not found. means this is not an approval stage
			$form->addElements(Precurio_FormElement::generateSubmitCancelButtons());	
		
		$form->setDecorators(array(
		    'FormElements',
		    array('HtmlTag', array('tag' => 'table')),
		    'Form',
		));
	    return $form;
	}
	
	/**
	 * @return Array of fields sorted by position 
	 * 
	 */
	private function getFields()
	{
		$allFields =  $this->processSchema['fields']['field'];
		$stateFieldsNames = $this->processState->getFields();
		
		$stateFields = array();
		foreach($allFields as $field)
		{
			if(array_search($field['name'],$stateFieldsNames)=== FALSE)
				continue;
			$stateFields[] = $field;
		}
		usort($stateFields, 'self::sortFn');
		return $stateFields;
	}
	private function sortFn($x, $y)
	{
		 if ( $x['position'] == $y['position'] )
		  return 0;
		 else if ( $x['position'] < $y['position'] )
		  return -1;
		 else
		  return 1;
	}


	/**
	 * @param $_footer the $_footer to set
	 */
	public function setFooter($_footer) {
		$this->_footer = $_footer;
	}

	/**
	 * @return the $_footer
	 */
	public function getFooter() {
		return $this->_footer;
	}

	/**
	 * @return the $_header
	 */
	public function getHeader() {
		return $this->_header;
	}

	
	/**
	 * Sets the view field 
	 * @param $view Zend_View_Interface
	 */
	public function setView(Zend_View_Interface $view) {
		$this->view = $view;
	}
}
