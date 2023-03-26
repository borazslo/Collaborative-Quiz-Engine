<?php

class questionPair extends Question {
    
        public $inputType = 'select';
    
        function prepareQuestion() {
            global $user;

            if(isset($this->pairs) AND !is_array($this->pairs)) $this->pairs = ($this->pairs)();
                                    
			if($this->pairs === array()) return;
			
			
            $c = $this->pseudoRandom(0, count($this->pairs) - 1 , $this->setUnique() );

			
			if(!isset($this->question)) $this->question = '';
			
				if(is_array($this->pairs[$c][0])) {
					$d = $this->pseudoRandom(0, count($this->pairs[$c][0]) - 1 , "user" );
					$question = $this->pairs[$c][0][$d];
				} else 
					$question = $this->pairs[$c][0];
			
			if(preg_match("/^image:(.*)$/",$question,$match)) {			
				if(file_exists(dirname(__FILE__)."/..".$match[1])) $image = $match[1];
				if(file_exists(dirname(__FILE__)."/../quizzes/".$this->quiz_id.$match[1])) $image = "/quizzes/".$this->quiz_id.$match[1];											
			}
					
			if(isset($image)) {
					//$image = alterImage($image);
			
					$this->question .= "<center><img class=\"img-fluid\" src=\"".$image."\"></img></center>";
			} else				
				$this->question .= "<br/><blockquote class='blockquote'>".$question."</blockquote>";
            if(!is_array($this->pairs[$c][1])) $this->answer = [ $this->pairs[$c][1] ];
            else $this->answer = $this->pairs[$c][1];

            foreach($this->pairs as $pair) {
                if( $this->pairs[$c][0] == $pair[0] ) {
                    if(!is_array($pair[1]))
                        $this->answer[] = $pair[1];
                    else
                        $this->answer = array_merge($this->answer,$pair[1]);
                }
            }

            $this->options = [];
            foreach($this->pairs as $option) {
                if(is_array($option[1])) $option[1] = $option[1][0];
                $this->options[] = $option[1];
            }
            $this->options = array_unique($this->options);
            natsort($this->options);
            unset($this->pairs);

        }
}

function alterImage($image) {
	$ext = mb_strtolower(explode(".",$image)[count( explode(".",$image))-1]);
	//var_dump($ext);
	if( $ext == "jpg" OR $ext == "jpeg") {
		$im = imagecreatefromjpeg(dirname(__FILE__)."/..".$image);
	}
	if(!isset($im)) return $image;
	
	$newImage = preg_replace_callback("/\/([a-z0-9]*?)(\.".$ext.")$/i",
		function($matches) { 						
			return "/modified/".md5($matches[1]).$matches[2];
		},$image);
	
		
	if(!imagefilter($im, IMG_FILTER_GRAYSCALE)) echo "IMG_FILTER_GRAYSCALE error";
	if(!imagefilter($im, IMG_FILTER_CONTRAST, 11)) echo "IMG_FILTER_CONTRAST error";	
	if(!imagefilter($im, IMG_FILTER_SELECTIVE_BLUR)) echo "IMG_FILTER_SELECTIVE_BLUR error";	
	if(!imagefilter($im, IMG_FILTER_SCATTER, 1, 4)) echo "IMG_FILTER_SCATTER error";
	if(!imageflip($im, IMG_FLIP_HORIZONTAL)) echo "imageflip error";
	
	
	
	imagejpeg($im, dirname(__FILE__)."/..".$newImage );		
	imagedestroy($im);
	return $newImage;
	


}