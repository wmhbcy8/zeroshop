$env:HJ_DB_HOST = "192.168.2.6"
$env:HJ_DB_PORT = "3306"
$env:HJ_DB_USERNAME = "root"
$env:HJ_DB_PASSWORD = Read-Host "MySQL password"
$env:HJ_DB_SITE = "huajian_site_10001"

& "$PSScriptRoot\..\tools\php\php.exe" -S 127.0.0.1:8000 -t "$PSScriptRoot\..\server\public" "$PSScriptRoot\..\server\public\index.php"
