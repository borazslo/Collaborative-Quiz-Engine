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
        
        
        foreach($settings as $key => $val) {
            $this->$key = $val;
        }
                                
        $this->prepareHint();
        $this->prepareQuestion();
        $this->prepareInput();
        
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
                                
            return  ''
                    . '<div class="embed-responsive embed-responsive-16by9">'
                    . '<iframe class="embed-responsive-item" src="'.preg_replace('/watch\?v=/','embed/',$url).'" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>'
                    . '</div>'
                    . '<br/>Itt egy videó, ami segíthet: <a class="text-decoration-none" target="_blank" href="'.$url.'">YouTube</a>.';           
                                                
        } elseif (preg_match('/^http/i', $url )) {
            return 'Itt egy link, ami segíthet: <a class="text-decoration-none" target="_blank"  href="'.$url.'">KATTINTS</a>!';
        
            
        } else {
               return $url; 
        }             
        
        
    }
    
    function prepareQuestion() {
        
    }
    
    function prepareInput() {
        
    }
}
