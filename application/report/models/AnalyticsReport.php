<?php
require_once 'report/models/vo/Analytic.php';
require_once 'report/models/vo/UserReport.php';
require_once 'cms/models/MyContents.php';
class AnalyticsReport {
	
	/**
	 * @var Zend_Db_Table_Rowset_Abstract
	 */
	private $analytics;
	public function __construct()
	{
		return;
		if($this->analytics == null)
		{	
			$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_REPORT);
			if(isset($ns->analytics))
			{
				$this->analytics = $ns->analytics;
			}
			else
				$this->getAnalytics();
		}
		return $this;
	}
	private function getAnalytics()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$this->analytics =  $table->fetchAll($table->select()->where('date_created > ?',$threeMonthsAgo));
		
		$ns = new Zend_Session_Namespace(Precurio_Session::NAMESPACE_REPORT);
		$ns->setExpirationSeconds(10800);//expire after 3 hr
		$ns->analytics = $this->analytics;
		
		return; 
	}
	private function getRow($id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$row =  $table->fetchRow($table->select()->where('id = ?',$id));
		return $row;
		
	}
	public function getMostSearchedContent()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$analytics =  $table->fetchAll($table->select()->where("module='search'")->where('date_created > ?',$threeMonthsAgo));
		
		
		$pos = 0;
		$arr = array();
		foreach($analytics as $analytic)
		{
			if($analytic->module == 'search')
			{
				$prevAnalytic = $this->getRow($analytic->id + 1);
				if($prevAnalytic->module == 'cms' && $prevAnalytic->controller == 'view' && ($prevAnalytic->session_id == $analytic->session_id))
				{
					$content_id = $prevAnalytic->getParam('c_id');
					if(Precurio_Utils::isNull($content_id))continue;
					
					if(!isset($arr[$content_id]))$arr[$content_id] = 0;
					$arr[$content_id]++;
				}
			}
			
			$pos++;
		}
		arsort($arr);
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			$temp = array();
			$content = MyContents::getContent($key);
			if($content == null)continue;
			$temp['title'] = $content->getTitle();
			$temp['value'] = $value;
			$temp['page_views'] = $content->num_of_hits;
			$temp['user'] = $content->getFullName();
			$temp['last_updated'] = $content->getDateLastUpdated();
			$temp['id'] = $key;
			$data[] = $temp;
		}
		
		unset($analytics);
		unset($arr);
		
		return $data;
	}
	
	public function getContentViewActivity()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$analytics =  $table->fetchAll($table->select()->where("module='cms'")->where("controller='view'")->where("action='details'")->where('date_created > ?',$threeMonthsAgo));
		
		$tr = Zend_Registry::get('Zend_Translate');
		
		$pos = 0;
		$arr = array();
		foreach($analytics as $analytic)
		{
			$date = new Zend_Date($analytic->date_created);
			$week = $date->get(Zend_Date::WEEK);
			if(!isset($arr[$week]))$arr[$week] = 0;
			$arr[$week] = $arr[$week]+1;
		}
		ksort($arr);
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			$temp = array();
			$temp['week'] = $tr->translate('week').' '.$key;
			$temp['value'] = (int)$value;
			$data[] = $temp;
		}
	
		
		unset($analytics);
		unset($arr);
		
		return $data;
	}
	public function getContentUpdateActivity()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$analytics =  $table->fetchAll($table->select()->where("module='cms'")->where("controller='edit'")->where("action='edit'")->where('date_created > ?',$threeMonthsAgo));
		
		$tr = Zend_Registry::get('Zend_Translate');
		
		$pos = 0;
		$arr = array();
		foreach($analytics as $analytic)
		{
			$date = new Zend_Date($analytic->date_created);
			$week = $date->get(Zend_Date::WEEK);
			if(!isset($arr[$week]))$arr[$week] = 0;
			$arr[$week] = $arr[$week]+1;
		}
		ksort($arr);
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			$temp = array();
			$temp['week'] = $tr->translate('week').' '.$key;
			$temp['value'] = (int)$value;
			$data[] = $temp;
		}
	
		
		unset($analytics);
		unset($arr);
		
		return $data;
	}
	public function getContentAdditionActivity()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$analytics =  $table->fetchAll($table->select()->where("module='cms'")->where("controller='add'")->where("action='submit'")->where('date_created > ?',$threeMonthsAgo));
		
		$tr = Zend_Registry::get('Zend_Translate');
		
		$pos = 0;
		$arr = array();
		foreach($analytics as $analytic)
		{
			$date = new Zend_Date($analytic->date_created);
			$week = $date->get(Zend_Date::WEEK);
			if(!isset($arr[$week]))$arr[$week] = 0;
			$arr[$week] = $arr[$week]+1;
		}
		ksort($arr);
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			$temp = array();
			$temp['week'] = $tr->translate('week').' '.$key;
			$temp['value'] = (int)$value;
			$data[] = $temp;
		}
	
		
		unset($analytics);
		unset($arr);
		
		return $data;
	}
	public function getMostSearchedPhrases()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SEARCH));
		$searches =  $table->fetchAll($table->select()->where('date_created > ?',$threeMonthsAgo));
		
		
		$pos = 0;
		$arr = array();
		foreach($searches as $search)
		{
			$query = strtolower($search->query);
			if(Precurio_Utils::isNull($query))continue;
			$wordArr = str_word_count($query,1);
			foreach($wordArr as $word)
			{
				if(!$this->isRealWord($word))continue;
				
				if(!isset($arr[$word]))$arr[$word] = 0;
				$arr[$word] = $arr[$word]+1;
			}

		}
		arsort($arr);
		
		$arr = array_splice($arr,0,15);
		
		ksort($arr);
		
	
		$data = array();
		foreach($arr as $key=>$value)
		{
			if($value < 2) continue;
			$temp = array();
			$temp['word'] = (string)$key;
			$temp['value'] = (int)$value;
			$data[] = $temp;
		}
	
		
		unset($searches);
		unset($arr);
		
		return $data;
	}
	public function getTopFailedSearchedPhrases()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USER_SEARCH));
		$searches =  $table->fetchAll($table->select()->where('hit_count = 0')->where('date_created > ?',$threeMonthsAgo));
		
		
		$res = 0;
		$arr = array();
		foreach($searches as $search)
		{
			$found = false;
			$query = strtolower($search->query);
			if(!$this->isRealWord($query))continue;
			foreach($arr as $word=>$value)
			{
				$res = levenshtein($word,$query);
				if($res === -1)break;
				if($res <= 3)
				{
					$arr[$word] = $arr[$word]+1;
					$found = true;
					break;
				}
			}
			if(!$found && ($res !==-1))
			{
				if(!isset($arr[$query]))$arr[$query] = 0;
				$arr[$query] = $arr[$query]+1;
			}

		}
		arsort($arr);
		
		$arr = array_splice($arr,0,15);
		
		ksort($arr);
		
	
		$data = array();
		foreach($arr as $key=>$value)
		{
			if($value < 2) continue;
			$temp = array();
			$temp['word'] = (string)$key;
			$temp['value'] = (int)$value;
			$data[] = $temp;
		}
	
		
		unset($searches);
		unset($arr);
		
		return $data;
	}
	public function getTopSearchOriginationContent()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$analytics =  $table->fetchAll($table->select()->where("module='search'")->where('date_created > ?',$threeMonthsAgo));
		
		
		$pos = 0;
		$arr = array();
		foreach($analytics as $analytic)
		{
			if($analytic->module == 'search')
			{
				$prevAnalytic = $this->getRow($analytic->id - 1);
				if($prevAnalytic->module == 'cms' && $prevAnalytic->controller == 'view' && ($prevAnalytic->session_id == $analytic->session_id))
				{
					$content_id = $prevAnalytic->getParam('c_id');
					if(Precurio_Utils::isNull($content_id))continue;
					
					if(!isset($arr[$content_id]))$arr[$content_id] = 0;
					$arr[$content_id]++;
				}
			}
			
			$pos++;
		}
		arsort($arr);
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			$temp = array();
			$content = MyContents::getContent($key);
			if($content == null)continue;
			$temp['title'] = $content->getTitle();
			$temp['value'] = $value;
			$temp['page_views'] = $content->num_of_hits;
			$temp['user'] = $content->getFullName();
			$temp['last_updated'] = $content->getDateLastUpdated();
			$temp['id'] = $key;
			$data[] = $temp;
		}
		
		unset($analytics);
		unset($arr);
		
		return $data;
	}
	
	public function getModuleInventory()
	{
		$arr = array(
			'Contacts'=>PrecurioTableConstants::CONTACTS,
			'Contents'=>PrecurioTableConstants::CONTENT,
			'Events'=>PrecurioTableConstants::EVENT,
			'Facts'=>PrecurioTableConstants::FACTS,
			'Links'=>PrecurioTableConstants::LINKS,
			'News'=>PrecurioTableConstants::NEWS,
			'Polls'=>PrecurioTableConstants::POLL,
			'Tasks'=>PrecurioTableConstants::TASK,
			'Users'=>PrecurioTableConstants::USERS,
			'Workflow'=>PrecurioTableConstants::WORKFLOW
			
		
		
			
		
		);
		
		
		$data = array();
		
		foreach($arr as $module=>$table)
		{
			$data[] = $this->getModuleReportData($table,$module);
		}
		
		return $data;
		
	}
	
	private function getModuleReportData($tableName,$moduleName)
	{
		$table = new Zend_Db_Table(array('name'=>$tableName));
		$all = $table->fetchAll($table->select()->where('active=1')->order('id desc'));
		$temp = array(
		'module'=>$moduleName,
		'inventory'=>$all->count(),
		'last_insert_date'=>$all->current()->date_created
		);
		return $temp;
	}
	
	
	public function getPortalVisitorsReport()
	{
		$now = time();
		$threeMonthsAgo = $now - (60 * 60 * 24 * 7 * 13); //3months == 13 weeks
		
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC,'rowClass'=>'Analytic'));
		$select = $table->select(false);
		$select->setIntegrityCheck(false);
		$select = $select->distinct()
				->from(array('a'=>PrecurioTableConstants::ANALYTIC))
				->join(array('b' => PrecurioTableConstants::USERS),'a.user_id = b.user_id',array('fullname'=>'concat(b.first_name," ",b.last_name)'))
				->where('b.active=1')
				->where('a.date_created > ?',$threeMonthsAgo)
				->order('a.id desc')
				->group('a.session_id')
				;
		$analytics =  $table->fetchAll($select);
		
		$arr = array();
		$arr2 = array();
		$dates = array();
		$names = array();
		foreach($analytics as $analytic)
		{
			$user_id = $analytic->user_id;
			if(!isset($arr[$user_id]))
			{
				$arr[$user_id] = 0;
				$dates[$user_id] = $analytic->date_created;
				$names[$user_id] = $analytic->fullname;
			}
			$arr[$user_id]++;
			
			$date = new Zend_Date($analytic->date_created);
			$week = $date->get(Zend_Date::WEEK);
			if(!isset($arr2[$week]))$arr2[$week] = 0;
			$arr2[$week] = $arr2[$week]+1;
		}
		arsort($arr);
		ksort($arr2);
		$arr = array_chunk($arr,10,true);$arr = $arr[0];
		
		
		$tr = Zend_Registry::get('Zend_Translate');
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			$temp = array();
			$temp['user'] = $names[$key];
			$temp['number_of_visits'] = $value;
			$temp['last_visit_date'] = $dates[$key];
			$temp['user_id'] = $key;
			$data[] = $temp;
		}
		
		$data2 = array();
		foreach($arr2 as $key=>$value)
		{
			$temp = array();
			$temp['week'] = $tr->translate('week').' '.$key;
			$temp['total'] = $value;
			$data2[] = $temp;
		}
		
		unset($analytics);
		unset($arr);
		unset($arr2);
		
		return array($data,$data2);
	}
	
	
	public function getPortalActivity()
	{
		$now = time();
		$aMonthAgo = $now - (60 * 60 * 24 * 30); //1months == 30 days
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC));
		$analytics =  $table->fetchAll($table->select()->where("module<>'default'")->where('date_created > ?',$aMonthAgo));
		
		$tr = Zend_Registry::get('Zend_Translate');
		$arr = array();
		
		foreach($analytics as $analytic)
		{
			$date = new Zend_Date($analytic->date_created);
			$day = $date->get(Zend_Date::DAY_OF_YEAR);
			if(!isset($arr[$day]))$arr[$day] = 0;
			$arr[$day] = $arr[$day]+1;
			
		}
		$min  = 100;
		
		if(true)//change to false if you do not want the minimum value to be intelligently calculated
		{
			$max = $arr[$day];
			$min = $arr[$day];
			foreach($arr as $value)
			{
				if($value < $min)$min = $value;
				if($value > $max)$max = ($max + $value)/2;
			}
			if(($max-$min)/3 > $min)
				$min  = ($max-$min)/3;
		}
		ksort($arr);
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			if($value < $min)continue// minimum allowable activity level is 100
			$temp = array();
			$temp['day'] = $tr->translate('day').' '.$key;
			$temp['total'] = $value;
			$data[] = $temp;
		}
		
		unset($analytics);
		unset($arr);
		
		return $data;
	}
	
	public function getUserList()
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'UserReport'));
		$users = $table->fetchAll($table->select()->where('active=1')->order('first_name'));
		
		$data = array();
		foreach($users as $user)
		{
			$temp = array();
			$temp['user'] = $user->getFullName();
			$temp['user_id'] = $user->getId();
			$temp['job_title'] = $user->getJobTitle();
			$temp['department'] = $user->getDepartment();
			$temp['number_of_tasks'] = $user->getNumOfTasks();
			$temp['number_of_contacts'] = $user->getNumOfContacts();
			$temp['date_created'] = $user->date_created;
			
			$data[] = $temp;
			
		}
		return $data;
	}
	/**
	 * @param $user_id
	 * @return UserReport
	 */
	public function getUserReport($user_id)
	{
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::USERS,'rowClass'=>'UserReport'));
		$user = $table->fetchRow($table->select()->where('active=1')->where('user_id = ?',$user_id));
		return $user;
		
		
	}
	public function getUserActivity($user_id)
	{
		$now = time();
		$aMonthAgo = $now - (60 * 60 * 24 * 30); //1months == 30 days
		$table = new Zend_Db_Table(array('name'=>PrecurioTableConstants::ANALYTIC));
		$analytics =  $table->fetchAll($table->select()->where('user_id = ? ',$user_id)->where("module<>'default'")->where('date_created > ?',$aMonthAgo));
		
		$tr = Zend_Registry::get('Zend_Translate');
		$arr = array();
		
		foreach($analytics as $analytic)
		{
			$date = new Zend_Date($analytic->date_created);
			$day = $date->get(Zend_Date::DAY_OF_YEAR);
			if(!isset($arr[$day]))$arr[$day] = 0;
			$arr[$day] = $arr[$day]+1;
			
		}
		$min  = 5;
		
		if(false)//change to false if you do not want the minimum value to be intelligently calculated
		{
			$max = $arr[$day];
			$min = $arr[$day];
			foreach($arr as $value)
			{
				if($value < $min)$min = $value;
				if($value > $max)$max = ($max + $value)/2;
			}
			if(($max-$min)/3 > $min)
				$min  = ($max-$min)/3;
		}
		ksort($arr);
		
		$data = array();
		foreach($arr as $key=>$value)
		{
			if($value < $min)continue// minimum allowable activity level is 100
			$temp = array();
			$temp['day'] = $tr->translate('day').' '.$key;
			$temp['total'] = $value;
			$data[] = $temp;
		}
		
		unset($analytics);
		unset($arr);
		
		return $data;
	}
	private function isRealWord($word)
	{
		$tr = Zend_Registry::get('Zend_Translate');
		switch ($word)
		{
			case $tr->translate('and'):
			case $tr->translate('or'):
			case $tr->translate('them'):
			case $tr->translate('it'):
			case $tr->translate('then'):
			case $tr->translate('there'):
			case $tr->translate('are'):
			case $tr->translate('is'):
			case $tr->translate('were'):
			case $tr->translate('where'):
			case $tr->translate('what'):
			case $tr->translate('module'):
			case $tr->translate('cms'):
			case $tr->translate('event'):
			case $tr->translate('task'):
			case $tr->translate('user'):
			case $tr->translate('now'):
				return false;
			default:
				return true;
		}
	}
}

?>