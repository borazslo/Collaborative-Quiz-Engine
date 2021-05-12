# Collaborative-Quiz-Engine


Installation
* clone repository
* `php composer.phar install` (if that is not enough: `php composer.phar update` )
* `mysql -u [username] -p [database_name] < SQLTemplate.sql`
* `cp config.php.default config.php`
* create / copy json quiz file (with the neccessary folders) to `quizzes`
* `mcedit config.php`

Optional
* You can put somewhere [index.php L33+](https://github.com/borazslo/Collaborative-Quiz-Engine/blob/d6f4da98a1f43b21291a9c0d6a725701a3422485/index.php#L33) this code to generate bulk data: `$bulk = new Bulk($quiz); $bulk->addAll();`
