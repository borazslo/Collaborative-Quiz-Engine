<?php

class questionEquation extends Question {
    
	function prepareQuestion() {
        
		if(!isset($this->variables) ) throw new Exception('Question '.$this->id.' is a "equation" type, but it has no "variables."');
		foreach($this->variables as $key => $var ) {
			$k = $this->pseudoRandom(0, count($var) - 1 , $this->setUnique() ); //Vigyázz! Ha ezt módsítod akkor a getUserResult-ba is módosítani kell!
			if(is_array($var[$k])) {
				for($i=0;$i<count($var[$k]);$i++) {
					$this->question = str_replace(':'.$key.$i,$var[$k][$i],$this->question);
					if(isset($this->hint)) $this->hint = str_replace(':'.$key.$i,$var[$k][$i],$this->hint);
				
				}
			
				$var[$k] = $var[$k][0]; 
			}
			$var = $var[$k];        
			
			$this->question = str_replace(':'.$key,$var,$this->question);
			if(isset($this->hint)) $this->hint = str_replace(':'.$key,$var,$this->hint);
						
		}
		
    }
	
	function getUserResult($user_answer) {
		
		foreach($this->variables as $key => $var ) {
			$varkey = $this->pseudoRandom(0, count($var) - 1 , $this->setUnique() ); //Vigyázz! Ha ezt módsítod akkor a prepareQuestion-ben is módosítani kell!
			if(is_array($var[$varkey])) $var[$varkey] = $var[$varkey][1]; 
			$this->vars[$key] = $var[$varkey];        
			//$$key = $var[$varkey];        
											
		}    
		return parent::getUserResult($user_answer);    

	}
    
}


	function percentageCalculation($question) {
			foreach($question->vars as $key => $var) $$key = $var;
			
			$nepesseg = 9937628;			
			return  round ( ( $y * 1000 ) * ( $x / $nepesseg ) , -2);
			
			
	}



