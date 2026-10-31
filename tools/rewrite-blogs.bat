@echo off
title Handyman blog rewrite
cd /d C:\Websites\tasfiahandyman
echo Blog rewrite: Handyman Service Singapore.
echo It stops by itself before 95 dollars for all sites together.
echo Close this window or press Ctrl+C to stop early. Run it again to continue.
echo.
"C:\Python314\python.exe" tools\rewrite_blogs.py --sites handyman
echo.
echo ---- Finished or stopped. Read the lines above. ----
cmd /k
