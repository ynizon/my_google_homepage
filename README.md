# Google Homepage with your Photos Library 

Having google home page with your pictures.

Base on the Google photos library project...

This directory contains some samples to help you get started with the PHP
client library for the [Google Photos Library API](https://developers.google.com/photos).

## Get started with the samples

. Download the source code for the sample by either cloning this repository and branch 
(for example `git clone https://github.com/google/php-photoslibrary.git -b samples`) 
or by [downloading a compressed tarball](../../blob/master/README.md#downloading-a-compressed-tarball).
. Set up your Google Developers project, enable the Google Photos Library API,
   and create credentials for a **web application**. 
    
   See [Setting up your OAuth2 credentials](../../blob/master/README.md#Setting-up-your-OAuth2-credentials).
. Download the OAuth configuration as a JSON file and copy it to the root of the directory with the name `client_secret.json`. 
. Install all dependencies through composer by running `composer install`.
. Check that your webserver fulfills [all requirements](../README.md#requirements-and-preparation),
   in particular the version of PHP and all required modules.
. Configure your web server to serve the `src/` directory.
. In the file `photoslibrary-sample.ini`, change the URLs where the samples are accessible. The URLs
   listed here must be included in the OAuth configuration in the developer console as 
   *Authorised redirect URIs*. 
