<?
namespace Admin\Model\Content;

use Common\CRUDListModel;
use Common\Db\Historical;
use Common\Db\HistoricalTrait;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Expression;

class BannerDb extends Table implements CRUDListModel, Multilingual, Historical {
	
	use MultilingualTrait, HistoricalTrait;
	
	protected $table = 'content_banners';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['body']);
		$this->history('banner');
	}
	

	// CRUD list implementation
	/**
	 * @param array $filter
	 * @return Select
	 */
	public function getSelect($filter){
	
		$select = new Select(['b' => 'content_banners']);		

		if(!empty($filter['query'])){
			$nest = $select->where->nest();
			$q = mb_strtolower($filter['query']);
			$nest->expression('concat(" ", LOWER(b.alias)) like ?', "% ".$q."%")->
				or->expression('concat(" ", LOWER(b.link)) like ?', "%".$q."%");
		}
		
		return $select;
	}
	
	public function getTotals($filter){
		
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(b.id)')]);
		return $select->fetchRow();
		
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
		
		$sort = $filter['sort'];
		if(!empty($sort) && is_array($sort) && count($sort) == 2){
			$select->order('b.'.$sort[0]. ' '.$sort[1]);
		} else {
			$select->order('b.id desc');
		}		
		
		$items = $select->fetchAll();
		foreach ($items as &$item){
			$this->buildItem($item);
		}
		return $items;
	}
		
	public function buildItem(&$item){
		return parent::buildItem($item);
	}

		
	// History Model implementation
	public function saveHistory(array $newValues = null, array $oldValues = null, $id = null) {
		$historyWriter = $this->getHistoryWriter();
		$historyWriter->reset('banner', $newValues, $oldValues, $id);
		$historyWriter->setSkipDataFor(['body_ru', 'body_en']);
		$historyWriter->writeAll();
	}
	
	public function readHistory($id) {
		$historyReader = $this->getHistoryReader();
		$historyReader->reset('banner', $id);		
		return $historyReader->getRecordsByDate();
	}

	public function getStat($id){
		$historyReader = $this->getHistoryReader();
		$historyReader->reset('banner', $id);
		$stat = $historyReader->getStat();				
		return $stat;
	}
		
}
