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
            if(!in_array($question->type, ['photo','manual'])) {
                unset($quiz->questions[$key]);
                continue;
            } 
                        
            $sql = "SELECT 
                    CONCAT(:quiz_id, '-', :question_id, '-', users.id) as id, 
                    users.name as `user`, 
                    groups.name as `group`, 
                    answers.answer, answers.result, answers.timestamp                    
                FROM answers 
                LEFT JOIN users ON users.id = answers.user_id 
                LEFT JOIN groups ON users.group_id = groups.id 
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
            //printr($quiz->questions[$key]->answersToCheck );exit;
        }
        
        
        $page->data['quiz'] = json_decode(json_encode($quiz), true);
        
    }
    
    static function photos() {
        global $connection, $page, $quiz, $development;
        
        $page->templateFile = 'photos';
        
        foreach($quiz->questions as $key => $question) {
            if($question->type == 'photo') {
                $sql = "SELECT answer, result , users.name, groups.name as `group` 
                            FROM answers 
                            LEFT JOIN users ON users.id = answers.user_id 
                            LEFT JOIN groups ON users.group_id = groups.id 
                            WHERE
                                quiz_id = :quiz_id AND 
                                question_id = :question_id AND
                                result = '2'
                                ";
                if(!$development) $sql .= " AND groups.name NOT LIKE '".Bulk::prefix()."%' AND users.name NOT LIKE '".Bulk::prefix()."%' AND answers.timestamp <> '".Bulk::date()."%'  "; 
                
                $stmt = $connection->prepare($sql);
                $stmt->execute([':question_id'=>$question->id, ':quiz_id' => $quiz->id]);
                $quiz->questions[$key]->answers = $stmt->fetchAll();
                
            } else {
                unset($quiz->questions[$key]);
            }
        }
       
        $page->data['quiz'] = json_decode(json_encode($quiz), true);
    }
    
    static function public_prepareihs() {

        global $page, $connection;
        $page->templateFile = 'html';

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

        

        $create = []; $s = $ss = $sss = 0;
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
                        $gog = [":id"=> $gog_id,":name"=>$data[$name],":code"=>"ihs-".readable_random_string(6),':option1'=>$data[$option1],':option2'=>$data[$option2]];
                        $stmt = $connection->prepare("INSERT IGNORE INTO groupofgroups (id, name, code, option1, option2) VALUES (:id, :name, :code, :option1, :option2) ");
                        $stmt->execute($gog);


                        for($i=0;$i< (int) $data[$count] + 2;$i++) { 
                            $ss++;                           
                            $k = rand(0,count($fak)-1);
                            printr($fak);
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
                    }


                    //printr($data);
                }
                fclose($file_to_read);
            }

        //INSERT 
        printr($create);

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
