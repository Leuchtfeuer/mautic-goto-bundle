# GoTo (ex Citrix) integration for Mautic with GoToWebinar & Co.

We have given the GoTo plugin (for GoToWebinar / GoToMeeting / GoToAssist / GoToTraining - formerly known as "Citrix plugin") a complete overhaul. It now comes with a bunch of new and previously missing features; highlights include
- Editor can offer selected webinars in form, rather than "all current"
- Support for recurring webinars (called "sessions" in GoToWebinar)
- Honours events from multiple organizer accounts in GoToWebinar
- Optionally, allow user to multi select in dropdown
- Optionally, Display more than just the title in form dropdown (e.g. date)
- Optionally, display selected metadata of webinar(s) above form (e.g. title, description, duration, ...)
- Automatically update webinar metadata from GoToWebinar
- Caching of GoTo data - thus no wait time, no more "API calls exceeded" issues

## Requirements
* Mautic Version 2.16.x (Mautic 3 support expected in 08/2020)

* Command line access

## Preparations
* If you have preexisting data: There is no migration as of today! You need to backup any data that you may still need, and to manually clean the database table plugin_citrix_events.

* Verify existing status, "nothing to update" should show up.

      cd [path-to-your-mautic]
      sudo -u www-data php app/console doctrine:schema:update --dump-sql
      
* Remove the existing plugin files and clear cache (the hard way!)

      cd [path-to-your-mautic]
      mv plugins/MauticCitrixBundle ~/MauticCitrixBundle.old
      rm -rf app/cache/prod/*
    
## Installation
* Download the plugin, unpack the file, rename the directory and move it to the plugin directory of the Mautic installation... and clear cache.

      wget https://github.com/Leuchtfeuer/mautic-goto-bundle/archive/master.zip
      unzip mautic-goto-bundle-master.zip
      chown -R www-data:www-data mautic-goto-bundle-master   [assuming that your web server uses the "www-data" account]
      mv mautic-goto-bundle-master [path-to-mautic]/plugins/MauticGoToBundle
      cd [path-to-your-mautic]
      rm -rf app/cache/prod/*
      sudo -u www-data php  app/console doctrine:schema:update --force
      
            
* In the Browser, go to "Settings" -> "Plugins" in the Mautic-Backend, klick on "Install/Update Plugins". The various "GoTo" cards appear in the Plugin list.
* Open the desired plugin (e.g. GoToWebinar) and write down the "Callback URL" from the grey box
    
## Authorization in GoTo Dev Account
* Go to https://developer.logmeininc.com/clients - using your main account in GoToWebinar (not just an organizer account!)
* From there, create a OAuth token to use for your Mautic, using the following steps:
* The client name/description can be chosen freely
* In "Forwarding URL", enter the "Callback URL" that you wrote down above (from the Mautic Plugin settings)
* In the "Permissions" tab, give rights for the desired apps e.g. GoToMeeting/Webinar
* At the end of the process, you will receive the Client ID and Client Secret. Make sure to store the Secret to a secure place immediately, it will not be displayed to you again.

## Apply Authorization
* Paste Client ID and Client Secret into the plugin settings in Mautic
* Now click "Authorize App"

## Set up Syncing
* Finally, add Cron job for the syncing:

      [cron schedule settings] www-data php [path-to-your-mautic]/app/console mautic:goto:sync

We suggest to do the sync every 15 minutes. If you sync too frequently, you may run out of API calls on the GoTo side (number of allowed API calls can be increased, though)

## Using the plugin

Just like with the old plugin, you can create a form with field type "Upcoming Webinars" (or Meeting, ....) - but this is now much more powerful, see feature list above.
* You will find all the options in the "properties" tab of the form field.
* If you choose to display selected metadata of webinar(s) above form, you can also control the styling here.

All the other options are unchanged, thus see existing docs such as https://docs.mautic.org/en/plugins/citrix:
* Segment filters
* Form actions
* Campaign conditions and actions
* Contact properties
* "Join Webinar" token in emails

