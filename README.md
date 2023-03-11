# Collaborative-Quiz-Engine


Installation
* Install [docker](https://www.docker.com/)
* `docker-compose build`
* `docker-compose up`
* `docker exec -i db mysql -uMYSQL_USER -pMYSQL_PASSWORD MY_DATABASE < db/SQLTemplate.sql`
* `cp config.php.default config.php`
* create / copy json quiz files (with the neccessary folders) to `quizzes`
* set `$config['defaultQuizId']` in `config.php` OR use `index.php?q=quizname`


Optional
* See: config.php.default
* You can put somewhere [index.php L33+](https://github.com/borazslo/Collaborative-Quiz-Engine/blob/d6f4da98a1f43b21291a9c0d6a725701a3422485/index.php#L33) this code to generate bulk data: `$bulk = new Bulk($quiz); $bulk->addAll();`
