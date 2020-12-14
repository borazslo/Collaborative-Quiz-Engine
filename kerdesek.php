<?php
$_string = <<<XML
#1
Ignáccal más is történt.
Ezért van róla kérdésünk.
Itt egy kis segíttség
ez meg a helyes válasz
#4
Szent Ignác Pamplónában nagyot küzdött és harcolt, és mindenki megemlegette.
Pamplonában a Francia kapu alatt (Porta da Francia) milyen robogó hajt éppen át?
Talán errefelé érdemes körülnézni: <a class="text-decoration-none" target="_blank" href="https://bit.ly/2Ag747s">Google Street View</a>
helyes válasz
#9
Ignáccal más is történt.
Ezért van róla kérdésünk.
Itt egy kis segíttség
ez meg a helyes válasz
#5
Ignáccal más is történt.
Ezért van róla kérdésünk.
Itt egy kis segíttség
ez meg a helyes válasz
#6
Újabb is történt.
Ezért van róla MÁSIK kérdésünk.
Ehhez nem segítünk
ez a helyes válasz
XML;

$_rows = explode("\n",$_string);
$kerdesek = [];
foreach($_rows as $row) {
    if(preg_match('/^#([0-9]{1,2})/',$row,$match)) {
        $key = $match[1];
    } else {
    $kerdesek[$key][] = $row;
    }
    
}
