<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Quiz
 *
 * @author webdev
 */
class Quiz {
    
    function __construct($jsonFile) {
        
        
        if(!file_exists($jsonFile)) throw new Exception("Config file '". $jsonFile."' does not exists.");
        if(! $settings = json_decode(file_get_contents($jsonFile)) ) throw new Exception("Config file '". $jsonFile."' is not valid Json.");
        foreach($settings as $key => $val) {
            if($key != 'questions') 
                $this->$key = $val;
        }
                
        if(!isset($this->id))
            $this->id = $jsonFile;
        
        if(isset($settings->questions)) {
            foreach($settings->questions as $key => $question) {
                $question->id = $key + 1;
                $question->quiz_id = $this->id;
                $className = "question".ucfirst($question->type);
                //if(!class_exists($className)) $className = 'Question';
                $this->questions[] = new $className($question);
            }
        }
        
        
    }
}
