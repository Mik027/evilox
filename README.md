# Evilox Scraper

A scraper for the Evilox website.
The Python file downloads the media of the day and updates a medias.json file with the information attached to the media.
The Dockerfile allows the scraper to run once a day at 00:01, and provides access to a web interface for browsing through the archived content.

General :\
Assets folder with logo and favicon not include.

Python file :\
Keep FORCE_WRITE to False in production mode

Dockerfile :\
Default SCRAP_RESULTS_PATH : /app/scrap-results\
Default assigned PORT :  4000\
