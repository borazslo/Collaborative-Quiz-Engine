<?php

class questionCompletion extends Question {
    
        function prepareQuestion() {
            global $user;

            if(!is_array($this->text)) $this->text = [$this->text];
            
            $c = $this->pseudoRandom(0, count($this->text) - 1 , $this->setUnique() );                        
			$text = $this->text[$c];
			            
			$words = explode(' ',str_replace("\n"," ",$text));
			foreach($words as $key => $word) {
				if(count(str_split_unicode($word)) < 4) {
					unset($words[$key]);
				}
			}
			$words = array_values($words);
			
            $k = $this->pseudoRandom(0,count($words)-1, isset($user->group_id) ? $user->group_id : 0 );			
			$this->answer = $hidden = $words[$k];
			$blank = '';
			for($i=0;$i<count(str_split_unicode($hidden));$i++) { $blank .= '?'; }
			
            $text = str_replace($hidden, $blank, $text);			
						
			if(isset($this->hardcore) AND $this->hardcore) {
				$lines = explode("\n",$text);
				foreach($lines as &$line) {
					$chars = str_split_unicode($line);
					foreach($chars as &$char) {
						if(in_array($char,str_split_unicode("aábcdeëéfghiíjklmnoóöőpqrstuúüűvwxyzAÁBCDEÉËFGHIÍJKLMNOÓÖŐPQRSTUÚÜŰVWXYZ")))$char = ".";
					}
					$line = implode($chars);
				}
				$text = implode("\n",$lines);
						
            } 
			$text = str_replace("\n","<br/>",$text);
			
			$fileName = md5($text); 
			$folder = "/".(isset($this->folder) ? $this->folder : "temp" )."/";
			if(!file_exists(dirname(__FILE__)."/..".$folder."/".$fileName.".png")) {
				$img = new TextToImage;
				$img->createImage($text);
				//$img->addBorder(1,8);
				$img->saveAsPng($fileName, dirname(__FILE__)."/..".$folder);				
				//$img->showImage();
			}
		
			if(file_exists(dirname(__FILE__)."/..".$folder.$fileName.".png")) {
				$this->question .= "<center><img src='".$folder.$fileName.".png'></img></center>";
			} else {			
				$this->question .= "<br/><blockquote class='blockquote'>".$text."</blockquote>";
			};                                               
        }
		
	function getUserResult($user_answer) {
        if($user_answer == '') return 0;
        
		$good_answer = $this->answer;
        
		if( strcasecmp(trim($user_answer),trim($good_answer)) == 0 ) return 2;    
		
		$from = str_split_unicode("áëéíoóöőúüűÁÉËÍÓÖŐÚÜŰ");
		$to   = str_split_unicode("aeeíoooouuuAEEIOOOUUU");
		
		$good_answer = str_split_unicode($good_answer);
		foreach($good_answer as &$char) {
			if($key = array_search($char,$from)) {
				$char = $to[$key];
			}
		}
		$good_answer = implode($good_answer);
		$user_answer = str_split_unicode($user_answer);
		foreach($user_answer as &$char) {
			if($key = array_search($char,$from)) {
				$char = $to[$key];
			}
		}
		$user_answer = implode($user_answer);
		if( strcasecmp(trim($user_answer),trim($good_answer)) == 0 ) return 2;    
		
		$good_answer = trim($good_answer,",!-");
		$user_answer = trim($user_answer,",!-");
		if( strcasecmp(trim($user_answer),trim($good_answer)) == 0 ) return 2;    
		
		        
        return -1;
    }
}
