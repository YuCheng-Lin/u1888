<?php
/**
 * PDO Class
 * @version 2014/12/18
 */
include_once 'pub_config_admin.php';
class PDO_DB extends PDO{
	
	/**
	 * 版本号
	 * 
	 * @version
	 */
	public $version = 'Version:2014/07/31';
	
	
	/**
	 * 每页要显示的比数
	 * 
	 * 预设全部显示
	 * @var integer
	 */
	private $rows_per_page = 'all';

	/**
	 * 目前页码
	 * 
	 * @var integer
	 */
	private $page_id = 0;
	
	/**
	 * 起始页数
	 * 
	 * @var integer
	 */
	private $start_page = 0;

	/**
	 * 分页总页数
	 * 
	 * @var integer
	 */
	public $total_page = 0;

	/**
	 * fetch 资料出来后所带入的阵列
	 * 
	 * @var array
	 */
	public $row = array();

	/**
	 * 分页起始笔数
	 * 
	 * @var integer
	 */
	private $start_row;

	/**
	 * 单页最大笔数
	 * 
	 * @var integer
	 */
	private $max_row;

	/**
	 * table 资料总笔数
	 * 
	 * @var integer
	 */
	public $all_rows = 0;

	/**
	 * 单页总笔数
	 * 
	 * @var integer
	 */
	public $total_row;         // 取出的资料总数

	/**
	 * 分页后缀字串，同时使用多资料表时，可加入字串区别querystring
	 * 
	 * @var string
	 */
	private $qs_suffix = '';
	
	/**
	 * table 名称
	 * 
	 * @var string
	 */
	private $table;

	/**
	 * 编码
	 * 
	 * @var string
	 */
	private $ENCODE = 'utf8';
	
	/**
	 * 资料校对
	 * 
	 * @var string
	 */
	private $COLLATION = 'utf8_unicode_ci';
	
	
	/**
	 * 资料库类型
	 * 
	 * mysql, mssql(mssql, sybase, dblib), oracle(oci), pgsql, sqlite .... etc..
	 * read more：http://php.net/manual/en/pdo.drivers.php
	 * @var string
	 */
	private $DB_TYPE = 'mysql';
	
	
	/**
	 * 资料库帐号
	 * 
	 * @var string
	 */
	private $DB_USER = '';
	
	/**
	 * 资料库密码
	 * 
	 * @var string
	 */
	private $DB_PWD = '';
	
	/**
	 * 资料库名称
	 * 
	 * @var string
	 */
	private $DB_NAME = '';
	
	/**
	 * 当次连线最后insert id;
	 * 
	 * @var string
	 */
	public $last_insert_id = null;
	
	/**
	 * PDO connection
	 * 
	 * @var object
	 */
	public $conn;
	
	
	/**
	 * PDOStatement Statement handle
	 * 
	 * @var string
	 */
	public $sth;

	
	/**
	 * 目前SQL查询字串
	 * 
	 * @var string
	 */
	public $current_query_string;

	/**
	 * Constructor
	 * 
	 * @param String $DB_HOST; 资料库位置 *required
	 * @param String $DB_USER; 资料库帐号 *required
	 * @param String $DB_PWD; 资料库密码 *required
	 * @param String $DB_NAME; 资料库名称 *required
	 * @param [option] String $DB_TYPE; 资料库种类
	 * @param [option] String $ENCODE; 编码
	 * @param [option] String $COLLATION; 资料校对
	 */
	public function __construct($DB_HOST, $DB_USER, $DB_PWD, $DB_NAME, $DB_TYPE='sqlsrv', $ENCODE='utf8', $COLLATION='utf8_unicode_ci')
	{
		
		if($DB_HOST == ''){
			throw new exception("Database Host is not been specify!");
		}
		if($DB_USER == ''){
			throw new exception("Username is not been specify!");
		}
		// if($DB_PWD == ''){
		// 	throw new exception("Password is not been specify!");
		// }
		if($DB_NAME == ''){
			throw new exception("Database is not been selected!");
		}
		
		$this->DB_HOST 		= $DB_HOST;
		$this->DB_USER 		= $DB_USER;
		$this->DB_PWD 		= $DB_PWD;
		$this->DB_NAME 		= $DB_NAME;
		$this->DB_TYPE		= $DB_TYPE;
		$this->ENCODE 		= $ENCODE;
		$this->COLLATION 	= $COLLATION;
		try {
			switch($this->DB_TYPE){
				case 'mysql':
				case 'mssql':
				case 'sybase':
				case 'dblib':
				case 'oci':
				case 'pgsql':
				case 'sqlite':
					$DSN = $this->DB_TYPE.':host='.$this->DB_HOST.';dbname='.$this->DB_NAME;
					// $conn = parent::__construct($DSN, $this->DB_USER, $this->DB_PWD);
					// $stmt = parent::prepare(sprintf('SET NAMES "%s"', $this->ENCODE));
					// $stmt->execute();
					// if($this->DB_TYPE == 'mysql'){
					// 	$stmt = parent::prepare(sprintf('SET collation_connection = "%s"', $this->COLLATION));
					// 	$stmt->execute();
					// }
					break;
				case 'sqlsrv':
					$DSN = $this->DB_TYPE.':Server='.$this->DB_HOST.';Database='.$this->DB_NAME;
					break;
				default:
					throw new exception("PHP PDO Drivers is not support!");
					break;
			}
			try {
			   $this->conn = new PDO($DSN , $this->DB_USER, $this->DB_PWD); 
			   $this->conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
			}catch( PDOException $e ) {
				if(DEBUG){
					echo 'Error connecting to SQL Server<pre>';
					print_r($e);
					exit;
				}
				return $e->getMessage();
			}
		} catch (PDOException $e) {
			if(DEBUG){
				echo 'Connection failed: ' . $e->getMessage();
				exit;
			}
			return $e->getMessage();
		}
		
	}

