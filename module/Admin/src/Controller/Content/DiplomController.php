<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\DiplomForm;
use Admin\Model\Content\DiplomDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Common\ImageService;
use Admin\Model\Users\MasterDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property DiplomDb $db 
 */
class DiplomController extends CRUDController implements CRUDEditModel{
	
	/** @var DiplomDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(DiplomDb::class);		 
		$this->crudInit('diplomas');
	}
	
	/**
	 * @Route(name="diplomas-index",route="/diplomas-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function diplomIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index($return){
		if(!empty($return['filter']['master'])){
			/* @var $masterDb masterDb */
			$masterDb = $this->serv(MasterDb::class);
			$master = $masterDb->get($return['filter']['master']);
			return ['masterName' => $master['displayname']];
		}
	}
	
	/**
	 * @Route(name="diplomas-edit", route="/diplomas-edit/:id",extends="private",type="segment")
	 */
	public function diplomEditAction(){
		return parent::processEditForm(DiplomForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		return ['master_id' => $this->params()->fromQuery('master_id', null), 'status' => 1, 'priority' => $this->db->getNextId()];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){
		
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
			$this->sendFlashMessage("Диплом сохранен", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Диплом сохранен", Flash::SUCCESS);
		}
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		$uploadUrl = $this->url()->fromRoute('private/diplomas-image-upload', ['id' => $this->id]);
		 
		
		$this->form->field('image')
			->url($uploadUrl);
		
		$this->form->field('image')
			->preview($imageService->resize($this->item['image'], 200, 280))
			->full($this->item['image']);
		
			
	}
	
	
	/**
     * @Route(name="diplomas-delete", route="/diplomas-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
        	
    /**
     * @Route(name="diplomas-status", route="/diplomas-status/:id[/:field]",extends="private",type="segment")
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
    	return new JsonModel(['result' => 'ok', $field => $update[$field]]);    	 
    }
    
    
    /**
     * @Route(name="diplomas-image-upload", route="/diplomas-upload/[:id]",extends="private",type="segment")
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
    		$image = $imageService->import($this->params()->fromFiles('image'), 'diplomas/'.$id);
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
    			'preview' => $imageService->resize($image, 200, 280)
    	]);
    
    }
    	
}

