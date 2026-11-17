@echo off
setlocal
cd /d "%~dp0"
title SIPERKASA - Pasang dan Jalankan

set "PHP=php"
if exist "C:\xampp\php\php.exe" set "PHP=C:\xampp\php\php.exe"
set "MYSQL=C:\xampp\mysql\bin\mysql.exe"

echo [1/5] Memeriksa PHP ...
"%PHP%" -v >nul 2>nul
if errorlevel 1 (
  echo PHP tidak ditemukan. Pasang XAMPP ^(PHP 8.2 atau lebih baru^) lalu coba lagi.
  pause
  exit /b 1
)
"%PHP%" -r "exit(PHP_VERSION_ID >= 80200 ? 0 : 1);"
if errorlevel 1 (
  echo Versi PHP terlalu lama. SIPERKASA butuh PHP 8.2 atau lebih baru.
  pause
  exit /b 1
)
"%PHP%" -r "echo '      PHP ' . PHP_VERSION . PHP_EOL;"

echo [2/5] Memeriksa kelengkapan folder vendor ...
if not exist "vendor\autoload.php" goto vendor_rusak
"%PHP%" artisan --version >nul 2>nul
if errorlevel 1 goto vendor_rusak
goto vendor_ok
:vendor_rusak
echo Folder vendor tidak ada atau tidak lengkap ^(biasanya karena proses zip atau copy tidak utuh^).
echo Kirim ulang zip dari laptop asal, lalu ekstrak ke folder baru yang kosong.
pause
exit /b 1
:vendor_ok

echo [3/5] Memeriksa MySQL ...
"%MYSQL%" -uroot -e "SELECT 1" >nul 2>nul
if errorlevel 1 (
  echo MySQL belum aktif. Buka XAMPP Control Panel, klik Start pada MySQL, lalu jalankan file ini lagi.
  echo Jika MySQL memakai kata sandi, isi DB_PASSWORD di file .env terlebih dahulu.
  pause
  exit /b 1
)

echo [4/5] Menyiapkan database ...
if not exist ".env" copy ".env.example" ".env" >nul
"%MYSQL%" -uroot -e "CREATE DATABASE IF NOT EXISTS siperkasa CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
"%PHP%" artisan migrate --force
if errorlevel 1 (
  echo Migrasi database gagal. Cek pengaturan DB_* di file .env.
  pause
  exit /b 1
)

set "JUMLAH="
for /f %%i in ('"%MYSQL%" -uroot -N -e "SELECT COUNT(*) FROM siperkasa.employees" 2^>nul') do set "JUMLAH=%%i"
if not defined JUMLAH set "JUMLAH=0"
if "%JUMLAH%"=="0" (
  echo Mengisi data demo ...
  "%PHP%" artisan db:seed --force
)

echo [5/5] Menjalankan aplikasi di http://localhost:8000 ^(tutup jendela ini untuk menghentikan^)
start "" http://localhost:8000
"%PHP%" artisan serve
pause
