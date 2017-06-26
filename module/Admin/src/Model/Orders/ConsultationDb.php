<?
namespace Admin\Model\Orders;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Common\ViewHelper\Phone;
use Common\Db\Discussion;
use Common\Db\DiscussionTrait;
use Admin\Model\Users\UserDb;
use Admin\Model\Users\MasterPricesDb;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Zend\Db\Sql\Join;

class ConsultationDb extends Table implements CRUDListModel, Historical, Discussion, ServiceManagerAware {
	
	use HistoricalTrait, DiscussionTrait, ServiceManagerTrait;
		
	const STATUS_NEW = 'new';
	const STATUS_DONE = 'done';
	const STATUS_DECLINE = 'decline';
	const STATUS_ARCHIVE = 'archive';
	
	protected $table = 'order_consultations';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->history('order_consultation');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['oc' => $this->table]);		

		$select->join(['ua' => 'users_accounts'], 'ua.id = oc.user_id', ['user_displayname' => 'displayname'], Join::JOIN_LEFT);
		$select->join(['uc' => 'users_customers'], 'uc.id = oc.user_id', ['customer_id' => 'id', 'customer_name' => 'name'], Join::JOIN_LEFT);
		
		if(!empty($filter['query'])){
			$nest = $select->where->nest(); 
			$nest->expression('concat(" ", LOWER(oc.name)) like ?', "% ".mb_strtolower($filter['query']."%"))
				->or->expression('LOWER(oc.skype) like ?', mb_strtolower($filter['query']."%"))
				->or->equalTo('oc.phone', Phone::normalize($filter['query']))
				->or->expression('LOWER(ua.email) like ?', mb_strtolower($filter['query']."%"));
			
		}
		
		if($filter['status'] == 'new'){
			$select->where->and->equalTo('oc.status', CallDb::STATUS_NEW);
		}
			
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(oc.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$sort = $filter['sort'];
		if(!empty($sort) && is_array($sort) && count($sort) == 2){
			$select->order($sort[0]. ' '.$sort[1]);
		} else {
			$select->order('oc.id desc');
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
    	$historyWriter->setSkipDataFor(['message']);
    	$historyWriter->writeAll();
    }
    
    public function readHistory($id) {
    	$historyReader = $this->getHistoryReader($id);
    	$dir1 = [$historyReader, 'getUserDescription'];
    	$historyReader->addDictionary('master_id', $dir1);
    	$historyReader->addDictionary('user_id', $dir1);
    	
    	$masterPricesDb = $this->serv(MasterPricesDb::class);
    	$dir2 = [$masterPricesDb, 'getDescription'];
    	$historyReader->addDictionary('tarif_id', $dir2);
    	
    	$dir3 = [$historyReader, 'dateDescription'];
    	$historyReader->addDictionary('meet_date', $dir3);
    	
    	$historyReader->addDictionary('status', $this->statusOptionsNames);
    	
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
    		self::STATUS_ARCHIVE => 'Архив',
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