	/**
	 * Desteuctor
	 *
	 */
	public function __destruct()
	{
		$this->sth = null;
		$this->conn = null;
	}

	/**
	 * Run the statement
	 *
	 */
	public function execute($fetchAll = false)
	{
		try{
			$result = $this->sth->execute();
			if (preg_match('/select/i', $this->sth->queryString)) {
				if($this->sth->rowCount() > 0){
					if($fetchAll){
						$this->row = $this->sth->fetchAll(PDO::FETCH_ASSOC);
					}else{
						$this->row = $this->sth->fetch(PDO::FETCH_ASSOC);
					}
					$this->total_row = $this->sth->rowCount();
				}elseif($this->total_page > 0){
					$this->row     	 = $this->sth->fetch(PDO::FETCH_ASSOC);
					$this->total_row = $this->all_rows;
				}else {
					$this->total_row = 0;
				}
			}
			
			if (preg_match('/insert/i', $this->sth->queryString)) {
				$last_insert_id = $this->conn->lastInsertId();
				if(!empty($last_insert_id) && $last_insert_id > 0){
					$this->last_insert_id = $last_insert_id;
				}
			}
			$this->current_query_string = '';
			return $result;
		} catch(Exception $e){
			return 'execute：'.$e->getMessage();
		}
	}
	
	/**
	 * get current query string
	 * @return query string
	 */
	public function getCurrentQueryString(){
		if(empty($this->current_query_string)){
			return 'Need to call before PDO_DB::execute();';
		}
		return $this->current_query_string;
	}
	
	/**
	 * select table
	 *
	 * @param string $table
	 */
	public function selectTB($table)
	{
		if(empty($table)){
			throw("Table is not been selected!");
		}
		$this->table = $table;
		return $this;
	}
	
