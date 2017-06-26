<?php 

namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Select;
use Zend\Db\Sql\Expression;


class BannerDb extends Table implements Multilingual {

	use MultilingualTrait;
	
    protected $table = 'content_banners';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['body']);
    }
    
    public function getBanners(){
    	$select = new Select($this->table);
    	$time = time();
    	$select->where
    		->expression('status = ?', 1);
    	$n = $select->where->and->nest();
    	$n->isNull('date_from')->or->lessThan('date_from', $time);
    	$n = $select->where->and->nest();
    	$n->isNull('date_to')->or->greaterThan('date_to', $time);
    	
    	$select
    		->order('priority desc')
    		->order(new Expression('rand() asc'));
    		
    	// echo $select->toString(); die;	
    		
    	return $this->fetchAll($select);
    	 
    }
    
  
    
}
