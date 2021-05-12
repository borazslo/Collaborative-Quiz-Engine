<?php

class questionAllOrNone extends Question {
    
    public $inputType = 'text';

    function __construct($settings) {        
        
        if(isset($settings->options)) $this->inputType = 'select';        
        
        parent::__construct($settings);
                
    }    
    /**
     * 
     * @param type $user_answer
     * @return int -1 = wrong, 0 = null, 1 = manual validation needed, 2 = ok
     */
    function getUserResult($user_answer) {
        global $user;
        
        if($user_answer == '' ) return 0;
                
        $answers = $this->getDifferentAnswers($user->group);
        if($answers == array()) return 1;
                
        $bestAnswer = array_key_first($answers); // The value of the value which choosen the most time
        
        if($user_answer != $bestAnswer AND 4 == 5) {
            $this->hint .= " - A többieket is megkérdezném erről.";
            return -1;
        } else {
            $numAnswers = array_sum($answers); // Number of all the answers                       
            $numBestAnswers = $answers[$bestAnswer]; // Number of the best answers;        
            global $config;
            if( isset($config['scoring']['allOrNoneTolerance']) ) $tolerance = $config['scoring']['allOrNoneTolerance']; else $tolerance = 10;                        
            $goodNumberShouldBe =  $numAnswers - ( $numAnswers  / 100 * $tolerance  );
            
            if( $numBestAnswers < $goodNumberShouldBe ) {
                $this->hint .= " - A többségből legyen még egység.";
                return 1;                  
            } else {
                return 2;
            }
            
        }
                
    }
    
    function createUserAnswer($result) {                    
        return rand(100,200);                
    }
    
}