	/**
	 * 设置分页变数后缀字串
	 * 
	 * @param string $suffix
	 */
	public function setQSSuffix($suffix)
	{
		if($suffix == ''){
			return $this;
		}
		$this->qs_suffix = $suffix;
		return $this;
	}
	
	
	/**
	 * SELECT method
	 *
	 * @param String $fieldname; ex: "id, name, phone" ...
	 * @param [option] String $condition, SQL 条件式，从 WHERE 开始
	 */
	public function getData ($fieldname, $condition='', $table='')
	{
		if($table == ''){
			$table = $this->table;
		}

		try{
			$this->sth = NULL;
			$this->current_query_string = sprintf("SELECT %s FROM %s %s", $fieldname, $table, $condition);
			$this->sth = $this->conn->prepare($this->current_query_string, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		}catch(PDOException $e){
			return 'Get Data Failed:'.$e->getMessage();
		}
	}

	public function pagingMSSQL($rows_per_page=1, $orderBy='', $qsSuffix='')
	{
		if(!empty($qsSuffix)){
			$this->setQSSuffix($qsSuffix);
		}
		$orderBySql = '';
		if(!empty($orderBy)){
			$orderBySql = " OVER (ORDER BY ".$orderBy." DESC)";
		}
		
		$all_row_sql = 'SELECT COUNT(*) AS all_rows FROM (';
		preg_match_all('/SELECT (.*) FROM/', $this->current_query_string, $queryAry);
		$all_row_sql .= preg_replace('/SELECT .* FROM/', 'SELECT ROW_NUMBER()'.$orderBySql.' AS RowNum, '.$queryAry[1][0].' FROM'
		, $this->current_query_string);
		$all_row_sql .= ') AS NewTable';
		$sth = $this->conn->prepare($all_row_sql);
		$sth->execute();
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$this->rows_per_page = $rows_per_page;

		if (preg_match('/GROUP BY/i', $all_row_sql)) {
			$this->all_rows = $sth->rowCount();
		} else {
			// TODO: 取得 table 总笔数
			$this->all_rows = $row['all_rows'];
		}
		
		if(isset($_GET['current_page'.$this->qs_suffix])){
			$this->start_page = filter_var($this->conn->quote($_GET['current_page'.$this->qs_suffix]), FILTER_SANITIZE_NUMBER_INT);
		}

		// TODO: 计算总页数
		if($this->all_rows > 0){
			$this->total_page =  ceil($this->all_rows / $this->rows_per_page);
		}

		// TODO: 防止页数超出范围
		if($this->start_page < 0){
			$this->start_page = 0;
		}else if($this->start_page > $this->total_page - 1){
			$this->start_page = $this->total_page - 1;
		}

		// TODO: 分页起始笔数
		$this->start_row = $this->start_page * $this->rows_per_page;

		// TODO: 分页结束笔数
		$this->max_row = $this->start_row + $this->rows_per_page;
		// TODO: 取余数即是末页最大笔数
		if($this->all_rows % $this->rows_per_page == 0){
			$last_page_rows = $this->all_rows;
		}else{
			$last_page_rows = $this->all_rows % $this->rows_per_page ;
		}


		// TODO: 该分页最大笔数
		if($this->start_page == $this->total_page-1){
			$this->max_row = $this->start_row + $last_page_rows;
		}

		if($this->all_rows > 0){
			$all_row_sql = preg_replace('/SELECT .* FROM \(/', 'SELECT * FROM (', $all_row_sql);
			$this->current_query_string = sprintf("%s WHERE RowNum > '%d' AND RowNum <= '%d'",$all_row_sql, $this->start_row, $this->max_row);
			$this->sth = $this->conn->prepare($this->current_query_string);
		}
	}


	/**
	 * 分页处理, 每页笔数
	 * 
	 * !important, 注意 bindParam问题
	 * 此function一定要放在bindParam或bindValue之后
	 *
	 * @param integer $rows_per_page 每页笔数
	 * @param string $qsSuffix	分页参数后缀字串
	 */
	public function paging($rows_per_page=1, $qsSuffix='')
	{
		if(!empty($qsSuffix)){
			$this->setQSSuffix($qsSuffix);
		}
		
		$all_row_sql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) AS all_rows FROM', $this->current_query_string);
		$sth = $this->conn->prepare($all_row_sql);
		$sth->execute();
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		$this->rows_per_page = $rows_per_page;

		if (preg_match('/GROUP BY/i', $all_row_sql)) {
			$this->all_rows = $sth->rowCount();
		} else {
			// TODO: 取得 table 总笔数
			$this->all_rows = $row['all_rows'];
		}
		
		if(isset($_GET['current_page'.$this->qs_suffix])){
// 			$this->start_page = $_GET['current_page'.$this->qs_suffix];
			$this->start_page = filter_var(parent::quote($_GET['current_page'.$this->qs_suffix]), FILTER_SANITIZE_NUMBER_INT);
		}

		// TODO: 计算总页数
		if($this->all_rows > 0){
			$this->total_page =  ceil($this->all_rows / $this->rows_per_page);
		}

		// TODO: 防止页数超出范围
		if($this->start_page < 0){
			$this->start_page = 0;
		}else if($this->start_page > $this->total_page - 1){
			$this->start_page = $this->total_page - 1;
		}

		// TODO: 分页起始笔数
		$this->start_row = $this->start_page * $this->rows_per_page;

		// TODO: 分页结束笔数
		$this->max_row = $this->start_row + $this->rows_per_page;

		// TODO: 取余数即是末页最大笔数
		if($this->all_rows % $this->rows_per_page == 0){
			$last_page_rows = $this->all_rows;
		}else{
			$last_page_rows = $this->all_rows % $this->rows_per_page ;
		}

		// TODO: 该分页最大笔数
		if($this->start_page == $this->total_page-1){
			$this->max_row = $this->start_row + $last_page_rows;
		}

		if($this->all_rows > 0){
			$this->current_query_string = sprintf("%s LIMIT %d, %d",$this->current_query_string, $this->start_row, $this->rows_per_page);
			$this->sth = parent::prepare($this->current_query_string);
		}
	}

