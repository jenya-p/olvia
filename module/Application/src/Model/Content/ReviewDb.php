<?
namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Select;
use Zend\Db\Sql\Expression;
use Admin\Model\Content\ReviewRefsDb;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Zend\Db\Sql\Join;

class ReviewDb extends Table implements Multilingual, ServiceManagerAware{
	
	use MultilingualTrait, ServiceManagerTrait;
		
	protected $table = 'content_reviews';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['name', 'body']);
	}

	public function getSelect($filter){
	
		$select = new Select(['r' => 'content_reviews']);
	
		$select->where->expression('r.status = ?', 1);
		
		if(is_array($filter['subject'])){
			
			$subject = $filter['subject']; 
			$select->join(['rr' => 'content_review_refs'], 'rr.review_id = r.id', [], Join::JOIN_INNER);
			$select->where
					->equalTo('rr.entity', $subject['entity'])->
				and	->equalTo('rr.item_id', $subject['item_id']);
			
		}
		
		if(!empty($filter['home'])){
			$select->where->and->expression('r.home = ?', 1);
		}
					
		return $select;
	}
	
	public function getTotals($filter){
	
		$select = $this->getSelect($filter);
		$select->reset(Select::COLUMNS)
			->columns(['count' => new Expression('count(r.id)')]);
		return $select->fetchRow();
	
	}
	
	public function getItems($filter, $p = 1, $ipp = 100){
		$select = $this->getSelect($filter);
		$select->limit($ipp)->offset(($p-1)*$ipp);
	
		$select
			->order('r.date desc')
			->order(new Expression('id desc'));
	
		$items = $this->fetchAll($select);
		return $items;
	}
	
	
		
	public function buildItem(&$item){
		parent::buildItem($item);
		/*@var $reviewRefDb ReviewRefsDb */
		$reviewRefDb = $this->serv(ReviewRefsDb::class);
		$item['body'] = trim($item['body']);
				
		if(strpos($item['social'], 'vk.com') !== false){
			$item['social_icon'] = 'vkontakte';
		} else if (strpos($item['social'], 'facebook.com') !== false){
			$item['social_icon'] = 'facebook-rect';
		}
		
		$item['refs'] = $reviewRefDb->getRefs($item['id']);
	}
    
	
	
	
}
