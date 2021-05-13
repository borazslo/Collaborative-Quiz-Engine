<?php

class questionCompletion extends Question {
    
        function prepareQuestion() {
            global $user;

            if(!is_array($this->text)) $this->text = [$this->text];
            
            $c = $this->pseudoRandom(0, count($this->text) - 1 , $this->setUnique() );                        
            $words = explode(' ',$this->text[$c]);
            
            $k = $this->pseudoRandom(0,count($words)-1, $user->id );            
            
            if(isset($this->hardcore)) { 
                foreach($words as $key => $word) {
                    preg_match('/\p{L}+/ui',$word,$match);
                    
                    if($k == $key) {
                        $char = '?';
                        $this->answer = $match[0];
                    } else $char = '.';
                    
                    $words[$key] = str_replace($match[0], str_repeat($char, mb_strlen($match[0])), $word);
                }            
            } else {
                $words[$k] = str_replace($match[0], '_____', $words[$k]);
            }
            $this->question .= "<br/><blockquote class='blockquote'>".implode(" ",$words)."</blockquote>";                                               
        }
}
