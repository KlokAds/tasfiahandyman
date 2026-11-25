@echo off
title Handyman blog rewrite
cd /d C:\Websites\tasfiahandyman
echo Blog rewrite: Handyman Service Singapore.
echo Sent as one batch at half price. Results come within 24 hours, usually within minutes.
echo It stops by itself before 275 dollars in total for all sites (about 220 were spent before).
echo You can close this window while it waits. Run it again to collect the results.
echo.
"C:\Python314\python.exe" tools\rewrite_blogs.py --sites handyman --batch --budget 275
echo.
echo ---- Finished or stopped. Read the lines above. ----
cmd /k
