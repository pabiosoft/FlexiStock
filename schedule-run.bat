@echo off
cd /d "C:\wamp64\www\FlexiStock"
php bin/console schedule:run >> var/log/scheduler.log 2>&1
