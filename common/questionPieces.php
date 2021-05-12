<?php

class questionPieces extends Question {
    
        function prepareQuestion() {
            global $user;

            $c = $this->pseudoRandom(0,count($this->pieces) -1, $this->setUnique() );
            
                                    
            $this->question .= "<br/><blockquote class='blockquote'>".$this->pieces[$c]."</blockquote>";                                               

            }
}
