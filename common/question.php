<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of question
 *
 * @author webdev
 */
class Question {
    
    public $inputType = 'text';
    
    function __construct($settings) {
        
		$this->settings = $settings;
		if(isset($settings->variationsByLevel)) {
			global $user;
			foreach($settings->variationsByLevel as $key => $setting) {
				if($key + 1 == ( isset($user->level) ? $user->level : 1) ) {	
					$settings = recursiveupdate($settings,$setting);
				}			
			}
		}
		
        foreach($settings as $key => $val) {
            $this->$key = $val;
        }
		
		if(isset($this->question) AND is_array($this->question))
			$this->question = $this->question[$this->pseudoRandom(0, count($this->question) - 1, $this->setUnique())];
		
		
        if(isset($settings->options) AND !is_array($settings->options) AND function_exists($settings->options))
            $this->options = ($settings->options)();
        
        $this->prepareQuestion();
        $this->prepareInput();
        
        $this->prepareHint();
        if(isset($this->video))
            $this->prepareVideo();
        $this->prepareImage();

        global $user;
        if(isset($user->id)) { 
            
            $this->params = [
                'quiz_id' => $this->quiz_id,
                'user_id' => $user->id,
                'question_id' => $this->id
            ];

            $this->loadOtherAnswers($user->group);
            $this->loadUserAnswer();
        }
        
    }
    
    function prepareHint() {
        if(!isset($this->hint)) return;
        
        if(!is_array($this->hint)) {   
            $this->hint = $this->hintUrlToHtml($this->hint);
            return;
        }
        
        //Ha többszintű tipp van, akkor csak azt mutatjuk meg neki, ami neki kell.
        global $user;        
        if(isset($user->level) and isset($this->hint[($user->level - 1) ])) {
            $this->hint = $this->hintUrlToHtml($this->hint[($user->level - 1) ]);
        } else {
            unset($this->hint);
        }
    }
    
    function hintUrlToHtml($url) {
        
        if(preg_match('/\/maps\//i', $url)) {            
            return 'Talán errefelé érdemes körülnézni: <a class="text-decoration-none" target="_blank" href="'.$url.'">Google Street View</a>.';                                            
            
        } elseif (preg_match('/youtube/i', $url )) {                                
            return  'Itt egy videó, ami segíthet: <a class="text-decoration-none" target="_blank" href="'.$url.'">YouTube</a>.';           
                                                
        } elseif (preg_match('/^http/i', $url )) {
            return 'Itt egy link, ami segíthet: <a class="text-decoration-none" target="_blank"  href="'.$url.'">KATTINTS</a>!';
                    
        } else {
               return $url; 
        }             
                
    }
    
    function prepareVideo() {

        if(preg_match('/youtube/i', $this->video)) $src = preg_replace('/watch\?v=/','embed/',$this->video);
        else $src = $this->video;
        
        $this->video_embed = ''
                . '<div class="embed-responsive embed-responsive-16by9">'
                . '<iframe class="embed-responsive-item" src="'.$src.'" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                . '</div>';
    }
    
    function prepareQuestion() {
        
    }
    
    function prepareInput() {
        
    }
    
    function prepareImage() {
        if(isset($this->image)) {

            $quiz_folder = "quizzes/".$this->quiz_id."/";
            
            if(is_dir($quiz_folder.$this->image)) {
                $extensions = "JPG|jpeg|jpg|png";
                $files = array_values(preg_grep('~\.('.$extensions.')$~', scandir($quiz_folder.$this->image)));

                global $user;
                $this->image = $quiz_folder.$this->image."/".$files[$this->pseudoRandom(0, count($files) - 1, isset($user->id) ? $user->id : 0 )]  ;
                return true;
            } 

            else if(file_exists($this->image)) { 
                return true; 
            }   
            
            else if(file_exists($quiz_folder.$this->image)) {

                $this->image = $quiz_folder.$this->image;
                return true;
            }



        }
        
    }

    function loadUserAnswer() {
        global $connection, $config;
        
        $old_answer = $this->getOldAnswer();
        $new_answer = $this->getNewAnswer();
        
        if(!$new_answer) $new_answer = $old_answer;
        
        
        $result = $this->getUserResult($new_answer);
		if(isset($this->uniqueAnswerRequired) AND in_array($result,[1,2]) AND !$this->validateUniqueAnswers($new_answer)) {
			$result = -1;
		}
        
		if(isset($config['debug']) AND $config['debug'] > 0 AND isset($this->answer)) {
			if(!is_array($this->answer)) $this->answer = [$this->answer];
			if(!isset($this->forAdmin)) $this->forAdmin = '';
			else $this->forAdmin .= "<br/>";
			$this->answer = array_unique($this->answer);
			natsort($this->answer);
			$this->forAdmin .= t('Correct answer').": \"".implode("\",\"",$this->answer)."\"";
		}
		
        if($new_answer != false and $new_answer != $old_answer) {
            if($old_answer != false ) {
                $stmt = $connection->prepare("UPDATE answers SET "
                . "answer = :answer, result = :result, timestamp = CURRENT_TIMESTAMP() "
                . " WHERE quiz_id = :quiz_id AND question_id = :question_id AND user_id = :user_id ");
            } else {
                $stmt = $connection->prepare("INSERT INTO answers (quiz_id, question_id, user_id, answer, result)"
                        . "VALUES (:quiz_id, :question_id, :user_id, :answer, :result)");
                
            }            
            $stmt->execute(array_merge($this->params, ['answer' => $new_answer, 'result' => $result]));
        }
        
        $this->user_answer = $new_answer;
        $this->user_result = $result;
                    
    }
    
