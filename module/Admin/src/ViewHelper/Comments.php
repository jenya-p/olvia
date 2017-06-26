<?php
namespace Admin\ViewHelper;

use ZfAnnotation\Annotation\ViewHelper;

/**
 * @ViewHelper(name="adminComments")
 * */
class Comments extends \Zend\View\Helper\AbstractHelper{
	
	public function __invoke($entity, $id = null, $comments = null){
		
		if($id === null){
			$id = $this->view->get('item')['id'];			
		}
		
		if($comments === null){
			$comments = $this->view->get('comments');
		}
		
		
		return $this->view->render('admin/parts/comments', ['entity' => $entity, 'id' => $id, 'comments' => $comments]);
		
	}	
	
	
}