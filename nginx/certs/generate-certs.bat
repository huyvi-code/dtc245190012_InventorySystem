@echo off
REM Script tạo chứng chỉ SSL tự ký cho Inventory System (Windows)
set DIR=%~dp0
echo Dang tao chung chi SSL tu ky tai %DIR%...

openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout "%DIR%server.key" -out "%DIR%server.crt" -config "%DIR%openssl.cnf"

if %ERRORLEVEL% EQU 0 (
    echo Tao chung chi SSL thanh cong!
    echo  - Key: %DIR%server.key
    echo  - Cert: %DIR%server.crt
) else (
    echo Co loi xay ra khi tao chung chi SSL.
)
