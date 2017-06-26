<?
namespace Admin\Model\Orders;

use Admin\Model\CommentsDb;
use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Select;
use Common\Db\Table;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\Db\DiscussionTrait;
use Common\Db\Discussion;

class CallDb extends Table implements CRUDListModel, Historical, Discussion, ServiceManagerAware {
	
	use HistoricalTrait, DiscussionTrait, ServiceManagerTrait;
	
	const STATUS_NEW = 'new';
	const STATUS_DONE = 'done';
	const STATUS_DECLINE = 'decline';
	
	protected $table = 'order_call';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->history('order_call');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['ocl' => 'order_call']);		

		$select->join(['a' => 'users_accounts'], 'a.id = ocl.user_id',  ['email', 'displayname'], Select::JOIN_LEFT);
		$select->join(['c' => 'users_customers'], 'c.id = ocl.user_id',  ['customer_id' => 'id', 'customer_name' => 'name'], Select::JOIN_LEFT);
		
		if(!empty($filter['query'])){
			$nest = $select->where->nest();
			
			$nest->
						expression('concat(" ", LOWER(ocl.name)) like ?', "% ".mb_strtolower($filter['query']."%"))
				->or->	expression('concat(" ", LOWER(a.displayname)) like ?', "% ".mb_strtolower($filter['query']."%"))
				->or->	expression('LOWER(a.login) = ?', mb_strtolower($filter['query']))
				->or->	expression('concat(" ", LOWER(c.name)) like ?', "% ".mb_strtolower($filter['query']."%"));
				
		}
		
		if($filter['status'] == 'new'){
			$select->where->and->equalTo('ocl.status', CallDb::STATUS_NEW);
		}
				
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(ocl.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$sort = $filter['sort'];
		if(!empty($sort) && is_array($sort) && count($sort) == 2){
			$select->order($sort[0]. ' '.$sort[1]);
		} else {
			$select->order('ocl.id desc');
		}
		
		return $this->fetchAll($select);		
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
		$historyWriter->setSkipDataFor(['message']);					
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);
		$historyReader->addDictionary('status', $this->statusOptionsNames);
		$dic = [$historyReader, 'getUserDescription'];
		$historyReader->addDictionary('user_id', $dic);
		
		return $historyReader->getRecordsByDate();
	}

	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		$stat = $historyReader->getStat();		
		return $stat;
	}

	
	var $statusOptionsNames = [
			self::STATUS_NEW => 	'Новая',
			self::STATUS_DONE =>  	'Выполнена',
			self::STATUS_DECLINE => 'Отмена',
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
