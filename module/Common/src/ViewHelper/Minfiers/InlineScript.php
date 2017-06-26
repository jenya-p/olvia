<? 
namespace Common\ViewHelper\Minfiers;

use Zend\View\Helper\InlineScript as ZendInlineScript;
use MatthiasMullie\Minify\JS;

class InlineScript extends ZendInlineScript {

	var $publicPath = '';
	var $ext = 'js';
	
	public function __construct($publicPath){
		$this->publicPath = $publicPath;
		return parent::__construct();
	}
	
	
	public function toString($indent = null){

		$hrefsToMinifi = $this->getHrefsToMinifi();

		if(!empty($hrefsToMinifi)){
			$cachedFileName = md5( implode("||", $hrefsToMinifi) ).'.'.$this->ext;
			$this->minifi($hrefsToMinifi, $cachedFileName);
			$this->postMinifi($hrefsToMinifi, $cachedFileName);
		}
			
		return parent::toString($indent);
	}
	
	public function getHrefsToMinifi(){
		$this->getContainer()->ksort();
		
		$hrefsToMinifi = [];
	
		foreach ($this as $item) {
			if($this->needMinifi($item)){
				$hrefsToMinifi[] = $item->attributes['src'];
			}
		}
	
		return $hrefsToMinifi;
	}
	
	public function needMinifi(\stdClass $item){
		if((isset($item->attributes['nominifi']) && $item->attributes['nominifi'] == true) || !isset($item->attributes['src'])) return false;
		$ext = pathinfo($item->attributes['src'], PATHINFO_EXTENSION);
		if($ext == $this->ext) return true;
		return false;
	}
	
	/**
	 * @param string $href
	 * @return NULL|string
	 */
	public function getFilePath($href){
	
		if(substr($href, 0, 1) != '/') {
			return null;
		}
	
		$pos = strpos($href, '?');
		if($pos !== false){
			$href = substr($href, 0, $pos);
		}
	
		return $this->publicPath.str_replace('/', DIRECTORY_SEPARATOR, $href);
	
	}
	
	
	public function minifi($hrefsToMinifi, $cachedFileNameShort){
		$cachedFileName = $this->publicPath.'cached'.DIRECTORY_SEPARATOR.$cachedFileNameShort;
	
		$minifier = new JS();
	
		if(!is_file($cachedFileName)){
			foreach ($hrefsToMinifi as $href){
				$filePath = $this->getFilePath($href);
				if(is_file($filePath)){
					$minifier->add($filePath);
				}
			}
			$minifier->minify($cachedFileName);
		}
	}
	
	
	public function postMinifi($hrefsToMinifi, $cachedFileName){
		$toRemove = [];
	
		foreach ($this as $key => &$item) {
			if($item->attributes['src'] == $hrefsToMinifi[0]){
				$item->attributes['src'] = '/cached/'.$cachedFileName;
			} else if(in_array($item->attributes['src'], $hrefsToMinifi)){
				$toRemove[] = $key;
			}
		}
	
		sort($toRemove, SORT_DESC);
		foreach ($toRemove as $key){
			$this->offsetUnset($key);
		}
	}
	
}