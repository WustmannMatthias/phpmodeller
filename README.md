# PHP Modeller
This project allows to model the architecture of any PHP application into a Graph database. It supposes you annotate your php files with their corresponding features in your applications.

Once modelled, PHP Modeller will allow you to have a better understanding of the topology of your projects, will give you some useful informations and statistics, like useless namespaces or broken dependencies, but will mainly help you to analyse the impact of a modification in your application, by telling you exactly which features you have to test before publishing a new version.


### Installation (Ubuntu) :

Clone the repository into your server directory. Exemple for an apache server : 
```console
$ cd /var/www/html
$ sudo git clone https://github.com/WustmannMatthias/PHP-Modeller
```



Install PHP-Modeller by launching the install script as a superuser : 
```
$ chmod +x install.sh
$ sudo ./install.sh
```



### How to use :

##### In Local
To model an application, just clone her repository into the /data/projects directory of this application : 
```
$ cd /var/www/html/PHP-Modeller/data/projects
$ git clone <your_project_url>
```
And if necessary, install additional dependencies (example with composer) :
```
$ cd <your_project_name>
$ composer install
```




Then go to the user interface of PHP-Modeller simply by typing following URL in the URL field of your browser, 
```
localhost/PHP-Modeller
```
and follow the instructions !






##### On a server
Establish a SSH conection to the server, then go to the project directory of the server and clone the repository of the application you want to model :
```
$ ssh <username>@<server_adress>
$ cd /var/www/html/PHP-Modeller/data/projects
$ git clone <your_project_url>
```
And if necessary, install additional dependencies (example with composer) :
```
$ cd <your_project_name>
$ composer install
```

Then, from any machine, just type following adress in your browser :
```
<your_server_adress>/PHP-Modeller
```
and follow the instructions !


