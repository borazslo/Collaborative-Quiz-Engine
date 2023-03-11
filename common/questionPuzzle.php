<?php

class questionPuzzle extends Question {

    public $inputType = 'select';
    
    function prepareQuestion() {
        $extensions = "JPG|jpeg|jpg|png";

        if(!isset($this->folder)) throw new Exception("Question ".$this->id.": 'puzzle' type question needs a folder.");
        if(!is_dir($this->folder)) throw new Exception("Question ".$this->id.": 'puzzle' type question needs a valid folder.");

        $files = array_values(preg_grep('~\.('.$extensions.')$~', scandir($this->folder)));

        // Collect possible answers
        $this->options = [];                           
        foreach($files as $k => $file) {
            preg_match('/^(.*?)\.('.$extensions.')$/i',$file,$match);
            if(!preg_match('/^puzzle_/i',$file)) {
                $this->options[] = $match[1];                
            } else {
                unset($files[$k]);
            }
        }
        
        if($this->options == []) {
            $this->question .= "<small class='d-block alert alert-warning'>Itt kéne egy képnek lenni, de sajnos nincs. Ezt elrontottuk. Elnézést.<br/>A helyes válasz ezért az hogy „senki”.</small>";
            $this->answer = ["„senki”", "senki"];
            $this->inputType = "text";
            return;
        }

        //Choose file               
        $c = $this->pseudoRandom(0, count($files) - 1 , $this->setUnique() );        
        $c = rand(0,count($files)-1);
        $file = $files[$c];

        $percentage = 28; //rand(1,15);

        // TODO: RM Regnum specifikus dolgok
        $sql = "SELECT count(*) c  
                    FROM users 
                    LEFT JOIN `groups` ON groups.id = users.group_id 
                    LEFT JOIN regnum_communities r ON r.name = groups.name 
                    WHERE 
                        users.name NOT LIKE '[bulk]%' AND
                        r.localRM = :localRM 
                    GROUP BY `localRM` 
                    LIMIT 1 ";        
        
        global $connection, $user;
        $stnt = $connection->prepare($sql);
        $stnt->execute([':localRM' => $user->group3]);
        $result = $stnt->fetch();
        
        if(!$result or $result == []) {
            srand(strtotime(date('Y-m-d H')));
            $percentage = rand(18,30);
            srand();    
        } else
            $percentage = $result[0];
        
                                                        
        $filename = "puzzle_".md5($file."-".$percentage).'.jpg';
        
        if(!file_exists($this->folder. "/". $filename) ) {
           //TODO: nem törli a régit
           $this->createImagePuzzle($this->folder.'/'.$file, $this->folder. "/". $filename,$percentage);           
        } 

        $this->question .= '<img src="'.$this->folder.'/'.$filename.'" class="img-thumbnail mx-auto d-block">';                    
        
        preg_match('/^(.*?)\.('.$extensions.')$/i',$file,$match);
        $this->answer = [ $match[1] ]; 
        $this->answerFile = $file;

    }

    function createImagePuzzle($from,$to,$percentage) {
        global $user;
        $felosztas = [8,11];
        
        $visible = $felosztas[0]*$felosztas[1] * ( $percentage / 100 );

        if(!file_exists($from)) {
            die('Hiányzik a képfájl: '.$from."!");        
            return false;
        }

        $image_data = @getimagesize($from);    
        if( $image_data === false ) {
            die('Ez nem is képfájl: '.$from."!");        
            return false;
        }                  
        if($image_data[0] > $image_data[1]) $felosztas = [$felosztas[1], $felosztas[0]];
        
        $image = imagecreatefromstring( file_get_contents( $from ));

        $color = imagecolorallocate ($image, 211,211,211);
        
        $whites = $this->randomNumbers($felosztas[0]*$felosztas[1] - $visible,$felosztas[0]*$felosztas[1], $this->id * 1000 + $user->id );
        
        
    

        //Egy-egy négyzet mérete
        $block = [ $image_data[0] / $felosztas[0], $image_data[1] / $felosztas[1]  ];

        foreach($whites as $id ) {
            $x1 = ( ( $id-1 ) % $felosztas[0] ) * $block[0];
            $y1 = floor( ( $id - 1 ) / $felosztas[0]  ) * $block[1];
            $x2 = $x1 + $block[0];
            $y2 = $y1 + $block[1];
            imagefilledrectangle($image, $x1,$y1,$x2,$y2, $color);
        }
        //exit;
        imagejpeg ($image,$to);
        return true;
    }

    function getUserResult($user_answer) {
        $result = parent::getUserResult($user_answer);
        
        if(  $result == 2) {
            $this->question = preg_replace('/puzzle_(.*?)\.jpg/',$this->answerFile,$this->question);
        }
       
        return $result;
    }
    
    function randomNumbers($darab, $max, $seed) {
        srand($seed);
        
        $numbers = []; for($i=1;$i<=$max;$i++) $numbers[] = $i;

        for($i=1;$i<=($max - $darab);$i++) {
            $key = array_rand($numbers);
            unset($numbers[$key]);
        }

        srand();
        
        return $numbers;    
    }
}


