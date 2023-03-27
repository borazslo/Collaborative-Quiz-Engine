<?php

class Admin {
    //put your code here
    
    static function stats() {
        global $page, $quiz;
        
        $page->templateFile = 'stats';
        $page->data['rankingTable'] = getRankingTable($quiz->id);
        
        $players = 0;
        foreach($page->data['rankingTable'] as $position) {
            $players += $position['members'];
        }
       $page->data['players'] = $players;
        
       global $connection;
       $stmt = $connection->prepare("SELECT answer FROM answers WHERE question_id = 40 AND result = '2'");
       $stmt->execute();
       $results = $stmt->fetchAll();
       $w = [];
       foreach($results as $result) {
               $w = array_merge(explode(',',$result[0]),$w);
       }
               
       
       $page->data['szavak'] = implode(' ',$w);
       
       
    }
    
    static function verification() {
        global $page, $quiz, $connection;
        
        $page->templateFile = 'verification';
        
        foreach($quiz->questions as $key => $question) {
            if(!in_array($question->type, ['photo','manual','abbreviation'])) {
                unset($quiz->questions[$key]);
                continue;
            } 
                        
            $sql = "SELECT 
                    CONCAT(:quiz_id, '-', :question_id, '-', users.id) as id, 
                    users.name as `user`, users.id as `user_id`, 
                    groups.name as `group`, groups.id as `group_id`, groups.level as `level`,
                    answers.answer, answers.result, answers.timestamp                    
                FROM answers 
                LEFT JOIN users ON users.id = answers.user_id 
                LEFT JOIN `groups` ON users.group_id = groups.id 
                WHERE 
                    quiz_id = :quiz_id AND
                    question_id = :question_id AND
                    result = '1' ";
            
            global $development;
            if(!$development) $sql .= " AND groups.name NOT LIKE '".Bulk::prefix()."%' AND users.name NOT LIKE '".Bulk::prefix()."%' AND answers.timestamp <> '".Bulk::date()."%'  ";
            
            $sql .= " ORDER BY RAND() LIMIT 100"; 
                
                   

            $stmt = $connection->prepare($sql);
            if(!$stmt->execute(['quiz_id'=>$quiz->id, ':question_id' => $question->id ])) printr($connection->errorInfo());
            $quiz->questions[$key]->answersToCheck = $stmt->fetchAll(PDO::FETCH_ASSOC);
			global $user;
			$savedUser = $user;
			if(is_array($quiz->questions[$key]->settings->question)) {
				$quiz->questions[$key]->question = "<i>".t("Multiple questions")."</i>";
				foreach($quiz->questions[$key]->answersToCheck as &$answerToCheck) {
					$tmpQuestion = $quiz->questions[$key];
					$user = [
						'name' => $answerToCheck['user'],
						'id' => $answerToCheck['user_id'],
						"level" => $answerToCheck['level'],
						"group" => $answerToCheck['group'],
						"group_id" => $answerToCheck['group_id'],
						"group2" => "",
						"admin" => false
					];
					$user = (object) $user;
					$className = "question".ucfirst($tmpQuestion->type);
					$tmpQuestion = new $className($tmpQuestion->settings);                
					$tmpQuestion->prepareQuestion();
					
					$answerToCheck['question'] = $tmpQuestion->question;
					
				}
			
			}
			$user = $savedUser;
			// $this->question = $this->question[$this->pseudoRandom(0, count($this->question) - 1, $this->setUnique())];
			
			
//			printr($quiz->questions[$key]);
//            printr($quiz->questions[$key]->answersToCheck );exit;
        }
        
        
        $page->data['quiz'] = json_decode(json_encode($quiz), true);
        
    }
    
