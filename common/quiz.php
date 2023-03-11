<?php

use JsonSchema\Validator;
use JsonSchema\Constraints\Constraint;

class Quiz {
    
    public $folder = 'quizzes/';
    
    function __construct($id) {
        
        $this->id = $id; 
        $this->loadAndValidateFile();
        
    }    
    
	function prepareQuestions() {
		$this->loadQuestions();
		$this->loadQuestionsStartEnd();
		$this->deleteInactiveQuestions();
	}
	
    function loadAndValidateFile() {
        
        // Get Json data file
        $configFile = $this->folder.$this->id."/".$this->id.".json";                
        if(!file_exists($configFile)) throw new Exception("Config file '". $configFile."' does not exists.");                        
        if(! $configData = json_decode(file_get_contents($configFile)) ) throw new Exception("Config file '". $configFile."' is not valid Json.");
		
		$questionsFile = $this->folder.$this->id."/questions.json";                
        if(!file_exists($questionsFile)) throw new Exception("Questions' file '". $questionsFile."' does not exists.");                        
        if(! $questionsData = json_decode(file_get_contents($questionsFile)) ) throw new Exception("Questions' file '". $questionsFile."' is not valid Json.");
		
		$data = $configData;
		$data->questions = $questionsData;
				
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
            // if($key != 'questions') 
                $this->$key = $val;
        }
                
        // Process questions one by one         
        if(isset($data->questions)) {
            foreach($data->questions as $key => $question) {
                $question->id = $key + 1;
                $question->quiz_id = $this->id;
                                
                //Cheking folders
                if(isset($question->folder)) {
					/* TODO: Jobb lenne ha működne, de itt még nincs $users az index.php okán. */
					$question->folder = preg_replace('/\[(user|group|group2|group3)\]/', "", $question->folder);
					/*
                    $question->folder = preg_replace_callback('/\[(user|group|group2|group3)\]/', function($matches) {
                        if($matches[1] == 'user ') $matches[1] = 'id';
                        global $user;
                        return $user->{$matches[1]};
                        
                    } , $question->folder);
                    */                                        
                    $question->folder = $this->folder.$question->quiz_id."/".$question->folder;
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
            }                        
        }
		
		if(isset($data->addons)) {		
			foreach($data->addons as $addon)
				include_once('addons/'.strtolower($addon)."/".$addon.".php");
		}

    }
    
	function loadQuestions() {
			$questions = $this->questions;
			$this->questions = [];
			
            foreach($questions as $key => $question) {
                $question->id = $key + 1;
                $question->quiz_id = $this->id;
                                
                //Load question
                $className = "question".ucfirst($question->type);
                $this->questions[] = new $className($question);                
            }                        
				
	}
	
    function loadQuestionsStartEnd() {
        $start = isset($this->timing->start) ? $this->timing->start : "today midnight";
        if(preg_match('/^date\(.*?\)$/i',$start)) eval('$start = '.$this->timing->start.';');        
        $start = strtotime($start);
        
        $frequency = isset($this->timing->frequency) ? ( strtotime($this->timing->frequency) - time() ) : 0 ; 
        $duration = isset($this->timing->duration) ? ( strtotime($this->timing->duration) - time() ) : 31556952 ;
                     
        if(isset($this->questions))
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
            if(isset($question->wait)) {
                $last_start = strtotime($question->wait, $last_start - $frequency);
            } 
        }                
    }
    
    /**
     * Delete inactive Questions based on startTime - endTime
     */
    function deleteInactiveQuestions() {
        global $user; 

        $now = time();
        $this->thereIsNoQuestion = true;
        if(isset($this->questions))
        foreach($this->questions as $key => $question) { 
            if ( $question->startTime > $now OR $question->endTime < $now ) {
                $this->questions[$key]->active = false;
                if(!isset($this->nextQuestionTime) AND $now - $question->startTime < 0) { $this->nextQuestionTime = $question->startTime; }
            } else {
                $this->questions[$key]->active = true;
                $this->thereIsNoQuestion = false;
            }
        }
    }    
}
