<?php

class Admin {
    //put your code here
    
    static function stats() {
        global $page, $quiz;
        
        $page->templateFile = 'stats';
        $page->data['rankingTable'] = getRankingTable($quiz->id);
        
    }
    
}
