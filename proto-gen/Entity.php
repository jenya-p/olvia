<?
define('DC', DIRECTORY_SEPARATOR);
$targetDir = __DIR__ . DC . '..';
$bkpDir = dirname(dirname(__DIR__)) . DC . 'olvia-build' . DC . 'proto-bkp' . DC . date('ymd_h_i');
class Entity {
	public $name;
	public $namespace;
	public $multilingual = false;
	public $historical = false;
	public $historyAlias;
	public $optionModel = false;
	public $title;
	public $source;
	public $sourceAlias;
	public $route;
	public $clientRoute;
	public $messages = [ 
			"error_insert" => "Ошибка сохранения",
			"success_insert" => "Объект создан",
			"success_save" => "Тэг сохранен",
			"new_object" => "Новый объект",
			"list_title" => "Список",
			'edit_left_header' => 'Редактирование',
			"create_new_label" => "Новый объект",
	];

	public function message($name, $msg = null) {
		if ($msg === null) {
			return $this->messages [$name];
		} else {
			$this->messages [$name] = $msg;
			return $this;
		}
	}
	public $fields;
	public $nameField = 'title';

	function field($name, $multilingual = false, $formType = null, $title = null) {
		$this->fields [$name] = [ 
				'name' => $name,
				'multilingual' => $multilingual,
				'formType' => $formType,
				'title' => $title 
		];
		return $this;
	}

	public function fields($names) {
		foreach ( $names as $name ) {
			switch ($name) {
				case "name": 
				case "title" :
					$this->field($name, true, 'text', 'Название');					
					break;
				case "body" :
				case "description" :
					$this->field($name, true, 'textarea', 'Текст');
					break;
				case "alias" :
					$this->field($name, false, 'text', 'УРЛ');
					break;
				case "status" :
					$this->field($name, false, 'select', "Статус");
					break;
				case "active" :
					$this->field($name, false, 'checkbox', "Опубликовано");
					break;
				case 'author' :
				case 'author' :
					$this->field($name, false, 'user', 'Автор');
					$this->formTrait('Admin\Forms\UsersElements');
					break;
				case 'created' :
				case 'date' :
					$this->field($name, false, 'date', 'Дата');
					break;
				case "views" :
					$this->field($name, false);
					break;
				case "priority" :
					$this->field($name, false, 'number', "Приоритет");
					break;
				case "image" :
					$this->field($name, false, 'image-upload', 'Изображение');
					$this->formTrait('Admin\Forms\ImageElements');
					break;
				case 'seo' :
					$this->field("seo_title", true, 'text', 'SEO Title');
					$this->field("seo_description", true, 'textarea', 'SEO Descritpion');
					$this->field("seo_keywords", true, 'textarea', 'SEO Keywords');
					break;
				default:
					if(is_array($name)){
						call_user_func_array([$this, 'field'], $name);
					} else {
						throw new \Exception('standart field "'.$name.'" not defined');
					}					
			}
		}
	}

	public function langFields() {
		$ret = [ ];
		foreach ( $this->fields as $field ) {
			if ($field ['multilingual']) {
				$ret [] = $field ['name'];
			}
		}
		return $ret;
	}

	public function getFormClass() {
		return $this->name . "Form";
	}

	public function getFormClassFull() {
		return 'Admin\\Forms\\' . $this->nameInSpace() . "Form";
	}

	public function getDbClass() {
		return $this->name . "Db";
	}

	public function getDbClassFull() {
		return 'Admin\\Model\\' . $this->nameInSpace() . "Db";
	}

	public function nameInSpace($delim = '\\') {
		if (! empty($this->namespace)) {
			return $this->namespace . $delim . $this->name;
		} else {
			return $this->name;
		}
	}

	public function getControllerNamespace() {
		if (! empty($this->namespace)) {
			return 'Admin\\Controller\\' . $this->namespace;
		} else {
			return 'Admin\\Controller';
		}
	}

	public function getFormNamespace() {
		if (! empty($this->namespace)) {
			return 'Admin\\Forms\\' . $this->namespace;
		} else {
			return 'Admin\\Forms';
		}
	}

	public function getDbNamespace() {
		if (! empty($this->namespace)) {
			return 'Admin\\Model\\' . $this->namespace;
		} else {
			return 'Admin\\Model';
		}
	}
	var $formTraits = [  
	];
	var $formTraitsFull = [  
	];

	public function formTrait($fullName) {
		if(!in_array($fullName, $this->formTraitsFull)){
			$this->formTraitsFull [] = $fullName;
			$this->formTraits [] = substr($fullName, strrpos($fullName, '\\') + 1);
		}		
	}
	
	public function getFieldDQL(){
		$rows = [];
		foreach ($this->fields as $field){
			switch ($field['formType']) {
				case "select":
				case "checkbox":
					$def = 'TINYINT(4) NOT NULL DEFAULT \'1\'';
					break;
				case "text":
				case "image-upload":
					$def = 'VARCHAR(128) NULL DEFAULT NULL';
					break;
				case "textarea":
					$def = 'LONGTEXT NULL';
					break;
				case 'date':
				case 'user':
				default:
					$def = 'INT(11) NOT NULL DEFAULT \'0\'';
			}
			if($field['multilingual']){
				$rows[] = "\t".'`'.$field['name'].'_ru` '.$def;
				$rows[] = "\t".'`'.$field['name'].'_en` '.$def;
			} else {
				$rows[] = "\t".'`'.$field['name'].'` '.$def;
			}
		}
		return implode(",\n", $rows);		
	}
	
