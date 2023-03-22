<?php

class questionEncryption extends Question {

	function prepareQuestion() {

		if(isset($this->encryptedandhint) AND !is_array($this->encryptedandhint)) $this->encryptedandhint = ($this->encryptedandhint)();
        if($this->encryptedandhint === array()) return;

		$c = $this->pseudoRandom(0, count($this->encryptedandhint) - 1 , $this->setUnique() );
		$this->shift = $this->pseudoRandom(5, 15 , $this->setUnique() );

		$this->question .= "<br/><blockquote class='blockquote'>".$this->shifttext($this->encryptedandhint[$c][0],$this->shift)."</blockquote>";
		$this->answer = [ $this->encryptedandhint[$c][0], mb_strtolower($this->encryptedandhint[$c][0])];
		if(!isset($this->hinttext)) $this->hinttext = '';
		$this->hinttext .= $this->shifttext($this->encryptedandhint[$c][1],$this->shift);
	}


	function prepareHint() {
		
		if(isset($this->hint) AND $this->hint === true) {
			$this->hint = $this->hinttext."<br/><br/>Minden betű ".$this->shift." karakterrel van arrébb tolva. (Kettős betűket nem veszünk figyelembe.)" ; 
		} elseif(isset($this->hint) AND $this->hint != "") {
			$this->hint = $this->hinttext."<br/><br/>".$this->hint ; 
		} else
			$this->hint = $this->hinttext;
		
		unset($this->hinttext);
	}
	
	function shifttext($text, $number) {
		
		//Hide numbers
		$text = preg_replace('/[0-9]/i','0',$text);

		$text = str_split_unicode($text);
		
		$dictionaryLowecase = str_split_unicode('_aábcdeéfghiíjklmnoóöőpqrstuúüűvwxyz');
		$dictionaryUppercase = str_split_unicode('_AÁBCDEÉFGHIÍJKLMNOÓÖŐPQRSTUÚÜŰVWXYZ');

		
		foreach($text as $i => $char) {
			  $position = array_search($char, $dictionaryLowecase,true);
			  if($position == false) $text[$i] = $text[$i];
			  else {
				  $position = $position + $number;
				  if ($position >= count($dictionaryLowecase) ) $position = $position - count($dictionaryLowecase) + 1;
				  $text[$i] = $dictionaryLowecase[$position];
			  }
		}

		foreach($text as $i => $char) {
			  $position = array_search($char, $dictionaryUppercase,true);
			  if($position == false) $text[$i] = $text[$i];
			  else {
				  $position = $position + $number;
				  if ($position >= count($dictionaryUppercase) ) $position = $position - count($dictionaryUppercase) + 1;
				  $text[$i] = $dictionaryUppercase[$position];
			  }
		}

		return implode("",$text);
	}




}