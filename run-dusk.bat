@echo off
REM Start PHP development server
start "ParkMate Dev Server" cmd /k php artisan serve --port=8000

REM Wait a few seconds for server to start
timeout /t 3

REM Run Dusk tests
php artisan dusk --config=phpunit.dusk.xml

REM Keep window open
pause
