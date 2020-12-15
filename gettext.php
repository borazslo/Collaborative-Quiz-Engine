<?php

//https://blog.terresquall.com/2020/09/troubleshooting-php-gettext/
// sudo localedef -c -i en_GB -f UTF-8 en_AU
// locale -a
$lang = 'hu_HU';

// Set the language.
$results = putenv("LC_ALL=$lang");
if (!$results) {
    exit ('putenv failed');
}
setlocale(LC_ALL, $lang);
echo setlocale(LC_ALL, 0);
$results = setlocale(LC_ALL , $lang);
if (!$results) {
    exit ('setlocale failed: locale function is not available on this platform, or the given local does not exist in this environment');
}

$domain = 'myPHPApp';
//$results = bindtextdomain($domain,  dirname(__FILE__)."/locale/nocache" );
$results = bindtextdomain($domain,  dirname(__FILE__)."/locale" );
echo 'new text domain is set: ' . $results. "\n";

bind_textdomain_codeset($domain, 'UTF-8');

$results = textdomain($domain);
echo 'current message domain is set: ' . $results. "\n";

// Sets the folder where the messages textdomain will check for translation files.



// Specifies the character set that the translation file uses.


// Set the text domain to use.

// Print the translated version of 'Welcome to My PHP Application'
echo gettext("Welcome to My PHP Application");

// Or use the alias _() for gettext()
echo _("Have a nice day");


?>
