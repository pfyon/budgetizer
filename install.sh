#!/bin/bash
echo "Welcome to the budgetizer installer."
if [ $("whoami") != "root" ]; then
	echo "This script must be run as root"
	exit 1
fi
echo "This script expects to be run from the root directory of your git checkout"
echo ""

SETUPPACKAGES=0
SETUPDB=0
SETUPWEB=0

#Default database stuff

DBUSER_DEFAULT="budget"
DBPASS_DEFAULT="superhugelongpassword"
DBNAME_DEFAULT="budgetdb"

read -p "Ready to install the required packages? (y/N) " RESULT
if [ "$RESULT" == "y" ]; then
	echo "Updating package list"
	echo ""
	apt-get update

	echo "Installing apache2, php5, php5-pgsql, and postgresql"
	echo ""
	apt-get install apache2 php5 php5-pgsql postgresql

	echo ""
	echo "Done package installation"
	SETUPPACKAGES=1
else
	echo "Skipping package installation"
	SETUPPACKAGES=0
fi


read -p "Ready to setup the database? (y/N) " RESULT
if [ "$RESULT" == "y" ]; then
	echo "Setting up the database"
	echo ""

	OKAY="N"
	while [ "$OKAY" != "y" ]; do
		read -p "Enter the desired name of the database [$DBNAME_DEFAULT]: " DBNAME
		if [ "$DBNAME" == "" ]; then
			echo "Using default"
			DBNAME="$DBNAME_DEFAULT"
		fi

		read -p "Enter the desired name of the database user [$DBUSER_DEFAULT]: " DBUSER
		if [ "$DBUSER" == "" ]; then
			echo "Using default"
			DBUSER="$DBUSER_DEFAULT"
		fi

		read -p "Enter the desired password for user $DBUSER [$DBPASS_DEFAULT]: " DBPASS
		if [ "$DBPASS" == "" ]; then
			echo "Using default"
			DBPASS="$DBPASS_DEFAULT"
		fi

		echo ""
		echo "Database name: $DBNAME"
		echo "Database user: $DBUSER"
		echo "Database password: $DBPASS"
		echo ""
		read -p "Are the above values correct? (y/N) " OKAY
	done

	#Create the entry in pg_hba.conf
	echo "We'll be inserting this line into /etc/postgresql/9.1/main/pg_hba.conf :"
	echo "host     $DBNAME     $DBUSER     127.0.0.1/32     md5"
	echo ""

	read -p "Ready? (y/N) " RESULT
	if [ "$RESULT" == "y" ]; then
		echo "host     $DBNAME     $DBUSER     127.0.0.1/32     md5" >> /etc/postgresql/9.1/main/pg_hba.conf
		echo "Reloading postgresql to get it to re-read that file"
		/etc/init.d/postgresql reload
	fi

	echo "Creating user $DBUSER"
	echo ""
	su - postgres -c "psql -c \"CREATE USER $DBUSER WITH PASSWORD '$DBPASS';\""

	echo "Creating database $DBNAME"
	echo ""
	su - postgres -c "psql -c 'CREATE DATABASE $DBNAME WITH OWNER $DBUSER;'"

	echo "Importing the schema"
	PGPASSWORD="$DBPASS"
	export PGPASSWORD

	psql -U $DBUSER -h localhost $DBNAME < database/schema.sql

	echo ""	
	echo "Done database setup"

	SETUPDB=1
else
	echo "Skipping database setup"
	SETUPDB=0
fi

read -p "Ready to setup the webserver? (y/N) " RESULT
if [ "$RESULT" == "y" ]; then
	if [ -f "/etc/apache2/sites-available/budgetizer" ]; then
		echo "/etc/apache2/sites-available/budgetizer already exists"
		echo "Skipping virtual host and SSL key setup"
	else
		echo "Enabling SSL and php5"
		echo ""
		a2enmod ssl php5 rewrite
	
		echo "Creating SSL certificate"
		echo ""
		mkdir /etc/apache2/certs
		openssl req -x509 -nodes -days 3650 -newkey rsa:2048 -keyout /etc/apache2/certs/apache.key -out /etc/apache2/certs/apache.crt
	
		echo "Creating the virtual host config file"
		echo "(This is pretty much a copy and paste from the default and default-ssl virtual host files)"
		echo ""
		echo '<VirtualHost *:80>
        ServerAdmin webmaster@localhost

        DocumentRoot /var/www
	DirectoryIndex index.php
        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /var/www/>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                Order allow,deny
                allow from all
        </Directory>

	RewriteEngine On
	RewriteCond %{HTTPS} !=on
	RewriteRule ^/?(.*) https://%{SERVER_NAME}/$1 [R,L]

        ErrorLog ${APACHE_LOG_DIR}/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>

