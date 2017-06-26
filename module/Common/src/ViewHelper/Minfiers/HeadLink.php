<? 
namespace Common\ViewHelper\Minfiers;

use Zend\View\Helper\HeadLink as ZendHeadLink;
use MatthiasMullie\Minify\CSS;

class HeadLink extends ZendHeadLink {

	var $publicPath = '';
	var $ext = 'css';
		
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
				$hrefsToMinifi[] = $item->href;
			}
		}			

		return $hrefsToMinifi;
	}
	
	public function needMinifi(\stdClass $item){
		if(isset($item->extras['nominifi']) && $item->extras['nominifi'] == true) return false;
		$ext = pathinfo($item->href, PATHINFO_EXTENSION);
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
		
		$minifier = new CSS();

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
			if($item->href == $hrefsToMinifi[0]){
				$item->href = '/cached/'.$cachedFileName;
			} else if(in_array($item->href, $hrefsToMinifi)){
				$toRemove[] = $key;
			}
		}
		
		sort($toRemove, SORT_DESC);
		foreach ($toRemove as $key){
			$this->offsetUnset($key);
		}
	}
	
}