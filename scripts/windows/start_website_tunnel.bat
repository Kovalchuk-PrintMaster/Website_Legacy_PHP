\
@echo off
chcp 65001 >nul
title ForPrint Website Local Tunnel

echo [INFO] SSH-тунель до s01 для Website Legacy PHP...
echo [INFO] local 8098 -> s01 127.0.0.1:8098

start "website_legacy_php_tunnel" cmd /k "ssh -N -L 8098:127.0.0.1:8098 s01"

timeout /t 3 /nobreak >nul

echo [INFO] Відкриваю сайт...
start "" "http://127.0.0.1:8098/"

exit
