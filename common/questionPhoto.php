<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of questionNumber
 *
 * @author webdev
 */
class questionPhoto extends Question {
    
    public $inputType = 'file';
    
    
    function getNewAnswer() {
        $key = $this->id;
        if(isset($_FILES['questions_'.$key])) {
            if($_FILES['questions_'.$key]['error'] == 4) {
                // Nincs kép feltöltve. Valószínűleg egyszerűen azért, mert nem nyomott még rá.
            } elseif ($_FILES['questions_'.$key]['error'] == 1) {
                $this->messages[] = ['danger',"Átlépted a megengedett legnagyobb méretet ami ".ini_get('upload_max_filesize')."."];
            } elseif ( $_FILES['questions_'.$key]['error'] > 0 ) {
                die("Uuuupsz. Mi történt? Annyit tudok, hogy: ".$_FILES['questions_'.$key]['error'].", de mit jelenthet ez?");
                
            } else { 
                // Akkor dolgozzuk fel, mert az jó
                $return = $this->uploadImage($_FILES['questions_'.$key]);

                if(isset($return['error'])) $this->messages[] = ['danger',$return['error']];
                else {                    
                    return $return;
                }                
            }                 
        }           
        return false;
    }
    
    
    /**
     * 
     * @param type $user_answer
     * @return int -1 = wrong, 0 = null, 1 = manual validation needed, 2 = ok
     */
    function getUserResult($user_answer) {
        if(isset($this->old_answer[0]) AND $this->old_answer[0]['answer'] != "") {
            if($user_answer != $this->old_answer[0]['answer'] ) { //There was an old photo, but a new has arrived
                unlink($this->old_answer[0]['answer']);
                return 1 ;
            } else { //There is no new photo but the old one.
                return $this->old_answer[0]['result'];
            }            
        } elseif($user_answer != '') { //New photo has arrived
            return 1;
        } else {
            return 0;
        }
    }
    
    
    function uploadImage($_file) {
    
        if( ! file_exists($_file['tmp_name']) ) {
            return ['error' => 'Nem található az ideiglenesen feltöltött file. Mi hibánk.'];
        }

        //Képség ellenőrzése, mert másképp át lehetne verni.
        $image_data = @getimagesize($_file['tmp_name']);    
        if( $image_data === false ) {
            return ['error' => 'Uupsz, '.$_file['name'].' nem is kép. Képet kérünk!'];
        }

        // Ha nincs GD, akkor gond van. TODO: nagy képekkel de úgyis működjön
        if(!function_exists('imagecreatefromstring')) {
           return ['error' => 'Nem tudunk képeket  átméretezni. Mihibánk.'];
           // Megoldás: GD azaz apt-get install php7.3-dev ÉS? apt-get install libjpeg-dev libfreetype6-dev
        }

        // Átméretezés, ha szükséges
        $image = imagecreatefromstring( file_get_contents( $_file['tmp_name']));
        $box = 1200;
        if($image_data[1] > $image_data[0] AND $image_data[1] > $box ) { //álló és túl nagy
            $newWidth = $image_data[0] * ( $box / $image_data[1]);
            $image = imagescale($image, $newWidth);
        } else if($image_data[0] > $image_data[1] AND $image_data[0] > $box ) { // fekvő és túl nagy
           $image = imagescale($image, $box);
        }    
        //Mentés
        global $imageFolder;
        $filename = md5(date('Y-m-d H:i:s')."-".rand(100,999)).".jpg"; //Igénytelen random név
        imagejpeg ($image,$imageFolder."/".$filename);
        return $imageFolder."/".$filename;    
    }
  
}


