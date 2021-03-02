::del *.jpg
::@start "PHP Scripted FFmpeg converter" .\system\php.exe -n -d extension=.\system\php_mbstring.dll -f .\system\gro.php "%~nx0" && exit

::@start "PHP Scripted FFmpeg converter" .\system\php.exe -n -d extension=.\system\php_mbstring.dll -f .\system\gro.php "%~nx0" && pause

::.\system\php.exe -n -d extension=.\system\php_mbstring.dll -f .\system\gro.php "%~nx0" && pause


.\system\php.exe -n -d extension=.\system\php_mbstring.dll -f .\system\SANITISEPHP.php "%~nx0" && pause
type corps.txt > ###BOOKAWESOME.html
SET chaine=COUVERTURE_

for   %%I in (*.PDF) do (
IF EXIST %%~pI%chaine%%%~nI.jpg (
    echo "EXIST"
) ELSE (
   ::i_view32.exe  %%I /convert "%chaine%%%~nI.jpg"  /extract=(%%~nI,jpg)
   c:\convert\i_view32.exe  %%I /convert "%chaine%%%~nI.jpg"  
)
)



for %%a in (*.jpg) do  Echo %%a | findstr /C:"LUT">nul && ( echo.TRUE    ) || (    echo ^<div class="profile-pic"^>^<img data-action="zoom" class=" lazyloaded" data-tags="" data-src="%%a" src="%%a" title="%%a" style="max-width: 199px;"^> ^<div class="edit" style="display: block ; z-index: 11;"^>^<a href="#"^>^<i class="fa fa-1" style="color: white; display: none;" aria-hidden="true"^>^</i^>^</a^>^&nbsp;^&nbsp;^<a href="#"^>^<i class="fa fa-2" style="color: white; display: none;" aria-hidden="true"^>^</i^>^</a^>^</div^>^</div^> >> ###BOOKAWESOME.html)
start "" "###BOOKAWESOME.html"
