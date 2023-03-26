<?php

class questionAbbreviation extends questionManual {
    
   
    function autoValidate($user_answer) {
		$user_answer = preg_replace("/[ ]{2,10}/"," ",trim($user_answer));
		
		$abbreviation = str_split_unicode($this->abbreviation);
		$words = explode(" ",$user_answer);
		if(count($words) != count($abbreviation)) return -1;
		
		$dictionaryLowecase = str_split_unicode('_aábcdeéfghiíjklmnoóöőpqrstuúüűvwxyz');
		$dictionaryUppercase = str_split_unicode('_AÁBCDEÉFGHIÍJKLMNOÓÖŐPQRSTUÚÜŰVWXYZ');
		
		for($i=0;$i<count($abbreviation);$i++) {
			$firstLetter = str_split_unicode($words[$i])[0];
			if(mb_strtolower($firstLetter) != mb_strtolower($abbreviation[$i]) ) {
				$pos = 	array_search($firstLetter, $dictionaryUppercase,true);
				if($pos) $firstLetter = $dictionaryLowecase[$pos];
				
				$pos = 	array_search($abbreviation[$i], $dictionaryUppercase,true);
				if($pos) $abbreviation[$i] = $dictionaryLowecase[$pos];
				
				if( $abbreviation[$i] != $firstLetter) {
					return -1;
				}				
			}
		}
	
		return parent::autoValidate($user_answer);               
    }
    
    
}



