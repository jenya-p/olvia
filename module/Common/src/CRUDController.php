<?php 
namespace Common;

use Common\Form\Form;
use Zend\Http\PhpEnvironment\Request;
use Zend\Session\Container;
use Zend\Stdlib\ArrayUtils;
use Zend\Cache\Storage\StorageInterface;
use Common\Db\Discussion;

/**
 * @property \Zend\Session\Container $session;
 */
abstract class CRUDController extends SiteController{	
    
	var $db;
	/** @var Container */
	var $session;
	/** @var StorageInterface */
	var $cache;
	var $className;
	
	var $roteMeaningPart = null;
	
	protected function crudInit($roteMeaningPart = null){
		$this->session = new Container(self::class);
		
		$this->cache = $this->serv('Cache/Default');
		$this->className = get_class($this);
		$this->className = substr($this->className, strrpos($this->className, '\\')+1);
		
		if($roteMeaningPart === null){
			$this->roteMeaningPart = Utils::camel2snake(str_replace('Controller', '', $this->className));
		} else {
			$this->roteMeaningPart = $roteMeaningPart;
		}
		$this->saveHistory();		
	}	
		
	/** 
	 * Абстрактный контроллер для списков в админке.
	 * Поддерживает листание по страницам и фильтры  
	 * */
    protected function crudList(CRUDListModel $listModel){
    	$p = $this->params('p');
    	$sort = $this->getSortParam();
    	
    	$filter = $this->params()->fromQuery('f', null);
    	    	
    	if (is_array($filter)){
    		return $this->redirectToFilteredList(null, $filter, $sort);    		
    	} else {
    		$filterHash = $this->params('f', null);    		
    		if(is_string($filterHash) && !empty($filterHash)){
	    		$filter = $this->cache->getItem($this->className._.$filterHash);
	    		if(!empty($sort)){
	    			if(!empty($sort) && is_array($sort) && count($sort) == 2){
	    				$filter['sort'] = $sort;
	    			} else {
	    				unset($filter['sort']);
	    			}
	    			$hash = md5(serialize($filter));
	    			$this->cache->setItem($this->className._.$hash, $filter);
	    			return $this->redirect()->toRoute(null, array('f' => $hash, 'p' => 1));
	    			
	    		} else {
	    			$this->cache->touchItem($this->className._.$filterHash, 12*60*60);
	    			if(empty($filter)){
	    				return $this->redirect()->toRoute(null, array('f' => null, 'p' => 1));
	    			}
	    		}	    		
    		} else if (!empty($sort)){
    			if(!empty($sort) && is_array($sort) && count($sort) == 2){
    				$filter = ['sort' => $sort];
    			}    			
    			$hash = md5(serialize($filter));
    			$this->cache->setItem($this->className._.$hash, $filter);
    			return $this->redirect()->toRoute(null, array('f' => $hash, 'p' => 1));
    		}
    		if(empty($p)){
    			return $this->redirect()->toRoute(null, ['f' => $filterHash, 'p' => 1]);
    		}
    	}
    	
    	$counts = $listModel->getTotals($filter);
    	$items = $listModel->getItems($filter, $p, 50);
    	
    	$return = [
    			'page' => $p,
    			'pageCount' => ceil($counts['count'] / 50),
    			'counts' => $counts,
    			'items' => $items,
    			'filter' => $filter
    	];
    	
    	$extra = $this->index($return);
    	if(is_array($extra) && !empty($extra)){
    		$return = array_merge($return, $extra);
    	}
    	
    	return $return;
    }
    
    public function redirectToFilteredList($route, $filter, $sort = null){    	
    	if(!empty($sort) && is_array($sort) && count($sort) == 2){
    		$filter['sort'] = $sort;
    	}
    	$hash = md5(serialize($filter));
    	$this->cache->setItem($this->className._.$hash, $filter);
    	return $this->redirect()->toRoute($route, array('f' => $hash, 'p' => 1));
    }
    
    
    private function getSortParam(){
    	$sort = $this->params()->fromQuery('sort', null);
    	if($sort == 'no') return 'no';
    	if(empty($sort)) return null;
    	$arr = explode('||', $sort);
    	if(count($arr) !== 2) return null;
    	return $arr;
    }
    
    protected function index(){
    	
    }
    
    var $id;
    var $item;
    var $isNew;
    var $lang;
    /** @var $form Form */
    var $form;
    
    const NEWID = 'new';
    
