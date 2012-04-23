<?php
require_once ('Zend/Db/Table/Row/Abstract.php');
/**
 * 
 * @author Mayor Brain
 *
 */
class StockTicker {

	/**
	 * Gets all the stocks for a specified user
	 * @param int $user_id - optional (defaults to the current user)
	 * @return Zend_Db_Table_Rowset
	 */
	static function getAll($user_id=0)
	{
		if(empty($user_id))$user_id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>'wgt_stock_ticker','rowClass'=>'Stock'));
		$stocks = $table->fetchAll($table->select()->where('user_id=?',$user_id));
		if($stocks->count() == 0)
		{
			self::insertDefault();
			//insertDefault must insert atleast a stock, else the line below
			//will cause an infinite recursion
			$stocks = self::getAll();
		}
		return $stocks;
	}
	
	/**
	 * Inserts default stocks for a user. This way, the stock ticker is never empty.
	 * @param int $user_id - optional (defaults to the current user)
	 * @return null
	 */
	static function insertDefault($user_id=0)
	{
		self::addNew("MSFT", "Microsoft Corporation");
		self::addNew("GOOG", "Google Inc.");
		self::addNew("EBAY", "eBay Inc.");
	}
	
	/**
	 * Adds a new stock for the specified user
	 * @param string $stock_symbol - Symbol of the stock, e.g. MSFT
	 * @param string $stock_name - Name of the stock e.g. Microsoft Corporation
	 * @param int $user_id - optional (defaults to the current user)
	 * @return int - Primary key of the new stock.
	 */
	static function addNew($stock_symbol,$stock_name,$user_id=0)
	{
		if(empty($user_id))$user_id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>'wgt_stock_ticker'));
		$stocks = $table->fetchAll($table->select()->where('user_id=?',$user_id)->where('stock_symbol = ?',$stock_symbol));
		if($stocks->count() > 0)//if symbol already exists.
		{
			return;
		}
		
		$id = $table->insert(array(
			'user_id'=>$user_id,
			'stock_symbol'=>$stock_symbol,
			'stock_name'=>$stock_name,
			'date_created'=>time()
		));
		return $id;
	}
	/**
	 * Deletes a particular stock from the user's stock list
	 * @param string $stock_symbol
	 * @param int $user_id - optional (defaults to the current user)
	 */
	static function deleteStock($stock_symbol,$user_id=0)
	{
		if(empty($user_id))$user_id = Precurio_Session::getCurrentUserId();
		$table = new Zend_Db_Table(array('name'=>'wgt_stock_ticker'));
		$table->delete("user_id = $user_id and stock_symbol = '$stock_symbol'");
		return;
	}
	
	/**
	 * Finds a stock using an internet service
	 * @param string $search - search parameter
	 * @return array
	 */
	static function findStock($search)
	{
		$service = "http://d.yimg.com/autoc.finance.yahoo.com/autoc?query=$search&callback=YAHOO.Finance.SymbolSuggest.ssCallback";
		$response = file_get_contents($service);
		if(empty($response))
		{
			return;
		}
		$response = substr($response,stripos($response, "(")+1);
		$response = substr($response,0,strlen($response)-1);
		return Zend_Json::decode($response);
	}
}

class Stock extends Zend_Db_Table_Row_Abstract{
	
	var $price = 0;
	var $change = 0.0;
	var $percent_change = "0.00%";
	
    /**
     * Gets stock data from internet service.
     */
    public function getData()
    {
    	//format nk1c6k2
    	//n=>name,k1=>last trade price,c6=>change,k2=>percentage change
    	//reference: http://www.gummy-stuff.org/Yahoo-data.htm
    	$service = "http://finance.yahoo.com/d/quotes.csv?s=".$this->stock_symbol."&f=nk1c6k2";
    	$response = file_get_contents($service);
   	 	if(empty($response))
		{
			return;
		}
    	//$response contains string in format similar to
    	// "Microsoft Corporation","N/A - 25.31","0.00","N/A - 0.00%" 
    	$response = str_ireplace('"', '', $response);//remove double quotes
    	$arr = explode(",", $response);
    	//$arr[0]=>'Microsoft Corpora',$arr[1]=>'N/A - 25.31',$arr[2]=>'0.00',$arr[3]=>'N/A - 0.00%',
    	$this->stock_name = $arr[0];
    	
    	$price = explode(" - ",$arr[1]);
    	$price = strip_tags($price[1]);
    	$this->price = $price;
    	
    	$this->change = $arr[2];
    	
    	$percent_change = explode(" - ",$arr[3]);
    	$percent_change = strip_tags($percent_change[1]);
    	$this->percent_change = trim($percent_change);
    }
    public function getSymbol()
    {
    	return $this->stock_symbol;
    }
    /**
     * Gets an id suitable for use, from the symbol.
     * This id is used in identifying html components
     */
    public function getId()
    {
    	$id = $this->stock_symbol;
    	$id = str_ireplace('.', '', $id);//a dot ('.') in the id will prevent jquery from identifying the element
    	$id = str_ireplace('#', '', $id);//an hash ('#') in the id will prevent jquery from identifying the element
    	return $id;
    }
    public function getName()
    {
    	return $this->stock_name;
    }
    public function getPrice()
    {
    	return $this->price;
    }
    public function getChange()
    {
    	return $this->change;
    }
    public function getPercentChange()
    {
    	return $this->percent_change;
    }
}
?>