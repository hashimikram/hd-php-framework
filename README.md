

# HD PHP Framework

HD PHP Framework is a web application framework

Requirements
-------------------------------------------------
To use this framework, you will need (as a minimum):
* PHP v5.4 (at least 5.6 is recommended)

Installation with Composer
-------------------------------------------------
Step 1 - Create in bitbucket an OAuth consumer. 

 - Go to BitBucket Settings -> OAuth and click the button "Add Consumer".
 
It's important to populate Callback URL with a url (http://example.com will work just fine)
Click 'This is a private consumer'
Click 'Projects -> Write'

Step 2 - run the following command:

    composer config -g bitbucket-oauth.bitbucket.org YOUR_KEY YOUR_SECRET

Step 3 - Include the library in your project

    composer require iss/hdphp-framework:^1.1
 
 Step 4 - Modify your composer.json file to include the repositry url
 

    "repositories" : [{
        "type" : "vcs",
        "url" : "git@bitbucket.org:hdsoftwaredev/hd-php-framework.git"
      }
    ]
 
Step 5 - Install PHP extensions

    sudo apt install php7.3-cgi unzip php7.3-zip php7.3-bcmath php7.3-cgi php7.3-curl php7.3-gd php7.3-gmp php7.3-intl php-geoip php7.3-mbstring php-mongodb php7.3-xml php-xdebug php7.3-mysqli

Step 6 - Update your dependencies with Composer
  

    composer update
Step 7 - Finally, be sure to include the autoloader in your project

    require_once '/path/to/your-project/vendor/autoload.php';

The Library has been added into your dependencies and is ready to be used.

