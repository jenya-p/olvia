<?php 

namespace Admin\Model\Users;

use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\MultilingualTrait;
use Common\Db\HistoricalTrait;
use Common\CRUDListModel;
use Common\Db\OptionsModel;
use Common\Db\Multilingual;
use Common\Db\Historical;
use Common\Db\Select;
use Zend\Db\Sql\Expression;

class MasterDb extends Table implements CRUDListModel, OptionsModel, Multilingual, Historical{
	
	use MultilingualTrait, HistoricalTrait, UserHistoryTrait;
	
	protected $table = 'users_masters';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['name', 'summary', 'body', 'consultation', 'education', 'seo_title', 'seo_description', 'seo_keywords']);
        $this->history('master');
    }
    
    
    public function get($id){
    	$id = $this->id($id);
    	if(isset($this->cache[$id])){
    		return $this->cache[$id];
    	}
    	 
    	$select = new Select(['m' => $this->table]);
    	$select->join(['a' => 'users_accounts'], 'a.id = m.id',  ['displayname', 'email'], Select::JOIN_LEFT);
    	
    	$select->where->equalTo('m.id', $id);    	
    	$item = $this->getAdapter()->fetchRow($select);
    	
    	$this->buildItem($item);
    	 
    	$this->cache[$id] = $item;
    
    	return $item;
    }
        
	/**
    * @param array $filter
    * @return Select
    */
    public function getSelect($filter){
    
    	$select = new Select(['m' => $this->table]);
    	
    	$select->join(['a' => 'users_accounts'], 'a.id = m.id',  ['email', 'displayname'], Select::JOIN_LEFT);
    	
    	if(!empty($filter['query'])){    		
    		$nest = $select->where->nest();
    		
    		$nest->expression('concat(" ", LOWER(m.name_ru)) like ?', "% ".mb_strtolower($filter['query']."%"))
    			->or->expression('LOWER(a.login) = ?', mb_strtolower($filter['query']))
    			->or->expression('concat(" ", LOWER(a.displayname)) like ?', "% ".mb_strtolower($filter['query']."%"));
    		
   			if(is_numeric($filter['query'])){
   				$nest->or->equalTo('m.id', $filter['query']);
   			}
    	}
    	return $select;
    }
    
    public function getTotals($filter){    	
    	$select = $this->getSelect($filter);
    	$select->reset(Select::COLUMNS)
    		->columns(['count' => new Expression('count(m.id)')]);
    	
    	return $select->fetchRow();
    }
    
    public function getItems($filter, $p = 1, $ipp = 100){
    	$select = $this->getSelect($filter);
    	$select->limit($ipp)->offset(($p-1)*$ipp);
    	
    	$select
    		->order('m.priority desc')
    		->order('m.name_ru asc');
    	
    	$items = $select->fetchAll();
    	foreach ($items as &$item){
    		$this->buildItem($item);
    	}
    	return $items;
    }
    
    public function buildItem(&$item){
    	return parent::buildItem($item);
    }
    
    
    public function options() {
    	return null;
    }
	
    public function option($key) {
		$user = $this->get($key);
		if($user != null ){
			return $user['displayname'];
		} else {
			null;
		}
	}

	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
		$historyWriter->setSkipDataFor(['body', 'consultation', 'education', 'seo_description', 'seo_keywords']);
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		return $historyReader->getRecordsByDate();
	}
	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		$stat = $historyReader->getStat();
		$item = $this->get($id);
		$stat['views'] = $item['views'];
		return $stat;
	}
	
}
