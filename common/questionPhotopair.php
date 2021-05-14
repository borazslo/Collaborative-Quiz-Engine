<?php

class questionPhotopair extends questionPair {
    
        public $inputType = 'select';
    
        function prepareQuestion() {
            global $user;

            $extensions = "JPG|jpeg|jpg|png";

            if(!isset($this->folder)) throw new Exception("Question ".$this->id.": 'photopair' type question needs a folder.");
            if(!is_dir($this->folder)) throw new Exception("Question ".$this->id.": 'photopair' type question needs a valid folder.");

            $files = array_values(preg_grep('~\.('.$extensions.')$~', scandir($this->folder)));

            // Collect possible answers
            $this->pairs = [];                           
            foreach($files as $k => $file) {
                preg_match('/^(.*?)\.('.$extensions.')$/i',$file,$match);
                if(!preg_match('/^puzzle_/i',$file)) {
                    $this->pairs[] = [
                            "<img class=\"mx-auto img-thumbnail d-block\" src=\"".$this->folder.'/'.$file."\" >", $match[1]                
                    ];
                } else {
                    unset($files[$k]);
                }
            }
            
      
            parent::prepareQuestion();
            

        }
}