    function getOldAnswer() {
        global $connection;
                   
        $stmt = $connection->prepare("SELECT * FROM answers WHERE quiz_id = :quiz_id AND user_id = :user_id AND question_id = :question_id  LIMIT 1");
        $stmt->execute($this->params); 
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->old_answer = $result;
        $old_answer = isset($result[0]) ? $result[0]['answer'] : false;
        
        return $old_answer;        
    }
    
    function getNewAnswer() {
        if(isset($_REQUEST['questions']) AND isset($_REQUEST['questions'][$this->id])) {
                return $_REQUEST['questions'][$this->id];            
        } else {
            return false;
        }
    }
    
    /**
     * 
     * @param type $user_answer
     * @return int -1 = wrong, 0 = null, 1 = manual validation needed, 2 = ok
     */
    function getUserResult($user_answer) {
        if($user_answer == '') return 0;
        
		if(!is_array($this->answer) AND preg_match("/^callback:(.*)$/i",$this->answer,$match)) {
			if(function_exists($match[1]) ) 
				$this->answer = call_user_func($match[1], $this);
			else
				throw new Exception('Question '.$this->id.' is asking for "'.$match[1].'", but function does not exists.');
		}
		
        if(!is_array($this->answer)) $this->answer = [$this->answer];
        
        foreach($this->answer as $good_answer) {
            if( strcasecmp(trim($user_answer),trim($good_answer)) == 0 ) return 2;    
        }
        
        return -1;
    }
    
    function createUserAnswer($result) {
      
        if($result == '2') {
            
            if(!is_array($this->answer)) $this->answer = [$this->answer];            
            return $this->answer[array_rand($this->answer)];
            
        } elseif( $result == '-1') {
            return readable_random_string(rand(4,6));
        }
        
    }
    
    function loadOtherAnswers($groupName = false) {
        global $connection;
    
        $sql = "SELECT  
                    SUM(if(result = '2', 1, 0)) as 'right', 
                    SUM(if(result = '-1', 1, 0)) as 'wrong'
                FROM answers ";
        
        if($groupName) {
            $sql .= "LEFT JOIN `users`
                    ON users.id = answers.user_id
                LEFT JOIN `groups`
                    ON groups.id = users.group_id
                WHERE  
		groups.name = :group_name AND ";
            $params = array_merge($this->params,['group_name' => $groupName]); 
        }                
        else {
            $sql .= " WHERE ";
            $params = $this->params;
        }
        
        $sql .= "   
                    quiz_id = :quiz_id AND
                    question_id = :question_id AND
                    user_id != :user_id
                GROUP BY 
                    question_id ";
                               
        $stmt = $connection->prepare($sql);
        if(!$stmt->execute($params)) printr($connection->errorInfo());
        $this->others = $stmt->fetch(PDO::FETCH_ASSOC);  
    }
    
    function getDifferentAnswers($groupName = false) {
        global $connection;
          
        $sql = "SELECT  answer, COUNT(answer) as quantity
                FROM answers ";
        
        
        if($groupName) {
            $sql .= "LEFT JOIN users
                    ON users.id = answers.user_id
                LEFT JOIN `groups`
                    ON groups.id = users.group_id
                WHERE  
		groups.name = :group_name AND ";
            $params = array_merge($this->params,[':group_name' => $groupName]); 
        }
                
        else {
            $sql .= " WHERE ";
            $params = $this->params;
        }
        
        $sql .= "   quiz_id = :quiz_id AND
                    question_id = :question_id AND
                    ( user_id != :user_id OR user_id = :user_id ) AND
                    result IN ('-1','1','2') 
                GROUP BY answer 
                ORDER BY quantity DESC 
            ";
                       
        $stmt = $connection->prepare($sql);
        if(!$stmt->execute($params)) printr($connection->errorInfo());       
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);        

        $return = [];
        foreach($results as $result) {
            $return[$result['answer']] = $result['quantity'];
        }
        return $return;
    }
	
	function validateUniqueAnswers($answer) {
		global $user;
		$answers = $this->getDifferentAnswers($user->group);
		if(!array_key_exists($answer,$answers) OR $answers[$answer] == 1) 
			return true;
		else
			return false;		
	}
    
    function pseudoRandom($from, $to, $unique) {		
		global $config, $user, $page;
				

		if($user->admin AND isset($_SESSION['random']) AND $_SESSION['random'] == true ) {
			$page->data['random'] = true;
			srand(floor(time()));
			return rand($from, $to);
		} 
		//echo $unique."<br>";
		//exit;
		if(!is_numeric($unique)) $unique = crc32($unique);
        srand($unique * $this->id); // mindig ugyanazt fogja adni erre a unique-ra
        return rand($from, $to);                  
    }
    
    function setUnique() {
        global $user;
        if(!isset($this->unique)) $this->unique = 'user';
        if(is_array($this->unique)) {
            if(isset($this->unique[($user->level - 1)])) $this->unique = $this->unique[$user->level - 1];
            else $this->unique = $this->unique[count($this->unique) -1 ];
        }             
        if($this->unique == 'user') $this->unique = 'id';
        elseif($this->unique == 'group') $this->unique = 'group_id';
        
		if(isset($user->{$this->unique}))
			$this->unique = $user->{$this->unique};
		        		
        return $this->unique;
    }
    
}
