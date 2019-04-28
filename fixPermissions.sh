#!/bin/bash

sudo usermod -a -G www-data $USER
sudo chown -R $USER:www-data .
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
