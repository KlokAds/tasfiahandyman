@echo off
title Tasfia blog rewrite
cd /d C:\Websites\tasfiahandyman
echo Blog rewrite: Tasfia Door Repair, Tasfia Engineering, Tasfia Plumbing.
echo Sent as one batch at half price. Results come within 24 hours, usually about an hour.
echo It stops by itself before 270 dollars in total for all sites (95 were spent before).
echo You can close this window while it waits. Run it again to collect the results.
echo.
"C:\Python314\python.exe" tools\rewrite_blogs.py --sites door,engineering,plumbing --batch --budget 270
echo.
echo ---- Finished or stopped. Read the lines above. ----
cmd /k