    public function processEditForm($form, CRUDEditModel $crudModel){
    	if(!$form instanceof Form){
    		$form = $this->serv($form);
    	}
    	$this->form = $form;
    	$this->lang = $this->params('lang', 'ru');
    	$this->id = $this->params('id');
    	if($this->id !== self::NEWID){
    		$this->isNew = false;
    		$this->item = $crudModel->load($this->id);
    		if(empty($this->item)){
    			return $this->notFoundAction();
    		}
    	} else {    		
    		$this->isNew = true;
    		$this->item = $crudModel->create();
    	}
    	
     	if($this->request->isPost()){
     		$this->form->validate($this->request->getPost());
     		
     		if(!$this->form->hasErrors()){

     			$values = $this->form->values();
     			
     			$crudModel->validate($values);
  			 
     			if(array_key_exists('submit', $values)){
     				unset($values['submit']);
     			}
     			
     			if(!$this->form->hasErrors()){
     				$id = $crudModel->save($values);
     				if(!empty($id)){
     					$this->id = $id;
     					
     					if($this->db instanceof Discussion){
     						$newComment = $this->params()->fromPost('new_comment', null);
     						if(!empty($newComment)){
     							$this->db->addComment($this->id, $newComment);
     						}
     					}
     					
     				}
     				return $crudModel->afterSave();     				
     			}
     		}     		
     	} else {
	  		$this->form->values($this->item);
     	}
    	

     	if(!empty($this->form->hasField('submit'))){
     		$this->form->field('submit')
     			->addExtra('cancel-url', $this->historyBackUri ?: $this->url()->fromRoute('private/'.$this->roteMeaningPart.'-index'));
     	}
     	     	
    	$extra = $this->edit();
    	$ret = [
    		'id' => $this->id,
    		'form' => $this->form,
    		'item'=>  $this->item,
    		'isNew'=> $this->isNew
    	];
    	
    	if($this->db instanceof Discussion){
    		if(!$this->isNew){
    			$ret['comments'] = $this->db->getComments($this->id);
    		}    		
    		$ret['new_comment'] = $this->params()->fromPost('new_comment', null);
    	}
    	
    	if(is_array($extra)){    		
    		$ret = ArrayUtils::merge($ret, $extra);
    	}
    	
    	return $ret;    	    	
    }
    
    public function error($field, $msg){
    	$this->form->error($field, $msg);
    }
    
    var $historyBackUri = null;
    
    public function saveHistory(){
    	if($this->getRequest()->isGet() && !$this->getRequest()->isXmlHttpRequest()){
    		
	    	$uri = $this->getRequest()->getUri()->getPath();
	    	$routeMatch = $this->serv('Application')->getMvcEvent()->getRouteMatch();
	    	if(!empty($routeMatch)){
	    		$routeName = $routeMatch->getMatchedRouteName();
	    	} else {
	    		$routeName = '';
	    	}
	    	
	    	if(empty($this->session['history'])){
	    		$this->session['history'] = [];
	    	}
	    	
	    	if(count($this->session['history']) > 1 && $this->session['history'][1]['uri'] == $uri){
	    		array_shift($this->session['history']);
	    	}
	    	
	    	if(count($this->session['history']) > 0){
	    		if($this->session['history'][0]['routeName'] == $routeName){
	    			$this->session['history'][0]['uri'] = $uri;
	    		} else {
	    			array_unshift($this->session['history'], [
	    					'routeName' => $routeName,
	    					'uri' => $uri
	    			]);
	    			unset($this->session['history'][6]);
	    		}    		
	    	} else {
	    		array_unshift($this->session['history'], [
	    				'routeName' => $routeName,
	    				'uri' => $uri
	    		]);
	    	}
    	}
    	
    	if(count($this->session['history']) > 1){
    		$this->historyBackUri = $this->session['history'][1]['uri'];
    	}    	
    }
    
    
    protected function afterSaveRedirect($indexRoute = null, $editRoute = null, $useHistory = true){
    	if($indexRoute === null){
    		$indexRoute = 'private/'.$this->roteMeaningPart.'-index';
    	}
    	if($editRoute === null){
    		$editRoute = 'private/'.$this->roteMeaningPart.'-edit';    		
    	}
    	if($this->form->value('submit') == 'save'){
    		if(!empty($this->historyBackUri) && $useHistory){
    			return $this->redirect()->toUrl($this->historyBackUri);
    		} else {
    			return $this->redirect()->toRoute($indexRoute);
    		}
    	} else {
    		return $this->redirect()->toRoute($editRoute, ['id' => $this->id]);
    	}
    }
    
    
    protected function form($elementName = null){
    	if($elementName === null){
    		return $this->form;
    	} else {
    		return $this->form->field($elementName);
    	}
    	
    }
}