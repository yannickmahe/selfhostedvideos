Self Hosted Videos
==================

v O.O1 alpha

A PHP web app to setup your own YouTube like site for your private videos. This allows you to have your video library accessible from anywhere, and have your videos be embeddable. This app is aimed at a private audience, therefore login is required and robots.txt disallows crawling from web browsers.

Install steps:
--------------
- sudo apt-get install ffmpeg
- git clone git@github.com:yannickmahe/selfhostedvideos.git
- cd selfhostedvideos
- curl -sS https://getcomposer.org/installer | php
- php composer.phar update
- edit app/config/parameters.yml
- php app/console doctrine:database:create
- php app/console doctrine:schema:update --force
- php app/console fos:user:create --super-admin admin admin@example.com 123456
- mkdir web/uploads
- chmod -R 777 web/uploads
- setup server vhost

TODO list:
----------
- simpler install process
- user management interface
- edit rights for admin
- convert non mp4 to mp4
- optimize videos for mobile
- make it possible to prevent embedding
- ajax load for video list, instead of loading all videos
- optimize layout for mobile & tablet (responsive)
- show link to video page when upload finished
- better looking uploads bar
- autocomplete on search field
- add licence.txt
- customizable name
- translate texts
- check mkv format compatibility
