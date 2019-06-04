#!/bin/bash

sudo usermod -a -G www-data $USER
sudo chgrp -R www-data storage/* bootstrap/cache/*
sudo chmod -R ug+rwx storage/* bootstrap/cache/*
