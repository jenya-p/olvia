<? 
namespace Common\ViewHelper;


class Assets extends \Zend\View\Helper\AbstractHelper {
		
	var $publicPath;
	var $routeAssetPath;
	
	const SUMMERNOTE = 'summernote';
	const BOOTSTRAP = 'bootstrap';
	const CKEDITOR = 'ckeditor';
	
	var $names = [];
	
	var $map = [
			'reset' => 			['style' => 	'/admin/css/reset.css','minify' => true],
			'jquery' => 		['script' => 	'/assets/jquery/jquery.min.js'],
			'modernizr' =>		['script' => 	'/assets/modernizr.js'],
			'font-awesome' =>	['style' => 	'/assets/font-awesome/css/font-awesome.min.css'],
			'commons' => [
				'script' => [
					'/admin/js/common.js',
					'/admin/js/scrollspy.js',					
				],
				'style' => [	
					'/admin/css/style.css',						
				],
				'deps' => ['modernizr','reset','jquery','font-awesome', 'placeholder','mousewheel','maskedinput','datepicker','autosize'],
				'minify' => true,
			],
			'placeholder' => [
				'script' => '/assets/jquery.placeholder/jquery.placeholder.min.js',
				'deps' => ['jquery']
			],			
			'mousewheel' => [
				'script' => '/assets/jquery.mousewheel/jquery.mousewheel.min.js',
				'deps' => ['jquery']
			],
			'maskedinput' => [
				'script' => '/assets/jquery.maskedinput/jquery.maskedinput.min.js',
				'deps' => ['jquery']
			],
			'datepicker' => [
				'script' => [
						'/assets/moment-with-locales.js',
						'/assets/date-range-picker/jquery.daterangepicker.min.js'],
				'style' => '/assets/date-range-picker/daterangepicker.min.css',
				'deps' => ['jquery']
			], 
			'autosize' =>		[
					'script' => '/assets/autosize/autosize.min.js',
					'deps' => ['jquery']	
			],
			self::BOOTSTRAP => [
				'script' => '/assets/bootstrap/js/bootstrap.min.js',
				'style' => [
						'/assets/bootstrap/css/bootstrap.min.css',
// 						'/assets/bootstrap/css/bootstrap-theme.min.css',
					], 
				'deps' => ['jquery'],
				'minify' => false
			],
			self::SUMMERNOTE =>	[
					'script' => '/assets/summernote/dist/summernote.min.js',
					'style' => '/assets/summernote/dist/summernote.css',
					'deps' => ['jquery', self::BOOTSTRAP]					
			],
			self::CKEDITOR =>	[
					'script' => [
							'/assets/ckeditor/ckeditor.js',
							'/assets/ckeditor/config.js',
					],
					'deps' => ['jquery'],
					'minify' => false					
			],
			'dropzone' => [
					'script' => '/assets/dropzone/dropzone.js',
					'style' => '/assets/dropzone/dropzone.css',
					'deps' => ['jquery']
			],
			'fancybox' => [
					'script' => '/assets/fancybox/jquery.fancybox.min.js',
					'style' => '/assets/fancybox/jquery.fancybox.min.css',
					'deps' => ['jquery']
			],
			'autocomplete' => [
					'script' => '/assets/jquery-autocomplete/jquery.autocomplete.min.js',
					'deps' => ['jquery']
			],
			'user-select' => [
					'script' => '/admin/js/user-select.js',
					'deps' => ['autocomplete', 'commons']
			],
			'course-select' => [
					'script' => '/admin/js/course-select.js',
					'deps' => ['autocomplete', 'commons']
			],
			'comments' => [
					'script' => '/admin/js/comments.js',
					'deps' => ['commons']
			],
			'image-upload' => [
					'script' => '/admin/js/image-upload.js',
					'deps' => ['fancybox', 'commons']
			],
			'tag-select' => [
					'script' => '/admin/js/tag-select.js',
					'deps' => ['autocomplete', 'commons']
			],
			'discounts-element' => [
					'script' => '/admin/js/discounts-element.js',
					'deps' => ['jquery']
			],
			'upload' => [
					'script' => '/assets/jquery.html5uploader/jquery.html5uploader.js',
					'deps' => ['jquery']
			],
			'r-text' => [
					'script' => '/admin/js/r-text.js',
					'deps' => ['jquery']
			],			
			'photoswipe' => [
					'style' => ['/assets/photoswipe/photoswipe.css',
						'/assets/photoswipe/default-skin/default-skin.css'],					
					'script' =>[ '/assets/photoswipe/photoswipe.min.js',
						'/assets/photoswipe/photoswipe-ui-default.min.js'],
					'deps' => ['jquery']
			]	
	]; 
	

	
    public function __construct($publicPath, $routeAssetPath){
        $this->publicPath = $publicPath;
        $this->routeAssetPath = $routeAssetPath;
    }

