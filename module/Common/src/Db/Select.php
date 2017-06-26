<?

namespace Common\Db;

use Common\Db\Adapter;
use Zend\Db\Adapter\Adapter as ZendAdapter;
use Zend\Db\Sql\Sql;


class Select extends \Zend\Db\Sql\Select{
	
	
	var $adapter = null;
	public function __construct($table = null, Adapter $adapter = null){
		if($adapter == null){
			$this->adapter = Adapter::getDefaultDbAdapter();
		} else {
			$this->adapter = $adapter;			
		}
		
		return parent::__construct($table);
	}
	
	/**
	 * @return \Common\Db\Adapter	*/
	public function getAdapter(){
		return $this->adapter;
			
	}
		
	/**
	 * @return array 
	 * */
	public function fetchRow(){
		return $this->getAdapter()->fetchRow($this);
	}
	
	/**
	 * */
	public function fetchOne(){
		return $this->getAdapter()->fetchOne($this);		
	}
	
	/**
	 * @param Select $select
	 * @return array
	 */
	public function fetchAll(){
		return $this->getAdapter()->fetchAll($this);
	}
	
	/**
	 * @param Select $select
	 * @return array
	 */
	public function fetchPairs(){
		$pairs = [];
		$result = $this->getAdapter()->fetchAll($this);
		
		foreach ($result as $item){
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
	 * @param Select $select
	 * @return array
	 */
	public function fetchGroups($groupColumn = null){
		$result = $this->getAdapter()->fetchGroups($this, [], $groupColumn);
		return $result;
	}
	
	
	
	/**
	 * @param Select $select
	 * @return array
	 */
	public function fetchColumn($num = 0){
		$column = [];
		$result = $this->getAdapter()->fetchAll($this);
	
		foreach ($result as $item){
			if(!isset($key)){
				$key = array_keys($item)[$num];
			}
			$column[] = $item[$key];
		}
		return $column;
	}
	
	
	/**
	 * @return array
	 * */
	public function toString(){
		$sql = new Sql($this->getAdapter());
		echo $sql->buildSqlString($this);		
	} 
}