	public function getDQL(){
		return 'DROP TABLE IF EXISTS '.$this->source.';'."\n".
'CREATE TABLE `'.$this->source.'` (
	`id` INT(11) NOT NULL AUTO_INCREMENT,
'.$this->getFieldDQL().',
	PRIMARY KEY (`id`)
)
COLLATE=\'utf8_general_ci\'
ENGINE=InnoDB
AUTO_INCREMENT=1;'."\n\n";
	}
	
	
	public function getHistoryDefs(){
		if(empty($this->historyAlias)) return '';
		$rows = [];
		foreach ($this->fields as $field){
			if(!empty($field['title'])){
				$title = $field['title'];
			} else {
				$title = $field['name'];
			}
			if($field['multilingual']){
				if(!empty($field['formType'] == "textarea")){
					$rows[] = "\t\t\t".'\''. $field['name'] .'\' => \'Изменен '.$title.' (Ру)\'';
					$rows[] = "\t\t\t".'\''. $field['name'] .'\' => \'Изменен '.$title.' (En)\'';
				} else {
					$rows[] = "\t\t\t".'\''. $field['name'] .'\' => \''.$title.' (Ру): %2$s → %1$s\'';
					$rows[] = "\t\t\t".'\''. $field['name'] .'\' => \''.$title.' (En): %2$s → %1$s\'';
				}
			} else {
				if(!empty($field['formType'] == "textarea")){
					$rows[] = "\t\t\t".'\''. $field['name'] .'\' => \'Изменен '.$title.'\'';
				} else {
					$rows[] = "\t\t\t".'\''. $field['name'] .'\' => \''.$title.': %2$s → %1$s\'';
				}
			}
		}
		
		$rows[] = "\t\t\t".'\'_delete\' => \''.$this->title.' удален\'';
		
		return "\t\t'".$this->historyAlias."' => [\n".implode(",\n", $rows)."\n\t\t],\n\n";
	}
	
	public function getViewPath(){
		return $this->from_camel_case($this->nameInSpace('/'));
		
	}
	
	public function getViewPathAndName(){
		return $this->getViewPath().'/'.$this->from_camel_case($this->name);
	}
	
	function from_camel_case($camel) {
		return strtolower(implode('/', array_map(function($camel1){
			return preg_replace('/(?!^)[[:upper:]][[:lower:]]/', '$0', preg_replace('/(?!^)[[:upper:]]+/', '-'.'$0', $camel1));
		}, explode('/', $camel))));
	}
	
}

function generate($entity) {
	createFile($entity, 'Controller', DC . 'module' . DC . 'Admin' . DC . 'src' . DC . 'Controller' . DC . $entity->nameInSpace(DC) . 'Controller.php');
	createFile($entity, 'Db', DC . 'module' . DC . 'Admin' . DC . 'src' . DC . 'Model' . DC . $entity->nameInSpace(DC) . 'Db.php');
	createFile($entity, 'Form', DC . 'module' . DC . 'Admin' . DC . 'src' . DC . 'Forms' . DC . $entity->nameInSpace(DC) . 'Form.php');
	
	createFile($entity, 'view.edit', DC . 'module' . DC . 'Admin' . DC . 'view' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-edit.phtml');
	createFile($entity, 'view.edit.left', DC . 'module' . DC . 'Admin' . DC . 'view' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-edit.left.phtml');
	createFile($entity, 'view.index', DC . 'module' . DC . 'Admin' . DC . 'view' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-index.phtml');
	createFile($entity, 'view.index.filter', DC . 'module' . DC . 'Admin' . DC . 'view' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-index.filter.phtml');
	
	createFile($entity, 'view.index.js', DC . 'public' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-index.js');
	createFile($entity, 'view.edit.js', DC . 'public' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-edit.js');
	createFile($entity, 'view.index.css', DC . 'public' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-index.css');
	createFile($entity, 'view.edit.css', DC . 'public' . DC . 'admin' . DC . $entity->getViewPathAndName() . '-edit.css');
	
   echo $entity->getDQL();
	
   echo $entity->getHistoryDefs();
}

function createFile($entity, $src, $dst) {
	global $targetDir, $bkpDir;
	if (! is_file(__DIR__ . DC . 'protos' . DC . $src . '.phtml')) {
		echo '\nНе найден прототип ' . $src . '.phtml';
	}
	ob_start();
	include __DIR__ . DC . 'protos' . DC . $src . '.phtml';
	$content = ob_get_clean();
	
	if (file_exists($targetDir . $dst)) {
		$dir = dirname($bkpDir . $dst);
		if (! is_dir($dir)) {
			mkdir($dir, null, true);
		}
		copy($targetDir . $dst, $bkpDir . $dst);
	}
	$dir = dirname($targetDir . $dst);
	if (! is_dir($dir)) {
		mkdir($dir, null, true);
	}
	file_put_contents($targetDir . $dst, $content);
}

	