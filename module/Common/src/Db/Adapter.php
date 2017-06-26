<?

namespace Common\Db;

use Zend\Db\Adapter\Adapter as ZendAdapter;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Sql;


class Adapter extends ZendAdapter {
	
	static $_defaultDbAdapter = null;
	static public function setDefaultDbAdapter(Adapter $adapter){
		self::$_defaultDbAdapter = $adapter;
	}
	
	static public function getDefaultDbAdapter(){
		return self::$_defaultDbAdapter;
	}
	
	/**
	 * @param Select|string $select
	 * @param array $parameters
	 * */
	public function fetchRow($select, $parameters = null){
		$sql = new Sql($this);		
		if(is_string($select)){
			return $this->createStatement($select)->execute($parameters)->next();
		} else {
			return $sql->prepareStatementForSqlObject($select)->execute($parameters)->next();
		}
	}
	
	/**
	 * @param Select|string $select
	 * @param array $parameters
	 * @return array
	 * */
	public function fetchPairs($select, $parameters = null){
		$sql = new Sql($this);
		if(is_string($select)){
			$results = $this->createStatement($select)->execute($parameters);
		} else {
			$results = $sql->prepareStatementForSqlObject($select)->execute($parameters);
		}
		$resultSet = new ResultSet();
		$resultArr = $resultSet->initialize($results)->toArray();
		$pairs = [];
		foreach ($resultArr as $item){
			if(!isset($key1)){
				$key2 = array_keys($item);
				$key1 = $key2[0];
				$key2 = $key2[1];
			}
			$pairs[$item[$key1]] = $item[$key2];
		}		
		return $pairs;
	}
	
	
	/**
	 * @param Select|string $select
	 * @param array $parameters
	 * @return array
	 * */
	public function fetchOne($select, $parameters = null){
		$sql = new Sql($this);
		if(is_string($select)){
			$res = $this->createStatement($select)->execute($parameters)->next();
		} else {
			$res = $sql->prepareStatementForSqlObject($select)->execute($parameters)->next();
		}
		return $res[array_keys($res)[0]];
	}
	
	/**	 
	 * @param Select|string $select
	 * @param array $parameters
	 * @return array
	 */
	public function fetchAll($select, $parameters = null){
		$sql = new Sql($this);
		if(is_string($select)){
			$results = $this->createStatement($select)->execute($parameters);
		} else {
			$results = $sql->prepareStatementForSqlObject($select)->execute($parameters);			
		}
		$resultSet = new ResultSet();
		return $resultSet->initialize($results)->toArray();
	}

	
	/**
	 * @param Select|string $select
	 * @param array $parameters
	 * @return array
	 */
	public function fetchGroups($select, $parameters = null, $groupColumn = null){
		$resultArr = $this->fetchAll($select, $parameters = null);
		if(empty($resultArr)) return [];
		
		$result = [];
		$current = null;
		$currentGroup = null;
		foreach ($resultArr as $item){
			if(empty($groupColumn)){
				$groupColumn = array_keys($item)[0];
			}
			if($current != $item[$groupColumn] || $currentGroup === null){
				$current = $item[$groupColumn];
				unset($currentGroup);
				$currentGroup = [
					$groupColumn => $current,
					'items' => [$item]
				];
				$result[] = &$currentGroup;
			} else {
				$currentGroup['items'][] = $item;
			}				
		}
		return $result;
		
	}
	
	public function fetchColumn($select, $parameters = null, $num = 0){
		$column = [];
		$result = $this->fetchAll($select, $parameters);
	
		foreach ($result as $item){
			if(!isset($key)){
				$key = array_keys($item)[$num];
			}
			$column[] = $item[$key];
		}
		return $column;
	}
	
	
	/** 
	 * @param string $table
	 * @param array $values
	 * @return long
	 */
	public function insert($table, $values){
		$sql = new Sql($this);
		$insert = $sql->insert($table)->values($values);
		$sql->prepareStatementForSqlObject($insert)->execute();
		return $this->getDriver()->getLastGeneratedValue();	
	}
	
	/**
	 * @param string $table
	 * @param array $values
	 * @param long $id
	 * @return long
	 */
	public function updateOne($table, $id, $values){
		$sql = new Sql($this);
		$update = $sql->update($table)->set($values);
		$update->where->expression('id = ? ', $id);
		return $sql->prepareStatementForSqlObject($update)->execute();
	}
	
	
	public function sql($sql, array $parameters = null){
		return $this->createStatement($sql)->execute($parameters);
	}
	
	
	public function delete($table, $id){	
		$sql = new Sql( $this );
		$delete = $sql
			->delete($table)
			->where->expression("id = ? ", $id);
		return $sql->prepareStatementForSqlObject($delete)->execute();		
	}
	
	public function datetime($time = null){
		if($time == null){
			return new Expression("NOW()");
		} else {
			return new Expression("FROM_UNIXTIME(?)", $time);
		}		
	}
	
	public function datetimeStr($time = null){
		if($time == null) return null;
		else {
			return date('Y-m-d H:i:s', $time);
		}
	}	
	
	public function timestamp($datetime){
		return strtotime($datetime);		
	}
	
	

}