<IfModule mod_ssl.c>
<VirtualHost _default_:443>
        ServerAdmin webmaster@localhost

        DocumentRoot /var/www
	DirectoryIndex index.php
        <Directory />
                Options FollowSymLinks
                AllowOverride None
        </Directory>
        <Directory /var/www/>
                Options Indexes FollowSymLinks MultiViews
                AllowOverride None
                Order allow,deny
                allow from all
        </Directory>

        ErrorLog ${APACHE_LOG_DIR}/error.log

        # Possible values include: debug, info, notice, warn, error, crit,
        # alert, emerg.
        LogLevel warn

        CustomLog ${APACHE_LOG_DIR}/ssl_access.log combined

        SSLEngine on
        SSLCertificateFile    /etc/apache2/certs/apache.crt
        SSLCertificateKeyFile /etc/apache2/certs/apache.key

        <FilesMatch "\.(cgi|shtml|phtml|php)$">
                SSLOptions +StdEnvVars
        </FilesMatch>

        BrowserMatch "MSIE [2-6]" \
                nokeepalive ssl-unclean-shutdown \
                downgrade-1.0 force-response-1.0
        # MSIE 7 and newer should be able to use keepalive
        BrowserMatch "MSIE [17-9]" ssl-unclean-shutdown
</VirtualHost>
</IfModule>' > /etc/apache2/sites-available/budgetizer
	fi

	read -p "Ready to copy php files to /var/www/ ? (y/N) " RESULT
	if [ "$RESULT" == "y" ]; then
		echo "Copying files"
		cp -r ajax /var/www/
		cp -r class /var/www/
		cp -r include /var/www/
		cp index.php /var/www/
		cp login.php /var/www/
		cp logout.php /var/www/
		cp budget.php /var/www/
		cp upload.php /var/www/
		cp style.css /var/www/

		if [ -f "/var/www/index.html" ]; then
			mv /var/www/index.html /var/www/index.html.bak
		fi
	else
		echo "Skipping php file copy"
	fi

	read -p "Do you want to copy and configure the config file? (y/N) " RESULT
	if [ "$RESULT" == "y" ]; then
		if [ "$SETUPDB" != "1" ]; then
			OKAY="N"
			while [ "$OKAY" != "y" ]; do
				read -p "Enter the database name [$DBNAME_DEFAULT]: " DBNAME
				if [ "$DBNAME" == "" ]; then
					echo "Using default"
					DBNAME="$DBNAME_DEFAULT"
				fi
		
				read -p "Enter the name of the database user [$DBUSER_DEFAULT]: " DBUSER
				if [ "$DBUSER" == "" ]; then
					echo "Using default"
					DBUSER="$DBUSER_DEFAULT"
				fi
		
				read -p "Enter the password for user $DBUSER [$DBPASS_DEFAULT]: " DBPASS
				if [ "$DBPASS" == "" ]; then
					echo "Using default"
					DBPASS="$DBPASS_DEFAULT"
				fi
		
				echo ""
				echo "Database name: $DBNAME"
				echo "Database user: $DBUSER"
				echo "Database password: $DBPASS"
				echo ""
				read -p "Are the above values correct? (y/N) " OKAY
			done
		fi

		cat config.sample.php |sed  "s/^\$dbname = '.*';/\$dbname = '$DBNAME';/" |sed "s/^\$user = '.*';/\$user = '$DBUSER';/" |sed "s/\$password = '.*';/\$password = '$DBPASS';/" > /var/www/config.php
	else
		echo "Leaving existing config file"
	fi

	echo "Enabling the budgetizer host"
	echo ""
	a2dissite default
	a2ensite budgetizer
	/etc/init.d/apache2 restart

	SETUPWEB=1
else
	echo "Skipping webserver setup"
	SETUPWEB=0
fi

echo "Done budgetizer installation"

if [ "$SETUPPACKAGES" == "1" ]; then
	echo " +   The required packages were installed/updated"
else
	echo " -   The required packages were NOT installed/updated"
fi

if [ "$SETUPDB" == "1" ]; then
	echo " +   The database was setup"
else
	echo " -   The database was NOT setup"
fi

if [ "$SETUPWEB" == "1" ]; then
	echo " +   The web server was setup"
else
	echo " -   The web server was NOT setup"
fi
