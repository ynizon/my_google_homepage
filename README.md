## My Homepage

My homepage is a web application to give you a default homepage on your browser with the list of your events,
and a new pictures of your family each time you refresh.

## Installation
- rename .env.example to .env 
- composer install
- create a database/database.sqlite empty file
- php artisan migrate

On your google cloud platform, create a new application and enable API for Google Photos and Calendar.
Go your authentification, then store credentials here:
- storage/app/google-calendar/client_secret.json (for pictures)
- storage/app/google-calendar/service-account-credentials.json (for events)

- Add it on your browser with this extension:
  Fast New Tab Redirect for chrome - https://chrome.google.com/webstore/detail/fast-new-tab-redirect/ohnfdmfkceojnmepofncbddpdicdjcoi

## Custom

You can change shortcuts in the header in resources/views/favorites.php

## Informations

Token for picture media library give an access for 1 hour (service account not work).
Pictures are duplicate into storage/app/pictures directory when you open the webpage  
(wait a little to synchronize pictures the first time).
Events API can have a service account, so you need to add the client_email of your service account file on your calendar.
Gmail readonly is require to get a counter of unread email.

## Screenshot
<img src="/public/screenshot.png">