    static function public_answers() {
        global $connection, $page, $quiz, $development;
        
        $page->templateFile = 'admin_answers';
        
        foreach($quiz->questions as $key => $question) {
            if(in_array($question->type, ['photo','manual','abbreviation'])) {
                $sql = "SELECT answer, result , users.name, groups.name as `group` 
                            FROM answers 
                            LEFT JOIN users ON users.id = answers.user_id 
                            LEFT JOIN `groups` ON users.group_id = groups.id 
                            WHERE
                                quiz_id = :quiz_id AND 
                                question_id = :question_id AND
                                result = '2'
                                ";
                if(!$development) $sql .= " AND groups.name NOT LIKE '".Bulk::prefix()."%' AND users.name NOT LIKE '".Bulk::prefix()."%' AND answers.timestamp <> '".Bulk::date()."%'  "; 
                
                $stmt = $connection->prepare($sql);
                $stmt->execute([':question_id'=>$question->id, ':quiz_id' => $quiz->id]);
				$answers = $stmt->fetchAll();
                if($question->type == 'photo')
					$quiz->questions[$key]->answers = $answers;
				else {
					$tmp = []; $count = [];
					$temp = $answers;
					foreach($temp as $k => $answer) {
						if(isset($quiz->questions[$key]->commas) ) {
							$words = explode(",",$answer['answer']);
							$answers = [];
							foreach($words as $word) {
								$answers[] = [
									'answer' => mb_strtolower(trim($word))
								];
							}
							
							
						
						} else {
							$answers = [$answer];
						}
						
						foreach($answers as $answer) {
							if(!isset($tmp[$answer['answer']])) {
								$tmp[$answer['answer']] = $answer;
								$tmp[$answer['answer']]['count'] = 0;
								$count[$k] = 0;
							}
							$tmp[$answer['answer']]['count']++;
							$count[$k]++;
						}
					}
					 
					$col = array_column( $tmp, "answer" );
					array_multisort( $col, SORT_ASC, $tmp );
					$col = array_column( $tmp, "count" );
					array_multisort( $col, SORT_ASC, $tmp );
					
					$quiz->questions[$key]->answers = $tmp;
				}
                
            } else {
                unset($quiz->questions[$key]);
            }
        }
       
        $page->data['quiz'] = json_decode(json_encode($quiz), true);
    }
    
	
	static function public_prepareihs() {
		global $page;
        $page->templateFile = 'html';
		
		Admin::ihsFeliratkozokFelvitel();
		//Admin::ihsTemplomokLetoltese();
	}
	
	static private function ihsTemplomokLetoltese() {
	/*
		SELECT nev, ismertnev, megye.megyenev, varos, lat, lon, imageurl 
			FROM templomok as t
			LEFT JOIN megye ON megye.id = t.megye 
			LEFT JOIN (
				SELECT church_id, CONCAT("https://miserend.hu/kepek/templomok/",church_id,"/",filename) as imageurl 
				FROM miserend.photos 
				GROUP By church_id 
				ORDER BY id desc
				) as kepek
				ON kepek.church_id = t.id
			WHERE varos IN ( 
				SELECT varos FROM (
					SELECT varos, megye,  count(*) as c 
					FROM miserend.templomok 			
					WHERE varos NOT LIKE 'Budapest%' AND templomok.orszag = 12 
					AND nev NOT LIKE '%ápolna%' AND nev NOT LIKE '%isézőhely%'
					GROUP BY varos order by megye, count(*) desc 
					) as varosok 
				WHERE c > 3
			) 
			AND nev NOT LIKE '%ápolna%' AND nev NOT LIKE '%isézőhely%' AND imageurl IS NOT NULL    
			ORDER BY megyenev, varos
    */ 
	
		global $connection, $config;

		$folder = "";
		
		$file_to_read = fopen('db/templomok.csv', 'r');
		if($file_to_read !== FALSE){
			$c = 0; $varosok = [];
			while(($data = fgetcsv($file_to_read, 10000, ',')) !== FALSE){
				if($c > 0) {				
					//if($c>10) break; printr($data); 
					
					$tmp = explode(".",$data[6]);
					$extension = $tmp[count($tmp)-1];
					$filePath = "/quizzes/ihs/templomok/".md5($data[6]).".".$extension;
					
					$fileok = false;
					if(!file_exists(dirname(__FILE__)."/..".$filePath)) {
						$image = file_get_contents($data[6]);
						if(!$image) $fileok = false;
						if(file_put_contents(dirname(__FILE__)."/..".$filePath,$image)) {
							echo "Letöltve: <a href=\"".$filePath."\">".$filePath."</a><br/>\n";
							$fileok = true;
						} else {							
							$fileok = false;
						}
					} else {
						$fileok = true;
					}
					
					if($fileok) {
						if(!isset($varosok[$data[3]])) $varosok[$data[3]] = [];
						$varosok[$data[3]][] = preg_replace("/^\/quizzes\/ihs/i","",$filePath);						
					} else {
						echo ":( ";
					}
				}
				$c++;
			}
			
			if(count($varosok) > 0) {
				$string = "[<br/>";
				foreach($varosok as $varos => $kepek) {
					if(count($kepek)>=4) {
						$string .= '[["image:'.implode('","image:',$kepek).'"],"'.$varos.'"]';
						$string .= next($varosok) ? ",<br/>" : "<br/>";
					}
				}
				$string .= "]";
				echo $string;
			}
		}
		
	}
	
