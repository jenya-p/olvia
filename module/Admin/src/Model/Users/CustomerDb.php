<?php 

namespace Admin\Model\Users;

use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\CRUDListModel;
use Common\Db\OptionsModel;
use Common\Db\Historical;
use Common\Db\Select;
use Zend\Db\Sql\Expression;
use Common\Db\HistoricalTrait;
use Common\Db\Discussion;
use Common\Db\DiscussionTrait;

class CustomerDb extends Table implements CRUDListModel, OptionsModel, Historical, Discussion{

	use HistoricalTrait, DiscussionTrait;
	
    protected $table = 'users_customers';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->history('customer');
    }
    

    public function get($id){
    	$id = $this->id($id);
    	if(isset($this->cache[$id])){
    		return $this->cache[$id];
    	}
    
    	$select = new Select(['c' => $this->table]);
    	$select->join(['a' => 'users_accounts'], 'c.id = a.id',  ['displayname'], Select::JOIN_LEFT);
    	
    	$select->where->equalTo('c.id', $id);
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
    
    	$select = new Select(['c' => $this->table]);
    	 
    	$select->join(['a' => 'users_accounts'], 'a.id = c.id',  ['displayname'], Select::JOIN_LEFT);
    	 
    	if(!empty($filter['query'])){
    		$q = mb_strtolower($filter['query']);
    		$nest = $select->where->nest();
    		$nest->expression('concat(" ", LOWER(a.displayname)) like ?', "% ".$q."%")
	    		->or->expression('LOWER(a.login) = ?', $q)
	    		->or->expression('concat(" ", LOWER(c.name)) like ?', "% ".$q."%");
    			
    	}
    	 
    	return $select;
    }
    
    public function getTotals($filter){
    	$select = $this->getSelect($filter);
    	$select->reset(Select::COLUMNS)
    	->columns(['count' => new Expression('count(c.id)')]);
    	 
    	return $select->fetchRow();
    }
    
    public function getItems($filter, $p = 1, $ipp = 100){
    	$select = $this->getSelect($filter);
    	$select->limit($ipp)->offset(($p-1)*$ipp);
    	 
    	$select->order('c.id asc');
    	 
    	$items = $select->fetchAll();
    	foreach ($items as &$item){
    		$this->buildItem($item);
    	}
    	return $items;
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
    
    public function getStat($id){
    	$historyReader = $this->getHistoryReader($id);
    	$stat = $historyReader->getStat();    	
    	return $stat;
    }
    
    public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
    	$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);
    	$historyWriter->setSkipDataFor('description');
     	$historyWriter->writeAll();
    }
    
    public function readHistory($id) {
    	$historyReader = $this->getHistoryReader($id);
    	$dic = ['f' => 'Жен', 'm' => 'Муж'];
    	$historyReader->addDictionary('sex', $dic);
    	$historyReader->addDictionary('birthday', [$historyReader, 'dateDescription']);
    	return $historyReader->getRecordsByDate();
    }
    
}
