<?php

namespace Admin\Forms\Content;

use Admin\Forms\ImageElements;
use Common\Form\Form;
use Common\Traits\Initializable;
use Common\Traits\ViewAware;
use Common\Traits\ViewTrait;
use ZfAnnotation\Annotation\Service;
use Common\Traits\ServiceManagerAware;
use Common\Traits\ServiceManagerTrait;

/**
 * @Service
 */
class DivisionForm extends Form implements ServiceManagerAware, Initializable{
		
	use ImageElements, ServiceManagerTrait;

	var $defaultElementDecorator = 'default';
	
	public function init(){
		
		$this->addClass('g-form');
				
  		$this->field('title', 'text', 'Название')
  			->validator('required', 'Заполните это поле');
	
  		$this->field('alias', 'text', 'URL');
  		
  		$this->field('status', 'checkbox', 'Опубликовано');
  		
  		$this->field('priority', 'number', 'Приоритет')
  			->description('Число. Чем больше, тем раньше показывается раздел');
  			
  		$this->field('image', 'image-upload', 'Изображение');
  			
  		$this->field('seo_title', 'text', 'SEO Title');
	
  		$this->field('seo_description', 'textarea', 'SEO Description');
	
  		$this->field('seo_keywords', 'textarea', 'SEO Keywords');
  		
 		$this->field('submit', 'submit-group', null, ['label-save' => 'Сохранить', 'label-apply' => 'Применить', 'decorator' => 'simple']);
  	}
  	


} 
