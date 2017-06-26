<?
namespace Common;

class VideoService{ 
	
	
	const YOUTUBE_APIKEY = 'AIzaSyDIjXbmeOxlgBEaBsFFcOyeqamNnh03s7k';
	
	var $imagePath = null;
	var $imageUrl = null;
	
	public function __construct($imagePath, $imageUrl){
		$this->imagePath = $imagePath;
		$this->imageUrl = $imageUrl;
	}	
	
	

	
	
}