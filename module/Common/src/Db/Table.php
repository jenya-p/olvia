<?
namespace Common\Db;

use Common\Db\Adapter;
use Zend\Db\Sql\Select as SqlSelect;
use Zend\Db\Sql\Sql;
use Zend\Db\TableGateway\AbstractTableGateway;


class Table extends AbstractTableGateway {
	
	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;	
	}
	
	/**
	 * @return \Common\Db\Adapter	*/
	public function getAdapter(){
		return parent::getAdapter();
	}
	
	/**
	 * @return array 
	 * */
	public function fetchRow($select){		
		$item = $this->getAdapter()->fetchRow($select);
		return $this->buildItem($item);
	}
	
	/**
	 * @return array
	 * */
	public function fetchAll($select){
		$items = $this->getAdapter()->fetchAll($select);
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
	
	var $cache = [];
	
	public function get($id){
		if(is_array($id) && is_numeric($id['id'])){
			$id = $id['id'];
		}
		if(!is_numeric($id)){return null;}		
		if(!isset($this->cache[$id])){
			$select = new SqlSelect($this->table);
			$select->where->equalTo('id', $id);
			$this->cache[$id] = $this->getAdapter()->fetchRow($select);
			$this->buildItem($this->cache[$id]);
		}
		return $this->cache[$id];
	}
	
	public function buildItem(&$item){
		if(!empty($item) && $this instanceof Multilingual){
			$this->concretLanguage($item);
		}
		if(!empty($item) && $this instanceof Discussion && !isset($item['comments_info'])){
			$item['comments_info'] = $this->getCommentsInfo($item['id']);
		}
		return $item;
	}
	
	public function getNextId(){
		return $this->getAdapter()->fetchOne('select max(id) from '.$this->table) + 1;
	}
	
	public function clearCache($id = null){
		if($id === null){
			$this->cache = [];
		} else {
			unset($this->cache[$id]);
		}
		return $this;
	}
	
	public function insert($insert){		
		if($this instanceof Multilingual){			
			$this->abstractLanguage($insert);
		}
		$res = parent::insert($insert);
		if($res!=0){
			$id = $this->lastInsertValue;
			if($this instanceof Historical){
				$this->getHistoryWriter($insert, null, $id)->write('_create');
				$this->saveHistory($insert, null, $id);				
			}
			return $id;
		} else {
			return null;
		}
	}
	
	public function updateOne($update, $id){		
		if($this instanceof Multilingual){
			$this->abstractLanguage($update);
		}
		if($this instanceof Historical){
			$oldValues = $this->get($id);
		}
		
		$result = parent::update($update, "id=$id");
		if($this instanceof Historical){
			$this->saveHistory($update, $oldValues, $id);
		}
		
		unset($this->cache[$id]);
		return $result;
	}
	
	public function deleteOne($id){
		$res = parent::delete("id=$id");

		if($res != 0 && $this instanceof Historical){
			$this->getHistoryWriter(null, null, $id)->write('_delete');
		}
		return $res;
	}
	
	/**
	 * @return string
	 * */
	public function sql2Str(SqlSelect $select){
		$sql = new Sql($this);
		echo $sql->buildSqlString($select);		
	}
	
	
	/**
	 * Извлекаем ID из сущности
	 * @param unknown $item
	 * @return unknown|NULL
	 */
	public function id($item){
		if(is_numeric($item)) {
			return $item;
		} else if(is_array($item) && is_numeric($item['id'])){
			return $item['id'];
		}
		return null;	
	}
	
	
	/**
	 * Если поле alias изменено, проверяем его уникальность, и если нужно, добавляем в него ID или рандомные числа. 
	 * @param string $alias
	 * @param string $id
	 * @throws \Exception
	 * @return string
	 */
	public function uniqueAlias($alias, $id = null){
		// Если алиас не изменен, то возвращаем как есть.
		if(isset($this->cache[$id])){
			if($this->cache[$id]['alias'] == $alias){
				return $alias;
			}
		}		
		$select = new Select(['t' => $this->table]);
		$sql = '';
		if(!empty($id) && is_numeric($id)){
			$select->where->notEqualTo('id', $id);
			$sql = ' AND id != '.$id.' ';
		} else {
			$id = $this->getNextId();
		}
		$sql = 'select count(*) from '.$this->table.' where alias = :alias '.$sql;
		
		if($this->getAdapter()->fetchOne($sql, ['alias' => $alias]) == 0 ){			
			return $alias;
		}
		// пробуем добавить ID к алиасу
		$alias2 = $alias.'-'.$id;
		for($i = 0; $i < 5; $i++){
			if($this->getAdapter()->fetchOne($sql, ['alias' => $alias2]) == 0){
				return $alias2;
			}
			// Пробуем добавляем рандомные числа, 3 попытки или эксепшн
			$alias2 = $alias.'-'.$id.'-'.rand(100,999);			
		}
		throw new \Exception('unique alias not found for '.$this->table);
	}

	/**
	 * Проверяем уникальность alias
	 * @param string $alias
	 * @param string $id
	 * @return boolean
	 */ 
	public function checkAlias($alias, $id = null){
		if(isset($this->cache[$id])){
			if($this->cache[$id]['alias'] == $alias){
				return true;
			}
		}
		$select = new Select(['t' => $this->table]);
		$sql = '';
		if(!empty($id) && is_numeric($id)){
			$select->where->notEqualTo('id', $id);
			$sql = ' AND id != '.$id.' ';
		}
		$sql = 'select count(*) from '.$this->table.' where alias = :alias '.$sql;
		return $this->getAdapter()->fetchOne($sql, ['alias' => $alias]) == 0;
	}
	
}