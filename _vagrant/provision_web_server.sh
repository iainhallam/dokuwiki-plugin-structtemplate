#!/bin/bash

# Install web server

sudo apt-get --assume-yes --quiet update
sudo apt-get --assume-yes --quiet install \
    libapache2-mod-php \
    php \
    php-cli \
    php-gd \
    php-intl \
    php-mysql \
    php-sqlite3 \
    php-xml
systemctl is-enabled --quiet apache2 || sudo systemctl enable apache2
systemctl is-active --quiet apache2 || sudo systemctl restart apache2
[[ -e /var/www/html/index.html ]] && sudo mv /var/www/html/index.html /var/www/html/apache.index.html
exit 0
