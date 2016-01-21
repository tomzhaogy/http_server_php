swoole http server

## Requirements

* PHP 5.3.10 or later
* Linux, OS X and basic Windows support (Thanks to cygwin)
* GCC 4.4 or later
1.install swoole
   wget https://codeload.github.com/swoole/swoole-src/tar.gz/swoole-1.7.19-rc2
   phpize
   ./configure
   make
   make install
   vi /etc/php.ini
    [swoole]
    extension=swoole.so
2.config.php 
   db node is mysql service  configuration
   redis node is redis service  configuration
   http node is http service  configuration
3.routeconfig.php 
