<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\PhotoForm;
use Admin\Model\Content\PhotoDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Admin\Model\Content\PhotoalbumDb;
use Common\ImageService;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property PhotoDb $db 
 */
class PhotoController extends CRUDController implements CRUDEditModel{
	
	/** @var PhotoDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(PhotoDb::class);		 
		$this->crudInit();
	}
	
	/**
	 * @Route(name="photo-index",route="/photo-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function photoIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
		/* @var $photoalbumDb PhotoalbumDb */
		$photoalbumDb = $this->serv(PhotoalbumDb::class);
		return [
				'albumOptions' => $photoalbumDb->options()
		];
	}
	
	/**
	 * @Route(name="photo-edit", route="/photo-edit/:id",extends="private",type="segment")
	 */
	public function photoEditAction(){
		return parent::processEditForm(PhotoForm::class, $this);
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
				
		if(empty($data['photoalbum_id'])){
			$data['photoalbum_id'] = null;
		}
		
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
			$this->sendFlashMessage("Фото сохранено", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Фото сохранено", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		$ret = [];
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('photo', ['id' => $this->id]);
			$albumDb =  $this->serv(PhotoalbumDb::class);			
			$ret['photoalbum'] = $albumDb->get($this->item['photoalbum_id']);			
		}
		$ret['stat'] = $this->db->getStat($this->item['id']);
		
		$uploadUrl = $this->url()->fromRoute('private/photo-image-upload', ['id' => $this->id]);
		 
		$this->form->field('image')
			->url($uploadUrl);
		
		$this->form->field('image')
			->preview($imageService->resize($this->item['image'], 400, 100))
			->full($imageService->resize($this->item['image']));
		
		return $ret;
	}
	
	
	/**
     * @Route(name="photo-delete", route="/photo-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	
    	return new JsonModel(['result' => 'ok']);
    }
    

    /**
     * @Route(name="photo-status", route="/photo-status/:id",extends="private",type="segment")
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
     * @Route(name="photo-image-upload", route="/photo-upload/[:id]",extends="private",type="segment")
     */
    public function uploadAction(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    
    	/* @var $photoalbumDb PhotoalbumDb */
    	$photoalbumDb = $this->serv(PhotoalbumDb::class);
    	 
    	$id = $this->params('id', 'new');
    
    	if($id == 'new'){
    		$id = $this->db->getNextId();
    	} else {
    		$item = $this->db->get($id);
    		if(empty($item)){
    			$albumId = $photoalbumDb->getNextId();
    		} else {
    			$albumId = $item['photoalbum_id'];
    			if(empty($albumId)){
    				$albumId = $photoalbumDb->getNextId();
    			}
    		}
    	}
    
    	try{
    		$image = $imageService->import($this->params()->fromFiles('image'), 'photo/'.$albumId.'/'.$id);
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
    
    const SESSION__MASS_ADD_PHOTOS_TO_ALBUM = 'mass_add_photos_to_album';
    
    /**
     * @Route(name="photo-mass-upload", route="/photo-mass-upload/[:album_id]",extends="private",type="segment")
     */
    public function massUploadAction(){
    	/* @var $imageService ImageService */
    	$imageService = $this->serv(ImageService::class);
    	/* @var $photoalbumDb PhotoalbumDb */
    	$photoalbumDb = $this->serv(PhotoalbumDb::class);
    	 
    	$albumId = $this->params('album_id', null);
    
    	if(empty($albumId)){
    		$albumFromSession = $_SESSION['SESSION__MASS_ADD_PHOTOS_TO_ALBUM'];
    		if(!empty($albumFromSession) && $albumFromSession[0] > time() - 60*30){    			
    			$albumId = $albumFromSession[1];
    		} else {
    			$albumId = $this->createEmptyAlbum();    			
    		}   
    		$_SESSION['SESSION__MASS_ADD_PHOTOS_TO_ALBUM'] = [time(), $albumId];
    	}
    	
    	$album = $photoalbumDb->get($albumId);
    	if(empty($album)){
    		return new JsonModel([
    				'result' => 'error',
    				'message' => 'Не найден альбом (id='.$albumId.')'
    		]);
    	}
    	    	 
    	$nextPhotoId = $this->db->getNextId();
    
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
    	 
    	$photo['photoalbum_id'] = $albumId;
    	 
    	$id = $this->db->insert($photo);
    	    	    	 
    	$photo = $this->db->get($id);
    	    	
    	$photo['photoalbum_name'] = $album['title'];
    	
    	/* @var RendererInterface $renderer */
    	$renderer = $this->serv('ViewRenderer');
    	 
    	return new JsonModel([
    			'result' => 'ok',
    			'original' => $image,    			 
    			'preview' => $imageService->resize($image, null, 100),
    			'html' => $renderer->render('admin/content/photo/photo-index.item.phtml',['item' => $photo])
    	]);
    
    }
    	
    
    private function createEmptyAlbum(){
    	/* @var $photoalbumDb PhotoalbumDb */
    	$photoalbumDb = $this->serv(PhotoalbumDb::class);
    	
    	$alias = $photoalbumDb->uniqueAlias("album-".date('d-m-Y'));
    	$albumId = $photoalbumDb->insert([
    			'title' => "Новый альбом",
    			'seo_title' => "Новый альбом",
    			'alias' => $alias,
    			'created' => time()
    	]);
    	return $albumId;
    	
    }
    
}