    public function routeAsset($name){
    	
    }
    
    public function __invoke($name = null){
    	if($name !== null){
    		if(!is_array($name)){
    			$name = [$name];
    		}
    		foreach ($name as $name1){
    			if(array_key_exists($name1, $this->map)){
    				$this->names[] = $name1;	
    			}
    		}
    	}
    	return $this;
    }
    
    public function requireAsset($name){    	
    	return $this($name);
    }
    
    
    public function append(){
    	$this->addRouteAsset();
    	$this->compile($this->names);
    	foreach ($this->scripts as $script){
    		if(in_array($script, $this->nominify['scripts'])){
    			$this->getView()->inlineScript()->appendFile($script,  'text/javascript', ['nominifi' => true]);
    		} else {
    			$this->getView()->inlineScript()->appendFile($script,  'text/javascript');
    		}    		
    	}
    	foreach ($this->styles as $style){
    		if(in_array($style, $this->nominify['styles'])){
    			$this->getView()->headLink()->appendStylesheet($style, 'screen', '', ['nominifi' => true]);
    		} else {
    			$this->getView()->headLink()->appendStylesheet($style);
    		}    		
    	}
    }
    
    public function prepend(){
    	$this->addRouteAsset();
    	$this->compile($this->names);

    	foreach (array_reverse($this->scripts) as $script){
    		if(in_array($script, $this->nominify['scripts'])){
    			$this->getView()->inlineScript()->prependFile($script,  'text/javascript', ['nominifi' => true]);
    		} else {
    			$this->getView()->inlineScript()->prependFile($script,  'text/javascript');
    		}   
    	}
    	
    	foreach (array_reverse($this->styles) as $style){
    		if(in_array($style, $this->nominify['styles'])){
    			$this->getView()->headLink()->prependStylesheet($style, 'screen', '', ['nominifi' => true]);
    		} else {
    			$this->getView()->headLink()->prependStylesheet($style);
    		}
    	}    	
    }
    

    var $scripts = [];
    var $styles = [];
    
    var $compiled = [];
    var $nominify = ['scripts' => [],'styles' => []];
    
    
    private function compile($names){
    	
    	foreach ($names as $name){
    		if(in_array($name, $this->compiled)){
    			continue;
    		}
    		
    		if(!empty($this->map[$name]['deps'])){
    			$this->compile($this->map[$name]['deps']);
    		}
    		
    		$scripts = $this->map[$name]['script'];
    		if(is_string($scripts)){
    			$scripts = [$scripts];
    		}
    		if(!empty($scripts)){
    			$this->scripts = array_merge($this->scripts, $scripts);    			
    		}
    		   		
    		$styles = $this->map[$name]['style'];
    		if(is_string($styles)){
    			$styles = [$styles];
    		}
    		if(!empty($styles)){
    			$this->styles = array_merge($this->styles, $styles);    			
    		}    		   		
    		if($this->map[$name]['minify'] === false){
    			$this->nominify['scripts'] = array_merge($this->nominify['scripts'], $scripts);
    			$this->nominify['styles'] = array_merge($this->nominify['styles'], $styles);
    		}
    		
    		$this->compiled[] = $name;
    	}
    }
    
    
    private function addRouteAsset(){
    	$path = $this->routeAssetPath;
    	if($path == null || array_key_exists($path, $this->names)){
    		return ;
    	}
    	$spec = [];

    	if(file_exists($this->publicPath.$path.'.js')){
    		$spec['script'] = '/'.str_replace(DIRECTORY_SEPARATOR, '/', $path).'.js';
    	}
    	if(file_exists($this->publicPath.$path.'.css')){
    		$spec['style'] = '/'.str_replace(DIRECTORY_SEPARATOR, '/', $path).'.css';
    	}

    	if(!empty($spec)){
    		$spec['deps'] = ['commons'];
    		$spec['minify'] = true;
    		$this->map[$path] = $spec;
    		$this->requireAsset($path);    		
    	}
    	
    	$this->routeAssetPath = null;
  		return $this;  	
    }
    
}