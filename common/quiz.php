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
    
    public $folder = 'quizzes/';
    
    function __construct($jsonFile) {
        
        $file = $this->folder.$jsonFile;
        
        if(!file_exists($file)) throw new Exception("Config file '". $file."' does not exists.");
        if(! $settings = json_decode(file_get_contents($file)) ) throw new Exception("Config file '". $file."' is not valid Json.");
        foreach($settings as $key => $val) {
            if($key != 'questions') 
                $this->$key = $val;
        }
                
        $this->id = preg_replace('/\.([a-zA-Z0-9]*?)$/i','',$jsonFile);
        
        if(isset($settings->questions)) {
            foreach($settings->questions as $key => $question) {
                $question->id = $key + 1;
                $question->quiz_id = $this->id;
                
                if(isset($question->folder)) {                    
                    $question->folder = $this->folder.$question->folder;
                }
                
                $className = "question".ucfirst($question->type);
                //if(!class_exists($className)) $className = 'Question';
                $this->questions[] = new $className($question);
            }
        }
        
        
    }
}
