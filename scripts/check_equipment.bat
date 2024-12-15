@echo off
echo [%date% %time%] Starting equipment lifecycle check...

:: Set working directory
cd /d c:\wamp64\www\FlexiStock

:: Create logs directory if it doesn't exist
if not exist "logs" mkdir logs

:: Run the command and log output
php bin/console app:check-equipment-lifecycle >> logs\equipment_check.log 2>&1

:: Check if the command was successful
if %errorlevel% equ 0 (
    echo [%date% %time%] Equipment check completed successfully >> logs\equipment_check.log
) else (
    echo [%date% %time%] Error: Equipment check failed with code %errorlevel% >> logs\equipment_check.log
)

exit /b %errorlevel%
