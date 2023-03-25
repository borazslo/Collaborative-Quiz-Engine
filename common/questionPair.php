<?php

class questionPair extends Question {
    
        public $inputType = 'select';
    
        function prepareQuestion() {
            global $user;

            if(isset($this->pairs) AND !is_array($this->pairs)) $this->pairs = ($this->pairs)();
                                    
			if($this->pairs === array()) return;
			
			
            $c = $this->pseudoRandom(0, count($this->pairs) - 1 , $this->setUnique() );

			
			if(!isset($this->question)) $this->question = '';
            $this->question .= "<br/><blockquote class='blockquote'>".$this->pairs[$c][0]."</blockquote>";
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