    static private function ihsFeliratkozokFelvitel() {

        global $connection, $config;

        $fak = file_get_contents("db/fak.txt");
        $fak = explode("\n",$fak);
        unset($fak[0]);unset($fak[1]);unset($fak[2]);
        $fak = array_values($fak);

        $nevek = file_get_contents("db/nevek.txt");
        $nevek = explode("\n",$nevek);
        foreach($nevek as &$nev) {
            if(trim($nev) == '') unset($nev);
        }
        //printr($nevek);
        unset($nevek[0]);unset($nevek[1]);unset($nevek[2]);
        $nevek = array_values($nevek);

        

        $create = []; $s = $ss = $sss = $g =  0;
        $file_to_read = fopen('db/ihs.csv', 'r');
            if($file_to_read !== FALSE){
                while(($data = fgetcsv($file_to_read, 10000, ',')) !== FALSE){
                    
                    //Line By Line
                    if(in_array($data[1],["5-8. évfolyam - 5 fős csapatok","9-12. évfolyam - 4 fős csapatok"])) {
                        $s++;
                        if($data[1] == "5-8. évfolyam - 5 fős csapatok" ) {
                            $level = 1;
                            $name = 6;
                            $count = 9;
                            $option1 = 5;
                            $option2 = 8;

                            $player = 5;


                        } elseif($data[1] == "9-12. évfolyam - 4 fős csapatok" ) {
                            $level = 2;
                            $name = 15;
                            $count = 18;
                            $option1 = 14;
                            $option2 = 17;

                            $player = 4;
                        }


                        $gog_id = 1000 + $s;
                        $code = "ihs-".readable_random_string(6,$gog_id);
                        $gog = [":id"=> $gog_id,":name"=>$data[$name],":code"=>$code,':option1'=>$data[$option1],':option2'=>$data[$option2]];
                        $stmt = $connection->prepare("INSERT IGNORE INTO groupofgroups (id, name, code, option1, option2) VALUES (:id, :name, :code, :option1, :option2) ");
                        $stmt->execute($gog);

                        echo $code."<br>\n";


                        for($i=0;$i< (int) $data[$count] + 2;$i++) { 
                            $ss++;                           
                            $k = rand(0,count($fak)-1);                            
                            $groupName = trim($fak[$k]);
                            $group_id = 1000  + $ss;
                            $group = [":id"=>  $group_id ,":name"=>$groupName,":level"=>$level];
                            unset($fak[$k]);
                            $fak = array_values($fak);
                            $stmt = $connection->prepare("INSERT IGNORE INTO `groups` (id, name, level) VALUES (:id, :name, :level) ");
                            $stmt->execute($group);

                            $stmt = $connection->prepare("INSERT  IGNORE INTO `lookup_groupofgroups` (groupofgroups_id, group_id) VALUES (:groupofgroups_id, :group_id) ");
                            $stmt->execute([':groupofgroups_id'=> $gog_id,':group_id'=> $group_id]);

                            $names = $nevek;
                            for($c=0;$c< $player;$c++) {
                                $sss++;
                                $k = rand(0,count($names)-1);
                                $user = [":id" => 1000 + $sss, ":name"=>$names[$k],":email"=>slugify($names[$k])."@".slugify($groupName).".ihs",":group_id" => $group_id];
                                $group['users'][] = $user;
                                unset($names[$k]);                                
                                $names = array_values($names);

                                $stmt = $connection->prepare("INSERT IGNORE INTO `users` (id, name, active, email, group_id) VALUES (:id, :name, 1, :email, :group_id) ");
                                $stmt->execute($user);
                            }

                            $gog['groups'][] = $group;  
                        }
                        $create[] = $gog;
                    } elseif($data[1] == "Felnőttek - 3 fős csapatok - 18+")  {
						$g++;
						$first = [];
						
						foreach( [24,28,32] as $cell)  {
							$chars = str_split_unicode($data[$cell]);
							$first[] = $chars[0].$chars[1];
						}
						natsort($first);
						$pw = implode("",$first);
												
						$group_id = 3000 + $g;
						$stmt = $connection->prepare("INSERT IGNORE  INTO `groups` (id, name, level) VALUES (:id, :name, :level) ");
						$stmt->execute([':id'=> $group_id,':name'=> $data[20], ':level' => 3]);

						foreach( [ [24,25,26], [28,29,30], [32,33,34] ] as $k => $keys )  {
							$user_id = 3000 + ( 3 * $g ) + $k;
							$stmt = $connection->prepare("INSERT IGNORE INTO `users` (id, name, active, email, password, group_id) VALUES (:id, :name, 1, :email, :pwd ,:group_id) ");
							$stmt->execute([':id'=> $user_id,':name'=> $data[$keys[0]]." ".$data[$keys[1]], ':email' => $data[$keys[2]], ':group_id' => $group_id, ':pwd' => crypt($pw, $config['authentication']['salt'])]);
						}
						
						echo $pw."<br/>\n";
					}


                    //printr($data);
                }
                fclose($file_to_read);
            }

        //INSERT 
        //printr($create);

    }
	
