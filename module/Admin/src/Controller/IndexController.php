<?php

namespace Admin\Controller;

use Zend\View\Model\ViewModel;
use ZfAnnotation\Annotation\Route;
use ZfAnnotation\Annotation\Controller;
use Common\SiteController;
use Common\Annotations\Roles;
use Common\Annotations\Layout;
use Zend\View\Model\JsonModel;
use Common\Db\Table;
use Admin\Model\Content\ContentDb;
use Admin\Model\Content\PhotoalbumDb;
use Admin\Model\Content\VideoDb;
use Common\Utils;
use Admin\Model\Users\MasterDb;
use Admin\Model\Content\ReviewDb;
use Admin\Model\CommentsDb;
use Admin\Model\Courses\CourseDb;

/**
 * @Controller
 * @Roles(value="admin")
 * @Layout(value="private")
 */
class IndexController extends SiteController {
	
	/**
	 * @Route(name="private",route="/private")
	 */
	public function indexAction() {
		return new ViewModel();
	}
	
	
	/**
	 * @Route(name="inline-save",route="/inline-save/:token", extends="private", type="segment")
	 */	
	public function inlineSaveAction(){
		$token = $this->params('token');
		list($table, $id, $field) = explode('-', $token);
		/* @var $service Table */
		
		if($table == 'content'){
			$service = $this->serv(ContentDb::class);
		} else if($table == 'photoalbum'){
			$service = $this->serv(PhotoalbumDb::class);
		} else if($table == 'video'){
			$service = $this->serv(VideoDb::class);
		} else if($table == 'master'){
			$service = $this->serv(MasterDb::class);
		} else if($table == 'review'){
			$service = $this->serv(ReviewDb::class);
		} else if($table == 'course'){
			$service = $this->serv(CourseDb::class);
		} else {
			return new JsonModel(['result' =>  'error', 'message' => 'table '.$table.' not found']);
		}
		
		if(!is_numeric($id)){
			return new JsonModel(['result' =>  'error', 'message' => 'id should be numeric']);
		}
		
		if(empty($field)){
			$field = 'body';
		}
		
		$data = $this->params()->fromPost('editabledata',null);
		$data = trim($data);
		if($data === null){
			return new JsonModel(['result' =>  'error', 'message' => 'data not set']);
		}
		$update = [
				$field => $data
		];
		
		try{
			$service->updateOne($update, $id);
		} catch (\Throwable $e){
			return new JsonModel(['result' =>  'error', 'message' => 'Exception while saving. '.$e->getMessage()]);
		}
		
		return new JsonModel(['result' =>  'ok']);		
	}
	
	/**
	 * @Route(name="file-upload",route="/file-upload", extends="private", type="segment")
	 */
	public function fileUploadAction(){
		// $this->layout()->setTerminal(true);
		$CKEditorFuncNum = $this->params()->fromQuery('CKEditorFuncNum');
		
		$config = $this->serv('Config');
		
		$baseDir = $config['path']['public'].'images'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.date('ym').DIRECTORY_SEPARATOR;
		$baseUrl = '/images/uploads/'.date('ym').'/';
		if(!is_dir($baseDir)){
			mkdir($baseDir, 0777, true);
		}
		$file = $this->params()->fromFiles('upload');
		
		if($file['size'] > 15*1024*1024) {
			$error = "Нельзя загружать файлы больше 15Mb";
			return $this->getResponse()->setContent('<html><body><script type="text/javascript">window.parent.CKEDITOR.tools.callFunction('.$CKEditorFuncNum.', null, "'.$error.'");</script></body></html>');
		}
		
		$nameBase = Utils::urlify(pathinfo($file['name'], PATHINFO_FILENAME));
		$nameExt = pathinfo($file['name'], PATHINFO_EXTENSION);
		$fileName = null;
		$randPart = '';
		for ($i = 0; $i < 3; $i++){
			if(!file_exists($baseDir.$nameBase.$randPart.'.'.$nameExt)){
				$fileName = $baseDir.$nameBase.$randPart.'.'.$nameExt;
				$fileUrl = $baseUrl.$nameBase.$randPart.'.'.$nameExt;
				break;
			}
			$randPart = '-'.rand(100, 999);
		}
		if($fileName === null){
			$error = "Не удалось подобрать имя для нового файла (.$nameBase.'.'.$nameExt.)";
			return $this->getResponse()->setContent('<html><body><script type="text/javascript">window.parent.CKEDITOR.tools.callFunction('.$CKEditorFuncNum.', null, "'.$error.'");</script></body></html>');
		}
		
		copy($file['tmp_name'], $fileName);
		
		return $this->getResponse()->setContent('<html><body><script type="text/javascript">window.parent.CKEDITOR.tools.callFunction('.$CKEditorFuncNum.', "'.$fileUrl.'");</script></body></html>');
		
	}
	
	/**
	 * @Route(name="post-comment-ajax",route="/post-comment/:entity/:id", extends="private", type="segment")
	 */
	function postCommentAction(){
		/* @var $commentsDb CommentsDb */
		$commentsDb = $this->serv(CommentsDb::class);
		
		$entity = $this->params('entity');
		$id 	= $this->params('id');
		$body 	= $this->params()->fromPost('body');
		
		if(empty($entity) || !is_numeric($id)){
			return new JsonModel(['result' =>  'error', 'message' => 'Что то пошло не так (1)']);
		}

		try{
			$id = $commentsDb->insert([
					'user_id' => $this->identity()->id,
					'entity' => $entity,
					'item_id' 	=> $id,
					'body' 	=> $body,
					'date' => time()
			]);
			
			$comment = $commentsDb->get($id);
				
		} catch (\Exception $e){
			return new JsonModel(['result' =>  'error', 'message' => 'Что то пошло не так (3) '.$e->getMessage()]);
		}
				
		if(empty($comment)){
			return new JsonModel(['result' =>  'error', 'message' => 'Что то пошло не так (2)']);
		}
		
		/* @var RendererInterface $renderer */
		$renderer = $this->serv('ViewRenderer');
		
		$html = $renderer->render('admin/parts/comments.item.phtml',['comment' => $comment]);
		
		return new JsonModel(['result' =>  'ok', 'html' => $html]);
		
	}
	
	
	/**
	 * @Route(name="delete-comment-ajax",route="/delete-comment/:id", extends="private", type="segment")
	 */
	function deleteCommentAction(){
		/* @var $commentsDb CommentsDb */
		$commentsDb = $this->serv(CommentsDb::class);
	
		$id 	= $this->params('id');
		
		if(!is_numeric($id)){
			return new JsonModel(['result' =>  'error', 'message' => 'Что то пошло не так (1)']);
		}
	
		try{
			$commentsDb->deleteOne($id);
		} catch (\Exception $e){
			return new JsonModel(['result' =>  'error', 'message' => 'Что то пошло не так (3) '.$e->getMessage()]);
		}
	
		return new JsonModel(['result' =>  'ok']);
	
	}
	
	
}

