<?php 

namespace Application\Model\Content;

use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;

class DivisionDb extends Table implements Multilingual{

	use MultilingualTrait;
	
	protected $table = 'content_division';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title', 'seo_title', 'seo_description', 'seo_keywords']);
    }
    
    
    public function getDivisions(){
    	$select = new Select(['d' => $this->table]);
    	$select->where->expression('d.status = ?', 1);
    	
    	$select->join(['c' => 'content'], 'c.division_id = d.id', ['article_count' => new Expression('count(c.id)')], Join::JOIN_INNER);
    	$select->group('d.id');
    	$select->order('d.priority DESC')->order('created DESC');
    	return $this->fetchAll($select);
    }
  
    public function getByAlias($alias){
    	$select = new Select($this->table);
    	$select->where->expression('alias = ?', $alias);
    	$select->where->expression('status = ?', 1);
    	return $this->fetchRow($select);
    }
    
  
}
