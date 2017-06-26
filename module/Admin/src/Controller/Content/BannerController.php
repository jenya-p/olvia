<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\BannerForm;
use Admin\Model\Content\BannerDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Common\ImageService;
use Common\Utils;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property BannerDb $db 
 */
class BannerController extends CRUDController implements CRUDEditModel{
	
	/** @var BannerDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(BannerDb::class);		 
		$this->crudInit();
	}
	
	/**
	 * @Route(name="banner-index",route="/banner-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function bannerIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="banner-edit", route="/banner-edit/:id",extends="private",type="segment")
	 */
	public function bannerEditAction(){
		return parent::processEditForm(BannerForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		return ['date_from' => null, 'date_to' => null, 'alias' => 'banner-'.$this->db->getNextId(), 'status' => 1];
	}

	public function validate(array $data){
		
	}
	
	public function save(array $data){		
		
		$data['alias'] = Utils::urlify($data['alias']);
		$data['alias'] = $this->db->uniqueAlias($data['alias'], $this->item['id']);
		
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
			$this->sendFlashMessage("Баннер сохранен", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Баннер сохранен", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		$uploadUrl = $this->url()->fromRoute('private/banner-image-upload', ['id' => $this->id]);
		 
		$this->form->field('image')
			->url($uploadUrl)
			->preview($imageService->resize($this->item['image'], ImageService::SIZE_BANNER))
			->full($imageService->resize($this->item['image']));
				
		return [
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	
	/**
     * @Route(name="banner-delete", route="/banner-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
    
    
        	
    /**
     * @Route(name="banner-status", route="/banner-status/:id",extends="private",type="segment")
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
    	
    
    /**
     * @Route(name="banner-image-upload", route="/banner-upload/[:id]",extends="private",type="segment")
     */
    public function uploadAction(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    
    	$id = $this->params('id', 'new');
    
    	if($id == 'new'){
    		$id = $this->db->getNextId();
    	}
    
    	$image = $imageService->import($this->params()->fromFiles('image'), 'banners/'.$id);
        	
    	return new JsonModel([
    			'result' => 'ok',
    			'original' => $image,
    			'preview' => $imageService->resize($image,  ImageService::SIZE_BANNER)
    	]);
    
    }
    
}

