<?php

namespace Admin\Controller\Content;

use Admin\Forms\Content\DivisionForm;
use Admin\Model\Content\DivisionDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Common\ImageService;
use Zend\View\Model\JsonModel;
use Admin\Model\Content\ContentDb;
use Common\Db\Select;
use Common\Utils;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property DivisionDb $db 
 */
class DivisionController extends CRUDController implements CRUDEditModel{
	
	public function init(){		
		$this->db = $this->serv("Admin\Model\Content\DivisionDb");
		$this->crudInit();
	}
	
	/**
	 * @Route(name="division-index",route="/division-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function divisionIndexAction(){
		return $this->crudList($this->db);
	}
	
	/**
	 * @Route(name="division-edit", route="/division-edit/:id",extends="private",type="segment")
	 */
	public function divisionEditAction(){
		return parent::processEditForm(DivisionForm::class, $this);
	}
	
	
	/**
	 * @Route(name="division-image-upload", route="/division-upload/[:id]",extends="private",type="segment")
	 */
	public function uploadAction(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		$id = $this->params('id', 'new');
		
		if($id == 'new'){
			$id = $this->db->getNextId();
		} else {
			$item = $this->db->get($id);
		}

		try{
			$image = $imageService->import($this->params()->fromFiles('image'), 'divisions/'.$id);
		} catch (\Exception $e){
			return new JsonModel([
					'result' => 'error',
					'message' => $e->getMessage()
			]);
		}
		
		if(!empty($item)){
			$this->db->updateOne(['image' => $image], $item['id']);
		}

		return new JsonModel([
			'result' => 'ok',
			'original' => $image,
			'preview' => $imageService->resize($image, 400, 100) 
		]);
		
	}
	
		
	// CRUD Model 
	public function load($id) {
		$item = $this->db->get($id);
		$this->db->concretLanguage($item);
		return $item;
	}

	public function create() {

	}

	public function validate(array $data){
		
    }
    
    public function save(array $data){
    	
    	if(empty($data['alias'])){
    		$data['alias'] = $data['title'];
    	}
    	$data['alias'] = Utils::urlify($data['alias']);
    	
    	if($this->isNew){
    	
    		$this->id = $this->db->insert($data);
    	
    		if($this->id == null) throw new \Exception("Не удалось создать аккаунт");
    		 
    	} else {
    	
    		$this->db->updateOne($data, $this->id);
    	
    	}
    	
    	$this->db->saveHistory($data, $this->item,$this->id);
    	
    	return $this->id;
    }
    
    public function afterSave(){
    	if($this->isNew){
    		$this->sendFlashMessage("Раздел создан", Flash::SUCCESS);
    	} else {
    		$this->sendFlashMessage("Раздел сохранен", Flash::SUCCESS);
    	}
    	
    	return $this->afterSaveRedirect();
    }
    
    public function edit(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    	
    	$uploadUrl = $this->url()->fromRoute('private/division-image-upload', ['id' => $this->id]);
    	
		$this->form->field('image')
			->url($uploadUrl);
		
		$this->form->field('image')
			->preview($imageService->resize($this->item['image'], 400, 100))
			->full($imageService->resize($this->item['image']));
			
		return [
			'articles' => $this->db->getLastArticles($this->item['id'], 10),
			'articles_count' =>$this->db->getArticlesCount($this->item['id']),
			'stat' => $this->db->getStat($this->item['id'])
		];
			
    }

    /**
     * @Route(name="division-delete", route="/division-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');
    	$articleCount = $this->db->getArticlesCount($id);
    	if($articleCount == 0){
    		$this->db->deleteOne($id);
    		return new JsonModel(['result' => 'ok']);
    	} else {
    		return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно. В разделе есть статьи']);
    	}    	    			
    }
    
    /**
     * @Route(name="division-status", route="/division-status/:id",extends="private",type="segment")
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

