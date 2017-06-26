<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\VideoForm;
use Admin\Model\Content\VideoalbumDb;
use Admin\Model\Content\VideoDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\Utils;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Common\ImageService;
use Common\VideoService;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property VideoDb $db 
 */
class VideoController extends CRUDController implements CRUDEditModel{
	
	/** @var VideoDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(VideoDb::class);		 
		$this->crudInit();
	}
	
	/**
	 * @Route(name="video-index",route="/video-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function videoIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
		/* @var $vaDb VideoalbumDb */
		$vaDb = $this->serv(VideoalbumDb::class);
		return [
				'albumOptions' => $vaDb->options()
		];
	}
	
	/**
	 * @Route(name="video-edit", route="/video-edit/:id",extends="private",type="segment")
	 */
	public function videoEditAction(){
		return parent::processEditForm(VideoForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		
	}

	public function validate(array $data){
		$isLinkProvided = false;
		if(!empty($data['link'])){
			if(!filter_var($data['link'], FILTER_VALIDATE_URL)){
				$this->form->error('link', 'Недопустимое значение');
			} else {
				$urlParts = parse_url($data['link']);
				
				if(strpos($urlParts['host'], 'youtube') !== false || 
					 strpos($urlParts['host'], 'youtu.be') !== false){
					 	$isLinkProvided = true;
				} else if(strpos($urlParts['host'], 'vimeo')  === false){					
						$this->form->error('link', 'Недопустимое значение');
				}
			}			
			
		} 
		if($this->isNew && empty($data['link']) && empty($data['html'])){
			$this->form->error('link', 'Необходимо указать ссылку на ролик или заполнить поле "HTML"');
		}
		if(!$isLinkProvided){
			if(empty($data['title'])){
				$this->form->error('title', 'Заполните это поле');
			}
		}
	}
	
	public function save(array $data){
		
		if(!empty($data['link'])){
			$videoData = $this->db->import($data['link']);			
			$data = array_merge($data, $videoData);
			if(empty($data['title']) && $videoData['source'] == VideoDb::SOURCE_YOUTUBE){
				$this->db->importYoutubeRemote($data);
			}
			$this->db->importRemoteThumb($data);
		}
		unset($data['link']);		
		
		
		if(empty($data['alias'])){
			$data['alias'] = $data['title'];
		}
		$data['alias'] = Utils::urlify($data['alias']);
						
		if(empty($data['videoalbum_id'])){
			$data['videoalbum_id'] = null;
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
			$this->sendFlashMessage("Видео сохранено", Flash::SUCCESS);
		} else {
			$this->sendFlashMessage("Видео сохранено", Flash::SUCCESS);
		}
		
		return $this->afterSaveRedirect();
	}
	
	public function edit(){
		/* @var $imageService ImageService */
		$imageService = $this->serv(ImageService::class);
		
		$uploadUrl = $this->url()->fromRoute('private/video-image-upload', ['id' => $this->id]);
		 
		$this->form->field('thumb')
			->url($uploadUrl)
			->preview($imageService->resize($this->item['thumb'], ImageService::SIZE_VIDEO_THUMB))
			->full($imageService->resize($this->item['thumb']));
		
		
		if(!$this->isNew){
			$this->layout()->site_url = $this->url()->fromRoute('video-view', ['alias' => $this->item['alias']]);
		}
		return [
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	/**
	 * @Route(name="video-image-upload", route="/video-upload/[:id]",extends="private",type="segment")
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
			$image = $imageService->import($this->params()->fromFiles('thumb'), 'video/'.$id);
		} catch (\Exception $e){
			return new JsonModel([
					'result' => 'error',
					'message' => $e->getMessage()
			]);
		}
	
		if(!empty($item)){
			$this->db->updateOne(['thumb' => $image], $item['id']);
		}
	
		return new JsonModel([
				'result' => 'ok',
				'original' => $image,
				'preview' => $imageService->resize($image, ImageService::SIZE_VIDEO_THUMB)
		]);
	
	}
	
	
	/**
     * @Route(name="video-delete", route="/video-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$this->db->deleteOne($id);
    	return new JsonModel(['result' => 'ok']);
//     	return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно.']);
    }
    
    
    /**
     * @Route(name="video-status", route="/video-status/:id[/:field]",extends="private",type="segment")
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
    
    
    
    	
}

