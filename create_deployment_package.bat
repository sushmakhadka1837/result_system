@echo off
REM ========================================
REM PEC Result System - Deployment Package Creator
REM ========================================

echo.
echo ====================================
echo  PEC Result System Deployment
echo ====================================
echo.

REM Create deployment folder
set DEPLOY_FOLDER=PEC_Result_System_Deploy
if exist %DEPLOY_FOLDER% rmdir /s /q %DEPLOY_FOLDER%
mkdir %DEPLOY_FOLDER%

echo [1/5] Creating deployment folder...
timeout /t 1 /nobreak >nul

REM Copy all files except excluded ones
echo [2/5] Copying project files...
xcopy /E /I /Y /EXCLUDE:deployment_exclude.txt . %DEPLOY_FOLDER%

REM Copy production config
echo [3/5] Setting up production configuration...
copy /Y db_config.production.php %DEPLOY_FOLDER%\db_config.production.php

REM Create images folder structure
echo [4/5] Creating upload directories...
if not exist %DEPLOY_FOLDER%\images\testimonials mkdir %DEPLOY_FOLDER%\images\testimonials

REM Create zip file (requires 7-Zip or WinRAR)
echo [5/5] Creating deployment package...
if exist "C:\Program Files\7-Zip\7z.exe" (
    "C:\Program Files\7-Zip\7z.exe" a -tzip PEC_Result_System.zip %DEPLOY_FOLDER%\*
    echo.
    echo ====================================
    echo  SUCCESS! Package created:
    echo  PEC_Result_System.zip
    echo ====================================
) else (
    echo.
    echo Please manually zip the folder: %DEPLOY_FOLDER%
    echo Or install 7-Zip from: https://www.7-zip.org/
)

echo.
echo Next Steps:
echo 1. Upload PEC_Result_System.zip to your hosting
echo 2. Extract files in public_html
echo 3. Create database and import result_system.sql
echo 4. Update db_config.php with hosting credentials
echo 5. Read DEPLOYMENT_GUIDE.md for details
echo.

pause
