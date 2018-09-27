#!/bin/bash

sudo chown -R www-data:www-data .
sudo usermod -a -G www-data $USER
sudo chown -R $USER:www-data ./
sudo find ./ -type f -exec chmod 644 {} \;
sudo find ./ -type d -exec chmod 755 {} \;
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
