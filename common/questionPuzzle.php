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
        foreach($files as $file) {
            preg_match('/^(.*?)\.('.$extensions.')$/i',$file,$match);
            $this->options[] = $match[1];                
        }
        sort($this->options);                    

        //Choose file               
        $c = $this->pseudoRandom(0, count($files) - 1 , $this->setUnique() );
        $file = $files[$c];

        //$file = $files[ rand(0,count($files)-1)];

        /*
        if(!isset($scores[$user['tanosztaly']]) AND ( $user['tanosztaly'] == 'DEV' OR $user['tanosztaly'] == '')) {
            $percentage = rand(1,50);
        }
        else 
        if(!array_key_exists($user['tanosztaly'], $scores)) {
            $percentage = 1;
        } else 
            $percentage = $scores[$user['tanosztaly']]['jatekos'];
        */
        $percentage = 8; //rand(1,15);
                
        $filename = sys_get_temp_dir ( ) . "/" . $this->quiz_id."/puzzle_".md5($file."-".$percentage).'.jpg';

        if(!file_exists($filename)) {
           //Nem az igazi, mert egészen újat generál mindig, nem pedig növekszik szépen.
           //TODO: nem törli a régit
           $this->createImagePuzzle($this->folder.'/'.$file,$filename,$percentage);
           
        }

        $this->question .= '<img src="'.$filename.'" class="img-thumbnail mx-auto d-block">';                    
        
        preg_match('/^(.*?)\.('.$extensions.')$/i',$file,$match);
        $this->answer = $match[1];        

    }

    function createImagePuzzle($from,$to,$percentage) {
        $felosztas = [6,12];
        
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
        $image = imagecreatefromstring( file_get_contents( $from ));

        $color = imagecolorallocate ($image, 211,211,211);

        $whites = $this->randomNumbers($felosztas[0]*$felosztas[1] - $visible,$felosztas[0]*$felosztas[1]);
        
        
    

        //Egy-egy négyzet mérete
        $block = [ $image_data[0] / $felosztas[0], $image_data[1] / $felosztas[1]  ];

        foreach($whites as $id ) {
            $x1 = ( ( $id-1 ) % $felosztas[0] ) * $block[0];
            $y1 = floor( ( $id - 1 ) / $felosztas[0]  ) * $block[1];
            $x2 = $x1 + $block[0];
            $y2 = $y1 + $block[1];
            imagefilledrectangle($image, $x1,$y1,$x2,$y2, $color);
        }
        
        imagejpeg ($image,$to);
        return true;
    }

    function randomNumbers($darab, $max) {
        $numbers = []; for($i=1;$i<=$max;$i++) $numbers[] = $i;
        shuffle($numbers);
        $numbers = array_slice($numbers, 0, $darab);
        sort($numbers);

        return $numbers;    
    }
}


