<?php

class questionPair extends Question {
    
        public $inputType = 'select';
    
        function prepareQuestion() {
            global $user;

            if(isset($this->pairs) AND !is_array($this->pairs)) $this->pairs = ($this->pairs)();
                                    
            $c = $this->pseudoRandom(0, count($this->pairs) - 1 , $this->setUnique() );

            $this->question .= "<br/><blockquote class='blockquote'>".$this->pairs[$c][0]."</blockquote>";
            $this->answer = [ $this->pairs[$c][1] ];
            foreach($this->pairs as $pair) {
                if( $this->pairs[$c][0] == $pair[0] )
                    $this->answer[] = $pair[1];
            }

            $this->options = [];
            foreach($this->pairs as $option) {
                $this->options[] = $option[1];
            }
            $this->options = array_unique($this->options);
            sort($this->options);
            unset($this->pairs);

        }
}