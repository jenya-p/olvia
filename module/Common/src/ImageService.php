<?
namespace Common;

class ImageService{ 
	
	const SIZE_VIDEO_THUMB = 'vt';
	const SIZE_REVIEW_USERPICK = 'rup';
	const SIZE_BANNER = 'b'; 
	const SIZE_ADMIN_LIST_THUMB = 'alt';
	const SIZE_USERPICK_LARGE = 'ul';
	const SIZE_SEARCHLIST = 'sl';
	
	private $overwrite = false;
	
	var $sizes = array(
		self::SIZE_VIDEO_THUMB => 	[218,164],
		self::SIZE_REVIEW_USERPICK => [78, 78],
		self::SIZE_BANNER => [null, 550],
		self::SIZE_ADMIN_LIST_THUMB => [75, 25],
		self::SIZE_USERPICK_LARGE => [310,310],
		self::SIZE_SEARCHLIST => [135,135],
	);
	
	var $sufixes = array(
		self::SIZE_VIDEO_THUMB => '_vth',
		self::SIZE_REVIEW_USERPICK => '_rup',
		self::SIZE_BANNER => '_b',
		self::SIZE_ADMIN_LIST_THUMB => '_thumb',
		self::SIZE_USERPICK_LARGE => '_ul',
		self::SIZE_SEARCHLIST => '_sl',
	);
	
	const ORIGIN_SUFIX = '_origin';
	
	var $imagePath = null;
	var $imageUrl = null;
	
	public function __construct($imagePath, $imageUrl){
		$this->imagePath = $imagePath;
		$this->imageUrl = $imageUrl;
	}	
	
	public function getSize($srcImageUrl){
		$srcFileName = $this->urlToPath($srcImageUrl);		
		return getimagesize($srcFileName);
	}	
	
	public function resize($srcImageUrl, $sizeX = 730, $sizeY = null, $sufix = null) {

		if(empty($srcImageUrl)){
			return $this->getStub($sizeX, $sizeY);
		}
		
		if(array_key_exists($sizeX, $this->sizes)){
			$sizeY = $this->sizes[$sizeX][1];
			$sufix = $this->sufixes[$sizeX];
			$sizeX = $this->sizes[$sizeX][0];
		}
				
		$srcFileName = $this->urlToPath($srcImageUrl);
		
		$imageData = getimagesize($srcFileName);
	
		$w = $imageData[0];
		$h = $imageData[1];
		 
		$x1 = 0; $y1 = 0;
		$x2 = $w; $y2 = $h;
		
		if($sizeX == null && $sizeY == null){
			$sizeX = $w;
			$sizeY = $h;			
		} else if($sizeX == null){
			
			$sizeX = floor($sizeY * ($w / $h));
			
		} else if($sizeY == null){			
		
			$sizeY = floor($sizeX * ($h / $w));
			
		} else {			
			$d = $sizeX / $sizeY;				
			if($d < ($w / $h)){
				$y1 = 0; $y2 = $h;
				$x2 = min($w, floor($h * $d));
				$x1 = floor(($w - $x2) / 2);
			} else {
				$x1 = 0; $x2 = $w;
				$y2 = min($h, floor($w / $d));
				$y1 = floor(($h - $y2) / 2);
			}				
		}
		
		$pathInfo = pathinfo($srcFileName);
		if($sizeX < 50){
			$dstExt = 'png';
		} else {
			$dstExt = 'jpg';
		}
		
		if(empty($sufix)){
			$sufix = '_'.$sizeX;
			if(!empty($sizeY)){
				$sufix .= 'x'.$sizeY;
			}
		}
		
		$pathInfo = pathinfo($srcFileName);
		$dstFileName = strtolower($pathInfo['filename']);
		$dstFileName = str_replace(self::ORIGIN_SUFIX, '', $dstFileName);
		$dstFileName = $pathInfo['dirname'].DIRECTORY_SEPARATOR.$dstFileName.$sufix.'.'.$dstExt;
		
		if($this->overwrite == false && is_file($dstFileName)){
			return $this->pathToUrl($dstFileName);
		}
		
		$img = $this->load($srcFileName, $imageData['mime']);
		
		$newImg = imagecreatetruecolor($sizeX, $sizeY);
		
		if($dstExt == 'png'){
			$white = imagecolorallocate($newImg, 250,250,250);			
			imagefilledrectangle($newImg, 0, 0, $sizeX, $sizeY, $white);
			imagecolortransparent($newImg, $white);
		} else if ($pathInfo['extension'] == 'png'){
			$white = imagecolorallocate($newImg, 250,250,250);
			imagefilledrectangle($newImg, 0, 0, $sizeX, $sizeY, $white);
		} else {
			$black = imagecolorallocate($newImg, 0, 0, 0);
			imagefilledrectangle($newImg, 0, 0, $sizeX, $sizeY, $black);
		}
		
		imagecopyresampled($newImg, $img, 0, 0, $x1, $y1, $sizeX, $sizeY, $x2, $y2);
		
 		if($sizeX + $sizeY < 200){
 			$this->save($newImg, $dstFileName, 50);
 		} else if($sizeX + $sizeY < 450){
 			$this->save($newImg, $dstFileName, 50);
 		} else {
			$this->save($newImg, $dstFileName, 80);
 		}
		
		imagedestroy($img);
		imagedestroy($newImg);
		
		return $this->pathToUrl($dstFileName);
	}
	
