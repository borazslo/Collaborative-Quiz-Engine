<?php

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class Quiz {
    
    public $folder = 'quizzes/';
    
    function __construct($jsonFile) {
        
        $this->id = preg_replace('/\.([a-zA-Z0-9]*?)$/i','',$jsonFile);
        
        $this->loadAndValidateFile($jsonFile);
        
        $this->loadQuestionsStartEnd();
        $this->deleteInactiveQuestions();
        
    }    
    
    function loadAndValidateFile($jsonFile) {
        
        // Get Json data file
        $file = $this->folder.$jsonFile;                
        if(!file_exists($file)) throw new Exception("Config file '". $file."' does not exists.");                        
        if(! $data = json_decode(file_get_contents($file)) ) throw new Exception("Config file '". $file."' is not valid Json.");

        // Validate Json data settings against schema
        $validator = new JsonSchema\Validator;
        $validator->validate($data, (object)['$ref' => 'file://'.dirname(__FILE__)."/../quizSchema.json"], Constraint::CHECK_MODE_APPLY_DEFAULTS); //file://' . realpath('schema.json')]);

        if (!$validator->isValid()) {
            $errortext = "JSON does not validate. Violations:<br>\n";
            foreach ($validator->getErrors() as $error) {
                $errortext .= sprintf("[%s] %s<br>\n", $error['property'], $error['message']);
            }
            throw new Exception($errortext);
        }
        
        // Load from Json data setting to $this
        foreach($data as $key => $val) {
            if($key != 'questions') 
                $this->$key = $val;
        }
                
        // Process questions one by one         
        if(isset($data->questions)) {
            foreach($data->questions as $key => $question) {
                $question->id = $key + 1;
                $question->quiz_id = $this->id;
                                
                //Cheking folders
                if(isset($question->folder)) {                                        
                    $question->folder = $this->folder.$question->folder;
                    if(!is_dir($question->folder)) throw new Exception('There is no folder called '.$question->folder);
                }
                if(in_array($question->type,['multi','puzzle'])) {
                    $tmpFolderPath = sys_get_temp_dir ( ) . "/" . $this->id ;                    
                    if(!is_dir($tmpFolderPath)) {
                        mkdir($tmpFolderPath);
                        if(!is_dir($tmpFolderPath)) {
                            throw new Exception('There is a need for folder called _'.$tmpFolderPath.'_');
                        }
                    }
                }
                
                //Load question
                $className = "question".ucfirst($question->type);
                $this->questions[] = new $className($question);
            }                        
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
