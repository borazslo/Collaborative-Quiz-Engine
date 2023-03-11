<?php

class questionEncryption extends Question {

	function prepareQuestion() {

		if(isset($this->encryptedandhint) AND !is_array($this->encryptedandhint)) $this->encryptedandhint = ($this->encryptedandhint)();
        if($this->encryptedandhint === array()) return;

		$c = $this->pseudoRandom(0, count($this->encryptedandhint) - 1 , $this->setUnique() );
		$shift = $this->pseudoRandom(5, 15 , $this->setUnique() );

		$this->question .= "<br/><blockquote class='blockquote'>".$this->shifttext($this->encryptedandhint[$c][0],$shift)."</blockquote>";
		$this->answer = [ $this->encryptedandhint[$c][0], mb_strtolower($this->encryptedandhint[$c][0])];
		if(!isset($this->hint)) $this->hint = '';
		$this->hint .= $this->shifttext($this->encryptedandhint[$c][1],$shift);
	}


	function shifttext($text, $number) {
		
		//Hide numbers
		$text = preg_replace('/[0-9]/i','0',$text);

		$text = $this->str_split_unicode($text);
		
		$dictionaryLowecase = $this->str_split_unicode('_aábcdeéfghiíjklmnoóöőpqrstuúüűwxyz');
		$dictionaryUppercase = $this->str_split_unicode('_AÁBCDEÉFGHIÍJKLMNOÓÖŐPQRSTUÚÜŰWXYZ');

		
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



	function str_split_unicode($str, $l = 0) {
    if ($l > 0) {
        $ret = array();
        $len = mb_strlen($str, "UTF-8");
        for ($i = 0; $i < $len; $i += $l) {
            $ret[] = mb_substr($str, $i, $l, "UTF-8");
        }
        return $ret;
    }
    return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
}
}