<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\TagForm;
use Admin\Model\Content\TagDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Common\Utils;
use Admin\Model\Content\TagGroupDb;
use Zend\View\Model\JsonModel;
use Common\Db\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Join;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property TagDb $db 
 */
class TagController extends CRUDController implements CRUDEditModel{
	
	/** @var TagDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(TagDb::class);		 
		$this->crudInit();
	}
	
	/**
	 * @Route(name="tag-index",route="/tag-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function tagIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="tag-edit", route="/tag-edit/:id",extends="private",type="segment")
	 */
	public function tagEditAction(){
		return parent::processEditForm(TagForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		// return [];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){
		
		if(empty($data['group_id'])){
			$data['group_id'] = null;
		}
		
		if(empty($data['alias'])){
			$data['alias'] = $data['name'];
		}
		$data['alias'] = Utils::urlify($data['alias']);
		
		if($this->isNew){						
			$this->id = $this->db->insert($data);			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		
		return $this->id;
	}
	
	public function afterSave(){
		if($this->isNew){
			$this->sendFlashMessage("Тэг создан", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Тэг сохранен", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		$tagGroupDb = $this->serv(TagGroupDb::class);
		$group = $tagGroupDb->get($this->item['group_id']);
		
		return [
			'stat' => $this->db->getStat($this->id),	
			'group' => $group
		];
	}
	
	/**
	 * @Route(name="tag-delete", route="/tag-delete/:id",extends="private",type="segment")
	 */
	public function deleteAction(){
		$id = $this->params('id', 'new');
		
		$this->db->deleteOne($id);
		
		return new JsonModel(['result' => 'ok']);
		
	}
	
	/**
	 * @Route(name="tag-status", route="/tag-status/:id[/:field]",extends="private",type="segment")
	 */
	public function statusAction(){
		$id = $this->params('id', 'new');
		$field = $this->params('field', 'status');
		$item = $this->db->get($id);
		if(empty($item)){
			return new JsonModel(['result' => 'error', 'message' => 'Объект не найден']);
		}
		$update = [$field => 0];
		if($item[$field] == 0){
			$update[$field] = 1;
		}
		$this->db->updateOne($update, $id);
		return new JsonModel(['result' => 'ok', 'status' => $update[$field]]);	
	}
		
	
	/**
	 * @Route(name="tag-ajax-select",route="/tag-ajax-select",extends="private",type="segment")
	 */
	public function ajaxSelectAction(){
		$query = $this->params()->fromQuery('q');
		if(empty($query)){
			return new JsonModel([]);
		}
		 
		$select = new Select(['t' => 'content_tags']);
		$select->columns(['id', 'name' => 'name_ru' , 'alias' => 'alias','status']);
		$select->join(['tg' => 'content_tag_groups'], 'tg.id = t.group_id', ['group_name' => new Expression('tg.name_ru')], Join::JOIN_LEFT);
		
		$select->where->nest
			->expression('t.name_ru like ?', mb_strtolower($query).'%')->or
			->expression('t.name_ru like ?', '% '.mb_strtolower($query).'%');
		 
		$select
			->order('t.name_ru ASC')
			->limit(20);
			
		$suggestions = $select->fetchAll();
		foreach ($suggestions as &$item) {
			$item['value'] = $item['name'];
			if(empty($item['group_name'])){
				$item['group_name'] = '';
			}
		} 
		
		return new JsonModel([
				"query" => $query,
				"suggestions" => $suggestions]);
		 
	
	}
	
	/**
	 * @Route(name="tag-ajax-create",route="/tag-ajax-create",extends="private",type="segment")
	 */
	public function ajaxCreateAction(){
		$name = $this->params()->fromQuery('name');
		if(empty($name)){
			return new JsonModel(['']);
		}
			
		$insert = [
			'name' => $name,
			'alias' => Utils::urlify($name),
			'group_id' => null,
			'status' => 0,
			'seo_title' => $name
		];
		
		$id = $this->db->insert($insert);
		
		$select = new Select(['t' => 'content_tags']);
		$select->columns(['id', 'name' => 'name_ru' , 'alias' => 'alias','status']);
		$select->join(['tg' => 'content_tag_groups'], 'tg.id = t.group_id', ['group_name' => new Expression('tg.name_ru')], Join::JOIN_LEFT);
	
		$select->where->expression('t.id = ?', $id);
			
		$item = $select->fetchRow();
		$item['value'] = $item['name'];
		if(empty($item['group_name'])){
			$item['group_name'] = '';
		}
		
		return new JsonModel(['result' => 'ok', 'item' => $item]);			
	
	}
	
	
}

