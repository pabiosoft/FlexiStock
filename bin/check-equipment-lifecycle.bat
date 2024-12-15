@echo off
cd /d %~dp0\..
php bin/console app:check-equipment-lifecycle --env=prod --no-debug >> var/log/equipment-lifecycle-check.log 2>&1
