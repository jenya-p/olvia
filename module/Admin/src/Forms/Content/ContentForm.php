<?php

namespace Admin\Forms\Content;

use Common\Form\Form;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;
use Admin\Forms\UsersElements;
use ZfAnnotation\Annotation\Service;
use Admin\Model\Users\UserDb;
use Common\Traits\Initializable;
use Admin\Forms\ImageElements;
use Admin\Model\Content\TagDb;
use Admin\Forms\TagElements;
use Admin\Model\Content\DivisionDb;

/**
 * @Service(name="Admin\Forms\Content\ContentForm")
 */
class ContentForm extends Form implements ServiceManagerAware, Initializable{
	
	use ServiceManagerTrait, UsersElements, ImageElements, TagElements;
	
	var $defaultElementDecorator = 'default';
		
	public function init(){
		
		$userDb = $this->serv(UserDb::class);
		$tagDb = $this->serv(TagDb::class);
		
 		$this->field('title', 'text', 'Название')
 			->validator('required', 'Заполните это поле');
 		
 		$this->field('alias', 'text', 'URL');
 			
 		$this->field('status', 'checkbox', 'Опубликовано');
 		
 		$this->field('priority', 'number', 'Приоритет')->description('Число. Чем больше, тем раньше показывается статья');
 		
 		$field = $this->field('type', 'select', 'Шаблон');
 		
	 	$field->option('page', 'Страница');
	 	$field->option('article', 'Статья');
	 		
	 	$field = $this->field('division_id', 'select', 'Раздел');	 			
	 	$field->options([$this, 'divisionOptions']);
	 	 		
	 	$field = $this->field('created', 'date', 'Дата публикации');
	 	
	 	$field = $this->field('author', 'user', 'Автор')
	 		->options($userDb);
	 	
	 	$field = $this->field('tags', 'tags', null, ['decorator' => 'simple'])
	 		->options($tagDb);
	 		
 		$this->field('body', 'ckeditor', null, ['decorator' => 'simple']);
 		
 		$this->field('seo_title', 'text', 'SEO Title');
 		
 		$this->field('seo_description', 'textarea', 'SEO Description');
 		
 		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
  		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 		
 		$this->addClass('g-form');
 		  		
	}

	
	public function divisionOptions(){
		/* @var $divisionDb DivisionDb */		
		$divisionDb = $this->serv(DivisionDb::class);
		$options = ['' => ['label' => 'Не определено', 'class' => 'empty']];
		$options = $options + $divisionDb->getOptions();
		return $options;		
	}
	
	
	
} 
