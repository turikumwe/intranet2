<?php

require_once ('Zend/Db/Table/Row/Abstract.php');

class FormBuilderComponent extends Zend_Db_Table_Row_Abstract {
	
	private $_name;
	private $_position;
	function getId()
	{
		return $this->componentid;
	}
	function getLabel()
	{
		return $this->componentlabel;
	}
	function getName()
	{
		return $this->_name;
	}
	function getType()
	{
		return $this->componenttype;
	}
	function getDataType()
	{
		switch ($this->getType())
		{
			case 'user':
			case 'department':
			case 'location':
			case 'currency':
			case 'date':
			case 'signature':
				return $this->getType();
			case 'price':
				return 'currency';
			default:
				return 'varchar';
		}
	}
	function getControl()
	{
		switch ($this->getType())
		{
			case 'user':
			case 'department':
			case 'location':
			case 'dropdown':
				return 'select';
			case 'date':
			case 'currency':
			case 'number':
			case 'price':
				return 'text';
			case 'check':
				return 'checkbox';
			case 'signature':
				return 'hidden';
			default:
				return $this->getType();
		}
		
	}
	function getRequired()
	{
		return $this->componentrequired ? 'true' : 'false';
	}
	function getDefaultValue()
	{
		return $this->componentdefaultvalue;
	}
	function getStateId($process_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::WORKFLOW_STATES));	
		$state = $table->fetchRow($table->select()->where('form_builder_sectionid = ?',$this->section)
								->where('process_id = ?',$process_id));
		return $state->id;
	}
	function generateSchema($oldName="")
	{
		$component = array(
			'field'=>array(
				'_c'=>array(
					'label'=>array(
						'_v'=>$this->getLabel()
					),
					'name'=>array(
						'_v'=>$oldName == "" ? $this->getName() : $oldName //if this component already had a name, please use it.
					),
					'type'=>array(
						'_v'=>$this->getDataType()
					),
					'control'=>array(
						'_v'=>$this->getControl()
					),
					'required'=>array(
						'_v'=>$this->getRequired()
					),
					'default'=>array(
						'_v'=>$this->getDefaultValue()
					),
					'options'=>array(
						'_c'=>array(
							'validators'=>array(
								'_c'=>$this->generateValidators(),
							),
							'filters'=>array(
								'_v'=>''
							),
							'display'=>array(
								'_c'=>$this->generateDecorators(),
							),
						)
					),
					'position'=>array(
						'_v'=>$this->_position
					),
					'componentid'=>array(
						'_v'=>$this->getId()
					)
				)
			)
		);
		$component = $this->generateDataSource($component);
		$component = $this->generateData($component);
		return $this->ary2xml($component);
	}

	private function generateDecorators()
	{
		$decorators = array(0=>array('name'=>'ViewHelper'),
							1=>array('name'=>'Errors'),
							2=>array('name'=>'HtmlTag','options'=>array('tag'=>'td','class'=>'alt')),
							3=>array('name'=>'Label'),
							4=>array('name'=>'HtmlTag','alias'=>'label','options'=>array('tag'=>'td','class'=>'alt')),
							5=>array('name'=>'HtmlTag','alias'=>'row','options'=>array('tag'=>'tr')),
						);
		if(!($this->_position % 2))
		{
			unset($decorators[2]['options']['class']);
			unset($decorators[4]['options']['class']);
		}
		if($this->getControl() == 'file')
		{
			unset($decorators[0]);
		}				
		$arr = array();
		foreach($decorators as $decorator)
		{
			$temp = array(
				'_c'=>array(
					'name'=>array(
						'_a'=>array('value'=>$decorator['name'])
					)
				)
			);
			if(isset($decorator['alias']))
			{
				$temp['_c']['name']['_a']['alias'] = $decorator['alias'];
			}
			
			$d = array('_a'=>array());
			if(isset($decorator['options']))
			{
				foreach($decorator['options'] as $key=>$option)
				{
					$d['_a'][$key] = $option;
				}
			}
			$temp['_c']['options'] = $d;
			
			$arr[] = $temp;
		}
		return array('decorator'=>$arr);
	}
	private function generateValidators()
	{
		$validators = array();
		$arr = array();
		foreach($validators as $validator)
		{
			$temp = array(
				'_c'=>array(
					'name'=>array(
						'_v'=>$validator['name']
					)
				)
			);
			if(isset($validator['params']))
			{
				
				$p = array();
				foreach($validator['params'] as $param)
				{
					$p[] = array(
						'_v'=>$param
					);
							
				}
				$temp['_c']['params'] = $p;
			}
			
			$arr[] = $temp;
		}
		return array('validator'=>$arr);	
	}
	private function generateDataSource($array)
	{
		$datasource = array();
		if($this->getType() == 'user')
		{
			$datasource = array('table'=>PrecurioTableConstants::USERS,'label'=>'first_name,last_name','index'=>'user_id');
		}
		elseif($this->getType() == 'department')
		{
			$datasource = array('table'=>PrecurioTableConstants::DEPARTMENTS,'label'=>'title','index'=>'id');
		}
		elseif ($this->getType() == 'location')
		{
			$datasource = array('table'=>PrecurioTableConstants::LOCATIONS,'label'=>'title','index'=>'id');
		}
		
		if(count($datasource))
		{
			$array['field']['_c']['datasource'] = array(
				'_c'=>array(
					'tablename'=>array(
						'_v'=>$datasource['table']
					),
					'labelfield'=>array(
						'_v'=>$datasource['label']
					),
					'indexfield'=>array(
						'_v'=>$datasource['index']
					)
				)
			);
		}
		
		return $array;
	}
	private function generateData($array)
	{
		$type = $this->getType();
		if(!($type == 'dropdown' || $type == 'check' || $type == 'radio'))return $array;
		
		$formsBuilder = new Precurio_FormsBuilder(0);
		$options = $formsBuilder->getComponentOptions($this->getId());
		unset($formsBuilder);
		
		if(count($options))
		{
			$o = array();
			foreach($options as $option)
			{
				$o[] = array(
						'_v'=>$option
					);
			}
			
			$array['field']['_c']['data'] = array(
				'_c'=>array(
					'option'=>$o
				)
			);
		}
		
		return $array;
		
		
	}
	/**
	 * Generates a name for the component based on the following rules
	 * 1) The component should be named by the first word of its label
	 * 2) If the first word has already been used, use the first_second
	 * 3) If the first_second word has already been used, use the first_second_third_nth.word
	 * till u find a unique name.
	 * 4) If at any point there is no next word, use a digit starting from 2. eg date_4 means 
	 * this is the 4th instance of a component that has its label has 'date'
	 * @param $existingNames Array of already existing form component names
	 * @return string the generated name of this component.
	 */
	function setName($existingNames)
	{
		$str = str_word_count(strtolower($this->getLabel()),1);
		$name = $str[0];
		$i=0;
		while(in_array($name,$existingNames))
		{
			$i++;
			if(isset($str[$i]))
			{
				$name = $name.'_'.$str[$i];
			} 
			else
			{ 	
				$j=2;
				while(in_array($name.'_'.$j,$existingNames))
				{
					$j++;
				}
				$name = $name.'_'.$j;
			}
		}
		$this->_position = count($existingNames) + 1; 
		$this->_name = $name;
		return $this->_name;
	}
	
	// _Internal: Remove recursion in result array
	function _del_p(&$ary) {
	    foreach ($ary as $k=>$v) {
	        if ($k==='_p') unset($ary[$k]);
	        elseif (is_array($ary[$k])) $this->_del_p($ary[$k]);
	    }
	}
	
	// Array to XML
	function ary2xml($cary, $d=0, $forcetag='') {
	    $res=array();
	    foreach ($cary as $tag=>$r) {
	        if (isset($r[0])) {
	            $res[]=$this->ary2xml($r, $d, $tag);
	        } else {
	            if ($forcetag) $tag=$forcetag;
	            $sp=str_repeat("\t", $d);
	            $res[]="$sp<$tag";
	            if (isset($r['_a'])) {foreach ($r['_a'] as $at=>$av) $res[]=" $at=\"$av\"";}
	            $res[]=">".((isset($r['_c'])) ? "\n" : '');
	            if (isset($r['_c'])) $res[]=$this->ary2xml($r['_c'], $d+1);
	            elseif (isset($r['_v'])) $res[]=$r['_v'];
	            $res[]=(isset($r['_c']) ? $sp : '')."</$tag>\n";
	        }
	        
	    }
	    return implode('', $res);
	}
	
	// Insert element into array
	function ins2ary(&$ary, $element, $pos) {
	    $ar1=array_slice($ary, 0, $pos); $ar1[]=$element;
	    $ary=array_merge($ar1, array_slice($ary, $pos));
	}
}

?>