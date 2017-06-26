<?php 

namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Update;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Join;
use Common\Traits\ServiceManagerTrait;
use Common\Traits\ServiceManagerAware;
use Application\Model\Courses\CourseDb;

class ContentDb extends Table implements Multilingual, ServiceManagerAware {

	use MultilingualTrait, ServiceManagerTrait;
	
	protected $table = 'content';		

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
        $this->langFields(['title', 'intro', 'body', 'seo_title', 'seo_description', 'seo_keywords']);
    }
    
	public function getArticleByAlias($alias){
		$select = new Select(['c' => $this->table]);
		$select->where->expression('c.alias = ?', $alias);
		$select->where->expression('c.status = ?', 1);
		
		$select->join(['u' => 'users_masters'], 		'u.id = c.author', 		['author_name' => 'name_'.$this->lang(), 'author_alias' => 'alias'], Join::JOIN_LEFT);
		
		return $this->fetchRow($select);		
	}

	
	public function incViews($id){
		$id = $this->id($id);
		$this->getAdapter()->sql('UPDATE content set views = views + 1 WHERE id = :id', ['id' => $id]);
	}

	
	public function getArticles($divisionId = null, $count = null){
		$select = new Select($this->table);
		$select->where->expression('status = ?', 1)->and->equalTo('type', 'article');
		if($divisionId != null){
			$select->where->expression('division_id = ?', $divisionId);
			$select->order('priority DESC')->order('created DESC');
		} else {
			$select->order('created DESC');
		}
	
		if($count !== null){
			$select->limit($count);
		}
	
		return $this->fetchAll($select);
	
	}
	
	
	public function getCourses($id){
		/* @var $courseDb CourseDb */
		$courseDb = $this->serv(CourseDb::class);
			
		$select = new Select(['c' => 'courses']);
		$select->join(['c2c' => 'content_content2course'], 'c2c.course_id = c.id', [], Join::JOIN_INNER);
		$select->where
			->equalTo('c2c.content_id', $id)
			->and->equalTo('c.status', 1);
		
		$select
			->order('c.status desc')
			->order('c.priority desc')
			->order('c.id asc');
		
		return $courseDb->fetchAll($select);		
	}
	
}
