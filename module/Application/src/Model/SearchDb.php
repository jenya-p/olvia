<?php 

namespace Application\Model;

use Common\Db\Table;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Zend\Db\Adapter\Adapter;
use Application\Model\Courses\CourseDb;
use Application\Model\Content\ContentDb;
use Application\Model\Content\TagDb;


class SearchDb extends Table implements ServiceManagerAware {

	use ServiceManagerTrait;
	
    protected $table = 'users_accounts';

    public function __construct(Adapter $adapter) {
    	return parent::__construct($adapter);
    }
    
    
	public function search($query, $offset = 0){
		
		$sql = 'select entity, item_id
			from search_index
			where MATCH (ru_1) AGAINST (:query) * 5 + MATCH (ru_2) AGAINST (:query) 
			order by
			MATCH (ru_1) AGAINST (:query) * 5 + MATCH (ru_2) AGAINST (:query) DESC
			limit 10';
		
		if($offset !== 0){
			$sql .= ' osffset'.intval($offset);
		}
		
		$searchItems = $this->getAdapter()->fetchAll($sql, ['query' => $query]);
		
		return $searchItems;
		
	}
    
    
}
