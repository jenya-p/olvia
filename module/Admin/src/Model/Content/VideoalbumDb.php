<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\OptionsModel;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;

class VideoalbumDb extends Table implements CRUDListModel, Multilingual, Historical, OptionsModel {
	
	use MultilingualTrait, HistoricalTrait;
	
	protected $table = 'content_videoalbums';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['title', 'seo_title', 'seo_description', 'seo_keywords']);
		$this->history('videoalbum');
	}


	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['va' => 'content_videoalbums']);		

		if(!empty($filter['query'])){
			$select->where->expression('LOWER(va.title_'.$this->lang().') like ?', mb_strtolower($filter['query']."%"));
		}
			
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(va.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		
		$select
			->join(['v' => 'content_videos'], 'v.videoalbum_id = va.id', ['video_count' => new Expression('count(v.id)')], Join::JOIN_LEFT)
			->group('va.id');
		
		
		$select->limit($ipp)->offset(($p-1)*$ipp);
		$select->order('va.priority desc');
		$select->order('va.id asc');
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		return parent::buildItem($item);
	}

	public function insert($insert){
    	if(empty($insert['created'])){
    		$insert['created'] = time();
    	}
    	return parent::insert($insert);    	
    }	

    public function getChildCount($id){
    	$select = new Select(['va' => 'content_videoalbums']);
    	$select->columns(['count' => new Expression('count(*)')]);
    	$select->where->expression('videoalbum_id = ?', $id);
    	return $select->fetchOne();
    }

    // Options Model implementation
    var $options = null;
    public function options() {
    	if($this->options === null){
    		$select = new Select(['va' => $this->table]);
    		$select->columns(['id', 'title' => 'title_ru']);
    		$select->order('title_ru ASC');
    		$this->options = $select->fetchPairs();
    	}
    	return $this->options;
    }
    
    public function option($key) {
    	if($this->options === null){
    		$this->options();
    	}
    	return $this->options[$key];
    }
	
		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {	
		$historyWriter = $this->getHistoryWriter($newValues, $oldValues, $id);					
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader($id);		
		return $historyReader->getRecordsByDate();
	}

	// Misc	
	public function getStat($id){
		$historyReader = $this->getHistoryReader($id);
		return $historyReader->getStat();
	}
		
}
