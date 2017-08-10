<?
namespace Admin\Model\Users;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\Db\Discussion;
use Common\Db\DiscussionTrait;
use Common\Traits\IdentityAware;
use Common\Traits\IdentityTrait;

class TodoDb extends Table implements CRUDListModel, Historical, Discussion, IdentityAware  {
	
	use HistoricalTrait, DiscussionTrait, IdentityTrait;
	
	protected $table = 'todos';

	const STATUS_NEW = 'new';
	const STATUS_DONE = 'done';
	const STATUS_DECLINE = 'decline';
	const STATUS_DEFERRED = 'deferred';
	
	
	
	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->history('todo');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['t' => 'todos']);		

		if(!empty($filter['query'])){
			$nest = $select->where->nest();
			
			$nest->where->expression('concat(" ", LOWER(t.title)) like ?', "% ".mb_strtolower($filter['query']."%"));
			if(is_numeric($filter['query'])){
				$nest->or->expression('a.id = ?', $filter['query']);
			}	
		}
			
		
		if(!empty($filter['user'])){
			if($filter['user'] == 'self'){
				
				$nest = $select->where->nest();
				$nest->equalTo('t.user_id', $this->identity()->id)
					->or->isNull('t.user_id');
				
			} else if($filter['user'] == 'empty'){
				$select->where->isNull('t.user_id');
				
			} else if(is_numeric($filter['user'])){
				$select->equalTo('t.user_id', $filter['user']);
				
			}
			
		}
		
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(t.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$select->join(['a' => 'users_accounts'], 'a.id = t.user_id',  ['user_name' => 'displayname'], Select::JOIN_LEFT);
		
		$sort = $filter['sort'];
		if(!empty($sort) && is_array($sort) && count($sort) == 2){
			
			if(in_array($sort[0], ['user_name'])){
				$sort[0] = 'a.displayname';			
			} else {
				$sort[0] = 't.'.$sort[0];
			}
			
			$select->order($sort[0]. ' '.$sort[1]);
		} else {
			$select
				->order('t.priority DESC')
				->order('t.intensity ASC');
		}
		
		
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		$item['status_name'] = $this->statusOption($item['status']);
		return parent::buildItem($item);
	}

	public function insert($insert){
    	return parent::insert($insert);    	
    }	

	
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {	
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);	
		// $historyWriter->setSkipDataFor(['']);							
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		
		$historyReader->addDictionary('status', $this->statusOptionsNames);
		
		return $historyReader->getRecordsByDate();
	}

	// Misc	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		$stat = $historyReader->getStat();		
		return $stat;
	}
	
	
	var $statusOptionsNames = [
			self::STATUS_NEW => 	 	'В работе',
			self::STATUS_DONE =>  	 	'Завершена',
			self::STATUS_DECLINE =>  	'Отмена',
			self::STATUS_DEFERRED =>  	'Отложена',
	];
	
	public function statusOption($key){
		if( array_key_exists($key, $this->statusOptionsNames)){
			return $this->statusOptionsNames[$key];
		} else {
			return $key;
		}
	}
	
	public function statusOptions(){
		return $this->statusOptionsNames;
	}
	
		
}
