<?php

namespace Admin\Forms\Users;

use Common\Form\Form;
use ZfAnnotation\Annotation\Service;
use Common\Traits\Initializable;
use Admin\Forms\ImageElements;

/**
 * @Service
 */
class MasterForm extends Form implements Initializable{
	
	use ImageElements;
	
	var $defaultElementDecorator = 'default';
	
	
	public function init(){
		
		$this->addClass('g-form');
			
  		$this->field('name', 		'text', null, ['label' => 'Имя преподавателя']);
  		
  		$this->field('alias', 'text', 'URL');
  		
  		$this->field('status', 'checkbox', 'Стреница специалиста опубликована');
  		
  		$this->field('priority', 'number', 'Приоритет')->description('Число. Чем больше, тем раньше показывается статья');
  		
  		$this->field('personal', 	'checkbox', null, ['label' => 'Персональные консультации', 'description' => 'Преподаватель будет отображаться на форме "Персональные консультации", к нему можно записаться']);
  		
  		$this->field('group', 	'checkbox', null, ['label' => 'Преподавание в группах']);
  		
  		$this->field('summary', 'ckeditor', null, ['decorator' => 'simple']);
  		
  		$this->field('body', 'ckeditor', null, ['decorator' => 'simple']);
  		
  		$this->field('image', 	'image-upload', null)
  			->label(['text' => null]);
  		
  		$this->field('consultation', 'ckeditor', null, ['decorator' => 'simple']);
  		
  		$this->field('education', 'ckeditor', null, ['decorator' => 'simple']);
  		
  		$this->field('seo_title', 'text', 'SEO Title');
  			
  		$this->field('seo_description', 'textarea', 'SEO Description');
  			
  		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
  		
  		
  		  			
  			
  		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
 			  		
	}
} 
