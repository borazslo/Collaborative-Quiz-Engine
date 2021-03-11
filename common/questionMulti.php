<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of questionNumber
 *
 * @author webdev
 */
class questionMulti extends Question {
    
    /**
     * 
     * @global type $user
     * @throws Exception
     */
    function prepareQuestion() {
        global $user;
        
        if(!isset($this->questions) OR !is_array($this->questions) ) throw new Exception('Question '.$this->id.' is a "multi" type, but there are no "questions."');
        if(!isset($this->answers) OR !is_array($this->answers)) throw new Exception('Question '.$this->id.' is a "multi" type, but there are no "answers."');
        if(count($this->questions) != count($this->answers))  throw new Exception('Question '.$this->id.' is a "multi" type, but answers != questions');
                   
        #$variation = round(1 + (hexdec(md5($user['tanazonosito'])) / hexdec("FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFF")) * (count($questions) - 1));            
        $variation = bindec(md5($user->id)) % count($this->questions);
                       
        $this->questionId = $variation;
        $this->question = $this->questions[$variation];
        $this->answer = $this->answers[$variation];            
    
    }
}

