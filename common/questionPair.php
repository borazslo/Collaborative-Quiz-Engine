<?php

class questionPair extends Question {
    
        public $inputType = 'select';
    
        function prepareQuestion() {
            global $user;

            if(isset($this->pairs) AND !is_array($this->pairs)) $this->pairs = ($this->pairs)();
                                    
            $c = $this->pseudoRandom(0, count($this->pairs) - 1 , $this->setUnique() );

            $this->question .= "<br/><blockquote class='blockquote'>".array_keys($this->pairs)[$c]."</blockquote>";
            $this->answer = $this->pairs[array_keys($this->pairs)[$c]];
            
            $this->options = [];
            foreach($this->pairs as $option) {
                $this->options[] = $option;
            }
            $this->options = array_unique($this->options);
            sort($this->options);
            unset($this->pairs);

        }
}