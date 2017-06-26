<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\PhotoalbumForm;
use Admin\Model\Content\PhotoalbumDb;
use Admin\Model\Content\PhotoDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ImageService;
use Common\Utils;
use Common\ViewHelper\Flash;
use Zend\View\Model\JsonModel;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property PhotoalbumDb $db 
 */
class PhotoalbumController extends CRUDController implements CRUDEditModel{
	
	/** @var PhotoalbumDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(PhotoalbumDb::class);		 
		$this->crudInit();
	}
	
	/**
	 * @Route(name="photoalbum-index",route="/photoalbum-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function photoalbumIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="photoalbum-edit", route="/photoalbum-edit/:id",extends="private",type="segment")
	 */
	public function photoalbumEditAction(){
		return parent::processEditForm(PhotoalbumForm::class, $this);
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
		
		if(empty($data['alias'])){
			$data['alias'] = $data['title'];
		}
		$data['alias'] = Utils::urlify($data['alias']);
		$data['alias'] = $this->db->uniqueAlias($data['alias'], $this->item['id']);
				
		if($this->isNew){						
			$this->id = $this->db->insert($data);
			
			$photoIdsToAdd = $_SESSION[self::SESSION__ADD_PHOTOS_AFTER_CREATE]; 
			if(!empty($photoIdsToAdd)){
				/* @var $photoDb PhotoDb */
				$photoDb = $this->serv(PhotoDb::class);
				$photoDb->addPhotosToAlbum($photoIdsToAdd, $this->id);
				unset($_SESSION[self::SESSION__ADD_PHOTOS_AFTER_CREATE]);				
			}
			
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		
		return $this->id;
	}
	
	public function afterSave(){
		if($this->isNew){
			$this->sendFlashMessage("Фотоальбом создан", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Фотоальбом сохранен", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		
		if(!$this->isNew){
			$this->layout()->site_url = $this->url()->fromRoute('photo-album', ['alias' => $this->item['alias']]);
			/* @var $photoDb PhotoDb */
			$photoDb = $this->serv(PhotoDb::class);
			$photos = $photoDb->getPhotoalbumPhotos($this->id);
		}
		
		return [
			'photos' =>	$photos,
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	
	/**
     * @Route(name="photoalbum-delete", route="/photoalbum-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', null);
		
    	$childCount = $this->db->getChildCount($id);
    	if($childCount == 0){
    		$this->db->deleteOne($id);
    		return new JsonModel(['result' => 'ok']);
    	} else {
    		return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно. В альбоме есть фотографии']);
    	}    	
    	
    }
    
    /**
     * @Route(name="photoalbum-status", route="/photoalbum-status/:id",extends="private",type="segment")
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
     * @Route(name="photoalbum-upload", route="/photoalbum-upload/[:id]",extends="private",type="segment")
     */
    public function uploadAction(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    	/* @var $photoDb PhotoDb */
    	$photoDb = $this->serv(PhotoDb::class);
    	
    	$albumId = $this->params('id', 'new');
    
    	$item = $this->db->get($albumId);
    	
    	if(empty($item)){
    		$albumId = $this->db->getNextId();
    	}
    	
    	$nextPhotoId = $photoDb->getNextId();

    	try{
    		$image = $imageService->import($this->params()->fromFiles('image'), 'photo/'.$albumId.'/'.$nextPhotoId);
    	} catch (\Exception $e){
    		return new JsonModel([
    			'result' => 'error',
    			'message' => $e->getMessage()
    		]);
    	}
    	
    	$photo = [			
    		'image' => $image    			
    	];
    	
    	if(!empty($item)){
    		$photo['photoalbum_id'] = $item['id'];
    	}
    	
    	$id = $photoDb->insert($photo);
    	
    	if(empty($item)){
    		$_SESSION[self::SESSION__ADD_PHOTOS_AFTER_CREATE][] = $id;
    	}
    	
    	$photo = $photoDb->get($id);
    	
    	/* @var RendererInterface $renderer */
    	$renderer = $this->serv('ViewRenderer');
    	
    	return new JsonModel([
    			'result' => 'ok',
    			'original' => $image,
    			'preview' => $imageService->resize($image, null, 100),
    			'html' => $renderer->render('admin/content/photoalbum/photoalbum-edit.photo.phtml',['photo' => $photo])
    	]);
    
    }
    
    const SESSION__ADD_PHOTOS_AFTER_CREATE = 'add_photos_after_create';
    	
}

