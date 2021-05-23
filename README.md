# Snappy Shopper test  (No Framework)

## Requirements
* PHP >= 7.1.0
* composer

## Instructions

1. Checkout project:
    ```
    git clone https://github.com/andrewf137/SnappyShopperTestNoFramework.git
    ```
2. "cd" to project folder.
3. Run `composer install`.
4. Set database credentials in config/config.php file 
5. Execute this script to create the database and load fixtures
    ```
    php init.php
    ```
   This script creates a few random agents-properties relations in the database 
6. Set a virtual host pointing out to `<projectFolder>`, something like:
    ```
    <VirtualHost *:80>
        ServerAdmin webmaster@localhost
        DocumentRoot /home/developer/workspace/SnappyShopperTest
    
        ServerName snappyshoppertest.loc
        ServerAlias snappyshoppertest.loc
    
        <Directory />
            Options FollowSymLinks
            AllowOverride All
        </Directory>
    
        <Directory /home/developer/workspace/SnappyShopperTest>
            AllowOverride All
            Order Allow,Deny
            Allow from All
            Require all granted
        </Directory>
    
        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined
    </VirtualHost>
    
    # vim: syntax=apache ts=4 sw=4 sts=4 sr noet
    ```
7. There are two end points available:
   * `index.php/save-properties?page[from]=1&page[to]=1&perPage=1`  
      This will populate the database with all properties determined by the values "from", "to" and "perPage"
   * `index.php/get-top-agents`  
      This will return a list of the top agents. For this to work, step 5 is necessary.