<?
namespace Application\Model\Content;

use Common\Db\Multilingual;
use Common\Db\MultilingualTrait;
use Common\Db\Select;
use Common\Db\Table;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Sql\Join;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Application\Model\Courses\CourseDb;
use Application\Controller\CatalogController;

class TagDb extends Table implements Multilingual, ServiceManagerAware {
	
	use MultilingualTrait, ServiceManagerTrait;
	
	protected $table = 'content_tags';

	public function __construct(Adapter $adapter) {
		$this->adapter = $adapter;
		$this->langFields(['name', 'seo_title', 'seo_description', 'seo_keywords', 'group_name']);	
	}

	public function getByAlias($alias){
		$select = new Select(['c' => $this->table]);
		$select->where->expression('c.alias = ?', $alias);
		$select->where->expression('c.status = ?', 1);	
		return $this->fetchRow($select);
	}
	
	
	public function getCouseFilterTags($type){
		/* @var $courseDb CourseDb */ 
// 		$courseDb = $this->serv(CourseDb::class);
		
// 		$ids = $courseDb->getCourseIdsForEventType($type);
// 		if(empty($ids)){
// 			return [];
// 		}
		
		return $this->getAdapter()->fetchGroups('select
					tg.name_'.$this->lang.' as group_name,
					tg.id as group_id,
					t.id as tag_id,
					t.name_'.$this->lang.' as tag_name,
					t.alias as tag_alias,
					count(c.id) as course_count
				from content_tags t
				left join content_tag_groups tg on tg.id = t.group_id
				left join content_tag_refs tr on tr.tag_id = t.id and tr.entity = "course"
				left join courses c on c.id = tr.item_id				
				group by t.id
				having count(c.id) > 0
				ORDER by tg.priority desc, tg.id asc, t.priority desc, t.id asc', ['courseIds' => $ids]);
			
	}
	
	
	public function getCourseTags($courseId){
		$select = new Select(['t' => $this->table]);
		$select->join(['tr' => 'content_tag_refs'], 'tr.tag_id = t.id', [], Join::JOIN_INNER);
		$select->where->equalTo('tr.entity', "course")
			->and->equalTo('tr.item_id', $courseId);
		$select
			->order('tr.priority desc')
			->order('t.priority desc')
			->order('t.id asc');
		return $this->fetchAll($select);
	}
	
	public function getContentTags($articleId){
		$select = new Select(['t' => $this->table]);
		$select->join(['tr' => 'content_tag_refs'], 'tr.tag_id = t.id', [], Join::JOIN_INNER);
		$select->where->equalTo('tr.entity', "content")
			->and->equalTo('tr.item_id', $courseId);
		$select
			->order('tr.priority desc')
			->order('t.priority desc')
			->order('t.id asc');
		return $this->fetchAll($select);
	}
}