	public function load($fullImage,$mime) {
		switch ($mime) {
			case 'image/jpg':
			case 'image/jpeg':
				return imagecreatefromjpeg($fullImage);
			case 'image/gif':
				return imagecreatefromgif($fullImage);
			case 'image/png':
				return imagecreatefrompng($fullImage);
			case 'image/bmp':
				return imagecreatefromwbmp($fullImage);
			default:
				return false;
		}
	}
	
	public function save($image, $filename, $quality = 80){
		$ext = pathinfo($filename, PATHINFO_EXTENSION);
		switch (strtolower($ext)) {
			case 'jpg':
			case 'jpeg':
				return imagejpeg($image, $filename, $quality);
			case 'gif':
				return imagegif($image, $filename);
			case 'png':
				return imagepng($image, $filename);
			case 'bmp':
				return image2wbmp($image, $filename);
			default:
				return false;
		}		
	}
	
	public function normalizeUrl($url){		
		$url = ltrim(str_replace('\\', '/', $url), '/');		
		$len = strlen($this->imageUrl);
		
		if(strtolower(substr($url, 0, $len-1)) == strtolower(substr($this->imageUrl, 1))){
			$url = substr($url, $len-1);
		}
		
		return $this->imageUrl.$url;
	}
	
	public function pathToUrl($fileName){
		$pathLen = strlen($this->imagePath);
		if(strtolower(substr($fileName, 0, $pathLen)) == strtolower($this->imagePath)){
			$fileName = substr($fileName, $pathLen);
		}		
		return $this->imageUrl.ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $fileName), '/');
	}
	
	public function urlToPath($url){
		$urlLen = strlen($this->imageUrl);
		if(strtolower(substr($url, 0, $urlLen)) == strtolower($this->imageUrl)){
			$url = substr($url, $urlLen);
		}
		return $this->imagePath.str_replace('/', DIRECTORY_SEPARATOR, ltrim($url, '/'));		
	}
	
	
	/**
	 * 
	 * @param array $file 
	 * @throws Exception
	 * @return string
	 */
	public function import($file, $nameSample = null){
		
		if(is_array($file)){				
			$name 	= $file['name'];
			$source = $file['tmp_name'];
		} else {
			$name 	= pathinfo($file, PATHINFO_BASENAME);
			$source = $file;
		}
	 
		$ext = strtolower(@end(explode('.', $name)));
			
		if($ext != 'jpg' && $ext != 'jpeg' && $ext != 'png' && $ext != 'bmp'){
			throw new \Exception('Сюда можно загружать только картинки (jpeg, png, bmp)');			
		}
	
		if (!is_file($source)) {
			throw new \Exception('Что то пошло не так. Попробуйте позже.');			
		}
	
		$fileName = $this->createFileName($name, $nameSample);

		copy($source, $fileName);
	
		$oiriginal = $this->pathToUrl($fileName);

		return $oiriginal;
	}
	
	/**
	 *
	 * @param string $imageUrl
	 * @throws Exception
	 * @return string
	 */
	public function importUrl($imageUrl, $nameSample = null){
	
		$ext = strtolower(pathinfo($imageUrl, PATHINFO_EXTENSION));
		$name = strtolower(pathinfo($imageUrl, PATHINFO_BASENAME));
				
		if($ext != 'jpg' && $ext != 'jpeg' && $ext != 'png' && $ext != 'bmp'){
			throw new \Exception('Сюда можно загружать только картинки (jpeg, png, bmp)');
		}
		
		$fileName = $this->createFileName($name, $nameSample);
		
		try {
			file_put_contents($fileName, fopen($imageUrl, 'r'));
		} catch (\Exception $e){
			throw new \Exception('Что то пошло не так. Попробуйте позже.');
		}
		
		return  $this->pathToUrl($fileName);
		
	}
	
	
	
	/**
	 * @param name
	 */
	private function createFileName($nameFromHttp, $nameSample = null) {
		$ext = strtolower(pathinfo($nameFromHttp, PATHINFO_EXTENSION));
		
		if($nameSample == null){
			$nameSample = date('ymdHis');
		}
		
		$baseDir = $this->imagePath . DIRECTORY_SEPARATOR;
		
		$dir = dirname($baseDir.$nameSample.'.'.$ext);
		if(!file_exists($dir)){
			mkdir($dir, 0777, true);
		}
		
		$randPart = '';
		
		if($this->overwrite){
			return $baseDir.$nameSample.$randPart.self::ORIGIN_SUFIX.'.'.$ext;
		}		
		for ($i = 0; $i < 3; $i++){
			if(!file_exists($baseDir.$nameSample.$randPart.self::ORIGIN_SUFIX.'.'.$ext)){
				return $baseDir.$nameSample.$randPart.self::ORIGIN_SUFIX.'.'.$ext;
			}
			$randPart = '-'.rand(100, 999);
		}
		throw new \Exception("Не удалось подобрать имя для нового файла (nameSample=".$nameSample.")");
	}


	
	public function crop(&$image, array $crop){
		$original = $image->getOriginal();
		$srcFileName = $this->urlToPath($original);
		
		$imageData = getimagesize($srcFileName);
			
		$x = $crop['x'];
		$y = $crop['y'];
		$w = $crop['width'];
		$h = $crop['height'];

		$newFileName = $this->createFileName($srcFileName);
				
		// Medium		
		$sizeX = $this->sizes[self::SIZE_MEDIUM][0];
		$sizeY = $this->sizes[self::SIZE_MEDIUM][1];
		
		$img = $this->load($srcFileName, $imageData['mime']);
		$newImg = imagecreatetruecolor($sizeX, $sizeY);		 
		imagecopyresampled($newImg, $img, 0, 0, $x, $y, $sizeX, $sizeY, $w, $h);
		
		$dstFileName = $this->urlToPath($image->getMedium());
		// @unlink($dstFileName);
		
		$dstFileName = $this->fileNameForSize($newFileName, self::SIZE_MEDIUM);
		
		imagejpeg($newImg, $dstFileName, 100);
		imagedestroy($newImg);
		$image->setMedium($this->pathToUrl($dstFileName));
		
		// Small
		$sizeX = $this->sizes[self::SIZE_SMALL][0];
		$sizeY = $this->sizes[self::SIZE_SMALL][1];		
		$d = $sizeX / $sizeY;
				
		if($d < ($w / $h)){
			$dY = 0; 
			$dX = round( ($w - $sizeX * $h / $sizeY) / 2 );
		} else {
			$dX = 0;
			$dY = round( ($h - $sizeY * $w / $sizeX) / 2 );			
		}
		
		$newImg = imagecreatetruecolor($sizeX, $sizeY);
		imagecopyresampled($newImg, $img, 0, 0, $x + $dX, $y + $dY, $sizeX, $sizeY, $w - 2* $dX, $h - 2 * $dY);
		$dstFileName = $this->urlToPath($image->getSmall());
		// @unlink($dstFileName);
		$dstFileName = $this->fileNameForSize($newFileName, self::SIZE_SMALL);		
		imagejpeg($newImg,$dstFileName, 100);
		imagedestroy($newImg);
		$image->setSmall($this->pathToUrl($dstFileName));
		imagedestroy($img);
	}
	
	private function fileNameForSize($fileName, $size){
		$pathInfo = pathinfo($fileName);
		$newFileName = $pathInfo['dirname'].DIRECTORY_SEPARATOR.
			strtolower($pathInfo['filename']).
			$this->sufixes[$size].'.'.strtolower($pathInfo['extension']);
		return $newFileName;
	}

	public function getOverwrite() {
		return $this->overwrite;
	}

	public function setOverwrite($overwrite) {
		$this->overwrite = $overwrite;
		return $this;
	}
	
	
	public function getStub($sizeX, $syzeY){
		if($sizeX == self::SIZE_VIDEO_THUMB){
			return $this->imageUrl.'blank_vth.png';
		} if($sizeX == self::SIZE_USERPICK_LARGE){
			return $this->imageUrl.'no-photo-ul.jpg';
		} else {
			return $this->resize($this->imageUrl.'dummy.jpg', $sizeX, $syzeY);
		}
	}
}