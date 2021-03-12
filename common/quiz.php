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
                                
                //Cheking folders
                if(isset($question->folder)) {                                        
                    $question->folder = $this->folder.$question->folder;
                    if(!is_dir($question->folder)) throw new Exception('There is no folder called '.$question->folder);
                }
                if(in_array($question->type,['multi','puzzle']) AND !is_dir(TMP_FOLDER.$this->id)) throw new Exception('There is a need for folder called '.TMP_FOLDER.$this->id);
                
                //Load question
                $className = "question".ucfirst($question->type);
                $this->questions[] = new $className($question);
            }
            $this->loadQuestionsStartEnd();
            $this->deleteInactiveQuestions();
            
        }
        
        
    }    
    
    function loadQuestionsStartEnd() {
        $start = strtotime(isset($this->timing->start) ? $this->timing->start : "today midnight" ); 
        $frequency = isset($this->timing->frequency) ? ( strtotime($this->timing->frequency) - time() ) : 0 ; 
        $duration = isset($this->timing->duration) ? ( strtotime($this->timing->duration) - time() ) : 31556952 ;
                
        foreach($this->questions as &$question) {
            if(!isset($last_start))
                $question->startTime = $start;          
            elseif(!isset($question->relativeStart))
                $question->startTime = $last_start + $frequency;
            else                
                $question->startTime = strtotime($question->relativeStart,$last_start);
            
            if(!isset($question->duration))
                $question->endTime = $question->startTime + $duration;
            else
                $question->endTime = strtotime($question->duration,$question->startTime);
            
            $last_start = $question->startTime;
        }                
    }
    
    /**
     * Delete inactive Questions based on startTime - endTime
     */
    function deleteInactiveQuestions() {
        global $user; 

        $now = time();
        foreach($this->questions as $key => $question) {            
            if( ( $question->startTime < $now OR $question->endTime > $now ) AND !isset($user->isAdmin)) {
                unset($this->questions[$key]);            
            }
        }
    }
}