	/**
	 * bind values , one time or multiple
	 *
	 * @param string $param
	 * @param string $value [option]
	 */
	// public function bindValues($param, $value='')
	// {
	// 	if(!empty($value)){
	// 		$paramAry = array();
	// 		$paramAry[$param] = $value;
	// 		$param = $paramAry;
	// 	}
	// 	try{
	// 		foreach($param as $key=>$val){
	// 			$this->sth->bindValue(':'.$key, $val);
	// 			echo $this->sth->queryString;
	// 			if(gettype($val) == 'string'){
	// 				$val = "'".$val."'";
	// 			}
	// 			$this->current_query_string = preg_replace('/:'.$key.'/', $val, $this->current_query_string);
	// 		}
	// 	} catch(Exception $e){
	// 		echo 'Bind Params Error:'.$e->getMessage();
	// 	}
	// }
	
	public function bindParams(){

	}

	public function bindValues($array, $typeArray = false, $sth='')
	{
		try{
			$sth = empty($sth) ? $this->sth : $sth;
	    	if(!is_object($sth) || !($sth instanceof PDOStatement)){
	    		throw new Exception('No PDOStatement');
	    	}
	        foreach($array as $key => $value){
	            if($typeArray){
	            	$sth->bindValue(":$key",$value,$typeArray[$key]);
	            }else{
	            	$param = false;
	                if(is_int($value))
	                    $param = PDO::PARAM_INT;
	                elseif(is_bool($value))
	                    $param = PDO::PARAM_BOOL;
	                elseif(is_null($value))
	                    $param = PDO::PARAM_NULL;
	                elseif(is_string($value))
	                    $param = PDO::PARAM_STR;
	                else
	                    $sth->bindValue(":$key",$value);
	                   
	                if($param){
	                    $sth->bindValue(":$key",$value,$param);
	                }
	            }
	        }
	        return $sth;
		}catch(Exception $e){
			return 'Bind Params Error:'.$e->getMessage();
		}
	}

