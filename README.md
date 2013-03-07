Self Hosted Videos
==================

A small app to setup your own youtube with your private videos

Install steps:
--------------
- sudo apt-get install ffmpeg
- git clone git@github.com:yannickmahe/selfhostedvideos.git
- Setup Vhost
- edit app/config/parameters.yml
- curl -sS https://getcomposer.org/installer | php
- php composer.phar update
- php app/console doctrine:database:create
- php app/console doctrine:schema:update --force
- php app/console fos:user:create --super-admin admin admin@example.com 123456
- mkdir web/uploads
- chmod -R 777 web/uploads