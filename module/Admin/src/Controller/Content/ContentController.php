<?php

namespace Admin\Controller\Content;

use Admin\Forms\Content\ContentForm;
use Admin\Model\Content\ContentDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\Utils;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Admin\Model\Content\DivisionDb;
use Zend\View\Model\JsonModel;
use Admin\Model\Content\TagDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property ContentDb $db 
 */
class ContentController extends CRUDController implements CRUDEditModel{
	
	/** @var ContentDb */
	var $db;
	/** @var TagDb */
	var $tagDb;
		
	public function init(){
		$this->db = $this->serv(ContentDb::class);
		$this->tagDb = $this->serv(TagDb::class);
		$this->crudInit();
	}
	
	/**
	 * @Route(name="content-index",route="/content-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function contentIndexAction(){
		
		return $this->crudList($this->db);
		
	}
	
	protected function index(){
		
		/* @var $divisionDb DivisionDb */
		$divisionDb = $this->serv(DivisionDb::class);
		return [
			'divisionOptions' => $divisionDb->getOptions() 
		];		
	}
	
	/**
	 * @Route(name="content-edit", route="/content-edit/:id",extends="private",type="segment")
	 */
	public function contentEditAction(){
		return parent::processEditForm(ContentForm::class, $this);
	}
		
	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		$item['tags'] = $this->tagDb->getItemTags('content', $id);
		$item['courses'] = $this->db->getArticleCourseIds($id);
		return $item;
	}

	public function create() {
		return [
			'created' => time(),
			'author' => $this->identity()->id	
		];
	}

	public function validate(array $data){		
    }
    
    public function save(array $data){
    	if(empty($data['alias'])){
    		$data['alias'] = $data['title'];
    	}
    	$data['alias'] = Utils::urlify($data['alias']);
    	    	    	
    	if(empty($data['division_id'])){
    		$data['division_id'] = null;
    	}

    	$tags = $data['tags'];
    	unset($data['tags']);

    	$courses = $data['courses'];
    	unset($data['courses']);
    	
    	
    	if($this->isNew){    		
    		$this->id = $this->db->insert($data);    		
    		if($this->id == null) throw new \Exception("Не удалось создать аккаунт");
    	} else {
    		$this->db->updateOne($data, $this->id);    		
    	}    
    	
    	$this->db->saveTagHistory($tags, $this->item['tags'], $this->id);
    	$this->tagDb->saveItemTags('content', $this->id, $tags);
    	
    	$this->db->saveArticleCourseIds($this->id, $courses);
    	
    	
    	return $this->id;
    }
    
    public function afterSave(){
    	if($this->isNew){
    		$this->sendFlashMessage("Статья создана", Flash::SUCCESS);
    	} else {
    		$this->sendFlashMessage("Статья сохранена", Flash::SUCCESS);
    	}
    	
    	return $this->afterSaveRedirect();
    	
    }
    
    public function edit(){
    	
    	if(!$this->isNew){
    		$this->layout()->site_url = $this->url()->fromRoute('content-article', ['alias' => $this->item['alias']]);
    	}
    	$ret = [];
    	$ret['stat'] = $this->db->getStat($this->item['id']);    	
    	return $ret;
    }    
    
    
    /**
     * @Route(name="content-delete", route="/content-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');
    	
    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);

    }
    
    /**
     * @Route(name="content-status", route="/content-status/:id",extends="private",type="segment")
     */
    public function statusAction(){
    	$id = $this->params('id', 'new');
    	$item = $this->db->get($id);
    	if(empty($item)){
    		return new JsonModel(['result' => 'error', 'message' => 'Объект не найден']);
    	}
    	$update = ['status' => 0];
    	if($item['status'] == 0){
    		$update['status'] = 1;
    	}
    	$this->db->updateOne($update, $id);
    	return new JsonModel(['result' => 'ok', 'status' => $update['status']]);
    	 
    }
    
    
}