	/**
	 * INSERT method
	 *
	 * @param Array $data; ex:$_POST
	 * @param [option] String $table; table name
	 */
	public function insertData ($data, $table='')
	{
		if(empty($table)){
			$table = $this->table;
		}
		// 取得资料表所有field
		$fields = $this->getTableField($table);
		$field_ary = array();
		$data_ary  = array();
		$effective_field = array();
		foreach($data as $key=>$val){
			if(in_array($key, $fields) && !empty($val)){
				$effective_field[$key] = $val;
				$field_ary[] = $key;
				// $field_ary[] = '`'.$key.'`';
				$data_ary[]  = ':'.$key;
			}
		}
		$fields = join(", ",$field_ary);
		$insert_value = join(", ",$data_ary);
		$queryString = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $fields, $insert_value);
		$this->current_query_string = $queryString;
		$this->sth = $this->conn->prepare($queryString);
		$this->bindValues($effective_field);
	}

	/**
	 * Update Or Create
	 */
	public function updateOrCreate (array $attributes, array $values = array(), $table='')
	{
		if(empty($table)){
			$table = $this->table;
		}
		$fields          = $this->getTableField($table);
		$effectiveArray  = array();
		$effective_field = array();
		$condition       = '';//條件式
		$conditionAry    = [];
		$fieldAry = [];

		//屬性產生條件式
		if(!is_array($attributes)){
			return;
		}
		if(count($attributes) <= 0){
			return;
		}
		foreach ($attributes as $key => $value) {
			if(in_array($key, $fields)){
				$conditionAry[] = $key." = '".$value."'";
			}
		}
		if(count($conditionAry) <= 0){
			return;
		}
		$condition = 'WHERE '.join(" AND ", $conditionAry);

		//搜尋屬性
		$queryString = sprintf("SELECT * FROM %s %s", $table, $condition);
		$sth = $this->conn->prepare($queryString, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
		$result = $sth->execute();
		if(is_string($result) || !is_bool($result) || !$result){
			return $result;
		}

		foreach($values as $key=>$val){
			if(in_array($key, $fields)){
				$effectiveArray[] = $key." = :".$key;

				$fieldAry[] = $key;
				$dataAry[]  = ':'.$key;

				$effective_field[$key] = $val;

				$executeAry[':'.$key] = $val;
			}
		}

		//有找到值 update
		if($sth->rowCount() > 0){
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			do{
				$updateStr   = join(",",$effectiveArray);
				$queryString = sprintf("UPDATE %s SET %s %s", $table, $updateStr, $condition);
				$updateSth = $this->conn->prepare($queryString);
				$updateSth->execute($executeAry);
			}while($row = $sth->fetch(PDO::FETCH_ASSOC));
		}
		//無則insert
		else{
			$fields = join(", ",$fieldAry);
			$insert_value = join(", ",$dataAry);
			$queryString = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $fields, $insert_value);
			$insertSth = $this->conn->prepare($queryString);
			$insertSth->execute($executeAry);
		}
	}
	

	/**
	 * UPDATE method
	 *
	 * @param array $data; ex;$_POST
	 * @param string $condition; such as a WHERE clause
	 * @param [option] String $table; table name
	 */
	public function updateData ($data, $condition, $table='')
	{
		if(empty($table)){
			$table = $this->table;
		}
		// 移除POST['id']栏位
// 		if(isset($data['id'])){
// 			unset($data['id']);
// 		}

		// 取得table所有field到$fields
		$fields = $this->getTableField($table);
		// echo '<pre>';
		// print_r($fields);
		// exit;

		// 检查是否有 mtime栏位，有则写入更新时间
// 		if(in_array('mtime', $fields)){
// 			$data['mtime'] = date("Y-m-d H:i:s");
// 		}

		$updateStrArray = array();
		$effective_field = array();

		foreach($data as $key=>$val){
			if(in_array($key, $fields)){
				$updateStrArray[] = $key." = :".$key;
				$effective_field[$key] = $val;
			}
		}

		$updateStr   = join(',',$updateStrArray);

		$queryString = sprintf("UPDATE %s SET %s %s", $table, $updateStr, $condition);
		$this->sth = $this->conn->prepare($queryString);
		$this->current_query_string = $queryString;
		$this->bindValues($effective_field);
	}

	/**
	 * DELETE method
	 *
	 * @param string $condition; such as a WHERE clause
	 * @param [option] String $table; table name
	 */
	public function deleteData ($condition, $table='')
	{
		if(empty($table)){
			$table = $this->table;
		}

		$queryString = sprintf("DELETE FROM %s %s", $table, $condition);
		$this->current_query_string = $queryString;
		$this->sth = $this->conn->prepare($queryString);
	}
	
	/**
	 * optimize table
	 * 
	 * @param string $table
	 */
	public function optimizeTable($table='')
	{
		if(empty($table)){
			$table = $this->table;
		}

		$sth = parent::prepare(sprintf("OPTIMIZE TABLE `%s`", $table));
		$sth->execute();
	}

	/**
	 * 取得table所有的field
	 * 
	 * @return array field names
	 */
	public function getTableField($table='')
	{
		if(empty($table)){
			$table = $this->table;
		}

		$col_name = array();

		try{
			$sql = "SHOW COLUMNS FROM ".$table;//mysql
			$fieldname = 'Field';
			switch ($this->DB_TYPE) {
				case 'sqlsrv':
					$sql = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='".$table."'";
					$fieldname = 'COLUMN_NAME';
					break;
			}

			$sth = $this->conn->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
			$sth->execute();
			
			if ($sth->rowCount() > 0) {
				while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
					$col_name[] = $row[$fieldname];
				}
				return $col_name;
			}
		} catch (PDOException $e){
			return $e->getMessage();
		}
	}

	/**
	 * 前往指定url
	 *
	 * @param string $gotoURL
	 */
	public function gotoURL($gotoURL)
	{
		header(sprintf('location:%s', $gotoURL ));
		exit;
	}

	/**
	 * 以阵列加索引(栏位名称)方式取回
	 * 
	 * @return array
	 */
	public function fetch_assoc()
	{
		return $this->sth->fetch(PDO::FETCH_ASSOC);
	}
	
	
	/**
	 * 资料集分页
	 *
	 * @return string page bar
	 */
	public function createPageBar($bar_length=5, $fullset=true)
	{
		if($this->total_row > 0){
			$get_query = '';
			$params = array();

			if (!empty($_SERVER['QUERY_STRING'])) {
				$params = explode("&", $_SERVER['QUERY_STRING']);
				$newParams = array();
				foreach ($params as $param) {
					if (stristr($param, "current_page".$this->qs_suffix) == false) {
						if (!preg_match('/current_page'.$this->qs_suffix.'/', $param)) {
							array_push($newParams, $param);
						}
					}
				}
				if (count($newParams) != 0) {
					$get_query = htmlentities("&" . implode("&", $newParams), ENT_QUOTES);
				}
			}

			//第一页
			$start_page = 0;

			//上一页
			$per_page   = $this->start_page - 1;
			if($per_page < 0){
				$per_page = 0;
			}

			//下一页
			$next_page  = $this->start_page + 1;

			if($next_page  > $this->total_page - 1){
				$next_page = $this->total_page - 1;
			}

			//最末页
			$last_page  = $this->total_page - 1;
			
			$num_bar = $this->createNumBar($bar_length);
			
			$page_bar_ary = array();
			$page_bar_ary['start_page'] = '<a class="_page_item _page_first" href="?current_page'. $this->qs_suffix .'='.$start_page.$get_query.'">第一页</a>';
			$page_bar_ary['per_page'] 	= '<a class="_page_item _page_prev" href="?current_page'. $this->qs_suffix .'='.$per_page.$get_query.'">上一页</a>';
			$page_bar_ary['fullset'] 	= '';
			$page_bar_ary['next_page'] 	= '<a class="_page_item _page_next" href="?current_page'. $this->qs_suffix .'='.$next_page.$get_query.'">下一页</a>';
			$page_bar_ary['last_page'] 	= '<a class="_page_item _page_last" href="?current_page'. $this->qs_suffix .'='.$last_page.$get_query.'">最末页</a>';
			
			if(($num_bar['current_page'] - 1) == $start_page){
				$page_bar_ary['start_page'] = '<span class="_page_item _page_first">第一页</span>';
			}
			if(($num_bar['current_page'] - 1) == $per_page){
				$page_bar_ary['per_page'] 	= '<span class="_page_item _page_prev">上一页</span>';
			}
			if(($num_bar['current_page'] - 1) == $next_page){
				$page_bar_ary['next_page'] 	= '<span class="_page_item _page_next">下一页</span>';
			}
			if(($num_bar['current_page'] - 1) == $last_page){
				$page_bar_ary['last_page'] 	= '<span class="_page_item _page_last">最末页</span>';
			}
			
			if($fullset){
				$page_bar_ary['fullset'] = $num_bar['default_style'];
			}

			return join('', $page_bar_ary);
		}
	}

	/**
	 * 数字分页bar
	 *
	 * @param integer $bar_length
	 * @param boolean $fnl; [first] and [last]
	 * @return array; page number bar
	 */
	public function createNumBar($bar_length=10, $fnl=true)
	{
		if(!empty($_GET['current_page'.$this->qs_suffix]) && is_numeric($_GET['current_page'.$this->qs_suffix])){
			$this->page_id = filter_var($this->conn->quote($_GET['current_page'.$this->qs_suffix]), FILTER_SANITIZE_NUMBER_INT);
		}
		// 最大、最小页限制
		if($bar_length >= $this->total_page){
			$startNum 	= 0;
			$lastNum	= $this->total_page - 1;
		}
		if($bar_length < $this->total_page){
			$halfNum   = $bar_length / 2;
			$beforeNum = $halfNum;
			$afterNum  = $halfNum - 1;
			if(($bar_length % 2) != 0){
				$halfNum = floor($halfNum); // 取最小整数
				$beforeNum = $halfNum;
				$afterNum  = $halfNum;
			}
			$startNum = $this->page_id - $beforeNum;
			$lastNum  = $this->page_id + $afterNum;
			
			if($startNum < 0){
				$startNum 	= 0;
				$lastNum	= $bar_length - 1;
			}
			if($lastNum > ($this->total_page - 1)){
				$startNum 	= $this->total_page - $bar_length;
				$lastNum 	= $this->total_page - 1;
			}
		}
		$get_query = '';
		if (!empty($_SERVER['QUERY_STRING'])) {
			$params = explode("&", $_SERVER['QUERY_STRING']);
			$newParams = array();

			foreach ($params as $param){
				if (stristr($param, "current_page".$this->qs_suffix) == false){
					array_push($newParams, $param);
				}
			}

			if (count($newParams) != 0){
				$get_query = htmlentities("&" . implode("&", $newParams), ENT_QUOTES);
			}
		}

		$num = array();
		$page_bar = array();
		if($this->page_id >= $this->total_page){
			$this->page_id = $this->total_page - 1;
		}
		$page_bar['current_page'] = $this->page_id;
		$page_bar['pre_page']     = $this->page_id - 1;
		$page_bar['next_page']    = $this->page_id + 1;

		$pre_page  = '<li><a aria-label="Previous" href="?current_page'. $this->qs_suffix .'='.$page_bar['pre_page'].$get_query.'"><span aria-hidden="true"><</span></a></li>';
		$next_page = '<li><a aria-label="Next" href="?current_page'. $this->qs_suffix .'='.$page_bar['next_page'].$get_query.'"><span aria-hidden="true">></span></a></li>';
		if($page_bar['pre_page'] < 0){
			$page_bar['pre_page'] = 0;
			$pre_page  = '<li class="disabled"><a aria-label="Previous" href="#"><span aria-hidden="true"><</span></a></li>';
		}
		if($page_bar['next_page'] > $lastNum){
			$next_page  = '<li class="disabled"><a aria-label="Next" href="#"><span aria-hidden="true">></span></a></li>';
		}

		for($i=$startNum ; $i<=$lastNum ; $i++){
			$n = $i + 1;
			if($this->page_id == $i){
				$num[] = '<li class="active"><a href="#">'.$n.'</a></li>';
				$page_bar['current_page'] = $n;
			} else {
				$num[] = '<li><a href="?current_page'. $this->qs_suffix .'='.$i.$get_query.'">'.$n.'</a></li>';
			}

			$page_bar['query_string'][] = '?current_page'. $this->qs_suffix .'='.$i.$get_query;
			$page_bar['num'][] = $n;
		}
		$num_bar = join("", $num);

		$page_bar['default_style'] = $pre_page.$num_bar.$next_page;
		$page_bar['first_page']['query_string'] =  '?current_page'. $this->qs_suffix .'=0'.$get_query;
		$page_bar['last_page']['query_string']  =  '?current_page'. $this->qs_suffix .'='.($this->total_page - 1).$get_query;

		if($this->total_page > 0){
			if($fnl){
				$first_page = '<li><a aria-label="First" href="?current_page'. $this->qs_suffix .'=0'.$get_query.'"><span aria-hidden="true"><<</span></a></li>';
				$latest_page = '<li><a aria-label="Last" href="?current_page'. $this->qs_suffix .'='.($this->total_page - 1).$get_query.'"><span aria-hidden="true">>></span></a></li>';
				if($page_bar['current_page'] == 1){
					$first_page  = '<li class="disabled"><a aria-label="First" href="#"><span aria-hidden="true"><<</span></a></li>';
				}
				if($page_bar['current_page'] == $this->total_page){
					$latest_page  = '<li class="disabled"><a aria-label="Last" href="#"><span aria-hidden="true">>></span></a></li>';
				}
				$page_bar['default_style'] = $first_page.$page_bar['default_style'].$latest_page;
			}
			return $page_bar;
		}
	}

	/**
	 * 适用详细页面的上下笔page bar
	 *
	 * @param integer $pk, 预设 primary key栏位名称 id
	 * @param string $mode, DESC 或 ASC
	 * @return array
	 */
	public function detailPageBar($pk, $mode)
	{
		$rec = array();
		
		$mode = strtoupper($mode);
		$exchange_mode = ($mode == 'DESC') ? 'ASC' : 'DESC';
		$compare = ($mode == 'DESC') ? '>' : '<';
		$exchange_compare = ($mode == 'DESC') ? '<' : '>';

		// 第一笔
		$sth = parent::prepare(sprintf('SELECT %s FROM %s ORDER BY %s %s LIMIT 1', 
								$pk, 
								$this->table, 
								$pk, 
								$mode));
		$sth->execute();
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if($pk != $row[$pk]) $rec['first'] = $row[$pk];

		// 上一笔
		$sth = parent::prepare(sprintf('SELECT %s FROM %s WHERE %s %s ? ORDER BY %s %s LIMIT 1', 
								$pk, 
								$this->table, 
								$pk, 
								$compare, 
								$pk, 
								$exchange_mode));
		$sth->bindParam(1, $pk, PDO::PARAM_INT);
		$sth->execute();

		if($sth->rowCount() > 0){
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$rec['prev'] = $row[$pk];
		}

		// 下一笔
		$sth = parent::prepare(sprintf('SELECT %s FROM %s WHERE %s %s ? ORDER BY %s %s LIMIT 1',
								$pk, 
								$this->table, 
								$pk, 
								$exchange_compare, 
								$pk, 
								$mode));
		$sth->bindParam(1, $pk, PDO::PARAM_INT);
		$sth->execute();

		if($sth->rowCount() > 0){
			$row = $sth->fetch(PDO::FETCH_ASSOC);
			$rec['next'] = $row[$pk];
		}

		// 第终笔
		$sth = parent::prepare(sprintf('SELECT %s FROM %s ORDER BY %s %s LIMIT 1',
								$pk, 
								$this->table, 
								$pk, 
								$exchange_mode));
		$sth->execute();
		$row = $sth->fetch(PDO::FETCH_ASSOC);
		if($pk != $row[$pk]) $rec['latest'] = $row[$pk];

		return $rec;

	}

	/**
	 * 显示记录计数
	 *
	 * @return string record infomation...
	 */
	public function recordInfo($defaultStyle='')
	{
		$total_page = 1;
		if(empty($defaultStyle)){
			$defaultStyle = '页次 %d / %d , 本页显示 %d ~ %d 笔 , 全部共 %d 笔纪录';
		}
		if($this->total_page > 1){
			$total_page = $this->total_page;
		}
		$record_info = array();

		if($this->all_rows > 0){
			$record = sprintf($defaultStyle,
			$this->start_page+1,
			$total_page,
			$this->start_row+1,
			$this->max_row,
			$this->all_rows);

			$record_info['default_style'] 		= $record;

			$record_info['current_page'] 		= $this->start_page+1;
			$record_info['total_page'] 			= $total_page;
			$record_info['current_record'] 		= $this->start_row+1;
			$record_info['current_record_max'] 	= $this->max_row;
			$record_info['total_records'] 		= $this->all_rows;

			return $record_info;
		}
	}
	
	
	/**
	 *  lastInsertId()
	 *
	 * @return integer
	 */
	public function getLastInsertId(){
		$lastInsertId = $this->conn->lastInsertId();
		if(!empty($lastInsertId) && $lastInsertId > 0){
			$this->last_insert_id = $lastInsertId;
		}
		return $this->last_insert_id;
	}
	
	
	/**
	 * LOCK table
	 *
	 * @param string $mode, 'w'=LOW_PRIORITY WRITE, 'r'=READ
	 */
	public function lockTable($mode)
	{
	 switch ($mode){
	 	case "w":
	 		$mode = 'LOW_PRIORITY WRITE';
	 		break;

	 	case 'r':
	 		$mode = 'READ';
	 		break;
	 }

	 $this->sth = parent::query(sprintf('LOCK TABLES %s %s', $this->table, $mode));
	}

	/**
	 * UNLOCK table
	 *
	 */
	public function unlockTable()
	{
		parent::query('UNLOCK TABLES');
		$this->sth = parent::query('UNLOCK TABLES');
	}
	
	
	/**
	 * 清空资料表
	 * @param string $table, table name
	 */
	public function truncate($table)
	{
		if(!empty($table)){
			parent::query(sprintf("TRUNCATE TABLE %s", $table));
		}else{
			parent::query(sprintf("TRUNCATE TABLE %s", $this->table));
		}
		
	}
	
	
	/**
	 * 取得table所有的comment
	 * @param string $field ; tabel field
	 * @return array field Comments (index:Field)
	 */
	public function getTableComment($field='')
	{
		$col_name = array();
		$this->sth = parent::query(sprintf('SHOW FULL FIELDS FROM %s', $this->table));
		// echo 'jovi:'.$this->sth->queryString;
		$this->execute();
		if ($this->total_row > 0) {
			do{
				$col_name[$this->row['Field']] = $this->row['Comment'];
				// echo '<hr />';
				// echo $this->row['Field'].'--'.$this->row['Comment'];
			}while($this->row = $this->fetchAssoc());
			
			if(isset($col_name[$field]) && $col_name[$field] != '' && $field != ''){
				return $col_name[$field];
			}else{
				return $col_name;
			}
		}
		return false;
	}
	
	/**
	 * get ENUM field columns
	 * 
	 * 取得enum型态内容
	 * @param String $field 栏位名称
	 * @return Array
	 */
	public function enumSelect($field){
		
		$this->sth = parent::query(sprintf('SHOW COLUMNS FROM %s LIKE %s', $this->table, parent::quote($field)));
		$this->execute();
		if ($this->total_row > 0) {
			$enum_array = array();
			$regex = "/'(.*?)'/";
			preg_match_all($regex, $this->row['Type'], $enum_array);
			$enum_fields = $enum_array[1];
			return $enum_fields;
		}
		return false;
	}
	
	
	/**
	 * 防止SQL injection
	 *
	 * @param string $content
	 * @return string or array
	 */
// 	function quote($content){
// 		if (is_array($content)) {
// 			foreach ($content as $key=>$value) {
// 				if(is_array($value)){
// 					$content[$key] = $this->quote($value);
// 				}else{
// 					// 去除斜杠
// 					if (get_magic_quotes_gpc()){
// 						$value = stripslashes($value);
// 					}
// 					$content[$key] = mysql_real_escape_string($value);
// 				}
// 			}
// 		} else {
// 			// 去除斜杠
// 			if (get_magic_quotes_gpc()){
// 				$content = stripslashes($content);
// 			}
// 			if (!is_numeric($content)){
// 				$content = mysql_real_escape_string($content);
// 			}
// 		}
// 		return $content;
// 	}
	
}

?>