    static function public_install() {
        global $config;

        global $connection;
        $sqlFiles[] = 'db/SQLTemplate.sql';
        if(isset($config['addons'])) {
            foreach($config['addons'] as $addon) {
                $file = 'addons/'.$addon."/".$addon.".sql";
                if(file_exists($file)) {
                    $sqlFiles[] = $file;
                }
            }

        }

        foreach($sqlFiles as $file) {
            echo "<h3>".$file."</h3>";
            $sql = file_get_contents($file);
            $result = $connection->exec($sql);
            var_dump($result);
        }

        global $page;
        $page->templateFile = 'html';

    }

    static function public_changelevel() {
        global $user, $connection;
        $level = getParam( $_REQUEST, "level", false);
		$random = getParam( $_REQUEST, "random", -1);

        if($user->admin == 1) {
			if(in_array($level,[1,2,3,4])) {
            //printr($user);
				$stmt = $connection->prepare("UPDATE `groups` SET level = :level WHERE id = :group_id LIMIT 1");
				$stmt->execute([':level'=>$level,':group_id' => $user->group_id]);
				$user->level = $level;
				$_SESSION['user']['level'] = $level;
			} else if ($random == 'true') {	
				$_SESSION['random'] = true;
			} else if ($random == 'false') {
				$_SESSION['random'] = false;
			}
        }
        
        header("Location: index.php");
        exit;
        

    }

    static function verify() {
        global $connection;
                
        if(!$action = getParam($_REQUEST,'action',false) OR !in_array($action,['ok','no','update'])) die('There is no such action');
        
        if($action == 'ok' OR $action == 'no' ) {
            if(! $id = getParam($_REQUEST, 'id') ) die('There is no id');
            $id = explode('-',$id);
            if(count($id) != 3) die('Invalid id');
            
            if($action == 'ok') $result = 2;
            elseif($action == 'no') $result = -1;
            
            $stmt = $connection->prepare("UPDATE answers SET result = :result, timestamp = :timestamp WHERE quiz_id = :quiz_id AND question_id = :question_id AND user_id = :user_id ");                
            if(! $stmt->execute([':result'=>$result,':timestamp' => date('Y-m-d H:i:s'),':quiz_id'=>$id[0], ':question_id' => $id[1], ':user_id' => $id[2]]) ) printr($connection->errorInfo());
            echo "#cardid".$_REQUEST['id'];
            exit;
        } elseif ($action == 'update') {
            if(! $ids = getParam($_REQUEST, 'ids') ) die('There is no id list.');
            if(preg_match('/(;|\'| )/i',$ids)) die('Invalid id list.'); // TODO: Elég ennyi egy mysql injection ellen?
            
            $sql =  "SELECT "
                    . " CONCAT( quiz_id, '-', question_id, '-', user_id ) as id  "
                    . "FROM answers "
                    . "WHERE  CONCAT( quiz_id, '-', question_id, '-', user_id )"
                    . " IN ('".implode("','",explode(',',$ids))."') AND result IN ('-1','2')";

            $stmt = $connection->prepare($sql);                        
            if(! $stmt->execute() ) printr($connection->errorInfo());
            $toDelete = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if($toDelete)
                echo "#cardid".implode(', #cardid',$toDelete);
            
        }
        
                        
    }
    
}
