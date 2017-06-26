<?
namespace Admin\Controller\Content;

use Admin\Forms\Content\VideoalbumForm;
use Admin\Model\Content\VideoalbumDb;
use Common\Annotations\Layout;
use Common\Annotations\Roles;
use Common\CRUDController;
use Common\CRUDEditModel;
use Common\ViewHelper\Flash;
use ZfAnnotation\Annotation\Controller;
use ZfAnnotation\Annotation\Route;
use Zend\View\Model\JsonModel;
use Admin\Model\Content\VideoDb;
use Common\Utils;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 * @property VideoalbumDb $db 
 */
class VideoalbumController extends CRUDController implements CRUDEditModel{
	
	/** @var VideoalbumDb */
	var $db;
	
	public function init(){
		$this->db = $this->serv(VideoalbumDb::class);		 
		$this->crudInit();
	}
	
	/**
	 * @Route(name="videoalbum-index",route="/videoalbum-index[/f-:f][/p-:p]",extends="private",type="segment")
	 */
	public function videoalbumIndexAction(){		
		return $this->crudList($this->db);		
	}
	
	protected function index(){
				
	}
	
	/**
	 * @Route(name="videoalbum-edit", route="/videoalbum-edit/:id",extends="private",type="segment")
	 */
	public function videoalbumEditAction(){
		return parent::processEditForm(VideoalbumForm::class, $this);
	}
		

	/* CRUD Model *************************** */
	
	public function load($id) {
		$item = $this->db->get($id);
		return $item;
	}

	public function create() {
		return [
			'author' => $this->identity()->id,
			'created' => time()	
				
		];
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
			if($this->id == null) throw new \Exception("Ошибка сохранения");
		} else {
			$this->db->updateOne($data, $this->id);			
		}		
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
		
		if(!$this->isNew){
			// $this->layout()->site_url = $this->url()->fromRoute('photoalbum', ['id' => $this->id]);
			/* @var $videoDb VideoDb */
			$videoDb = $this->serv(VideoDb::class);
			$videos = $videoDb->getAlbumVideos($this->id);
		}
		return [
			'videos' =>	$videos,
			'stat' => $this->db->getStat($this->item['id'])
		];
	}
	
	  /**
     * @Route(name="videoalbum-status", route="/videoalbum-status/:id",extends="private",type="segment")
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
     * @Route(name="videoalbum-delete", route="/videoalbum-delete/:id",extends="private",type="segment")
     */
    public function deleteAction(){
    	$id = $this->params('id', 'new');

    	$childCount = $this->db->getChildCount($id);
    	if($childCount == 0){
    		$this->db->deleteOne($id);
    		return new JsonModel(['result' => 'ok']);
    	} else {
    		return new JsonModel(['result' => 'error', 'message' => 'Удаление невозможно. В альбоме есть видео']);
    	}
    }
    
    
    	
}

