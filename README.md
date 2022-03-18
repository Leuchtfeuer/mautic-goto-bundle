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
* Mautic 4.x
* Command line access

## Preparations
* If you have preexisting data: BACKUP now! There is curently no migration.

* Verify existing status, "nothing to update" should show up.

      cd [path-to-your-mautic]
      sudo -u www-data php bin/console doctrine:schema:update --force
  This should give you "Nothing to update".
  
* Remove the existing plugin files and clear cache

      sudo -u www-data php bin/console cache:clear
      mv plugins/MauticCitrixBundle ~/MauticCitrixBundle.`date +%Y%m%d_%H%M%S`
    
## Installation
* Download the plugin, say to you home directory, e.g. using wget, and prepare it
  
      cd ~
      wget https://github.com/Leuchtfeuer/mautic-goto-bundle/archive/master.zip
      unzip mautic-goto-bundle-master.zip
      mv mautic-goto-bundle-master MauticGoToBundle

* copy plugin to the Mautic installation

      cd [path-to-your-mautic]
      cp -rp ~/MauticGoToBundle plugins/MauticGoToBundle
      chown -R www-data:www-data plugins/MauticGoToBundle   [assuming that your web server uses the "www-data" account]
      
* Create symlink (needed due to hard reference in core)

      mkdir -p plugins/MauticCitrixBundle/Helper/
      cd plugins/MauticCitrixBundle/Helper/
      ln -s ../../MauticGoToBundle/Helper/CitrixHelper.php .
      cd -
      
* Cleanup (the hard way :)

      rm -rf var/cache/*
      sudo -u www-data php bin/console cache:clear
      sudo -u www-data php  bin/console doctrine:schema:update --force
      
            
* In the Browser, go to "Settings" -> "Plugins" in the Mautic-Backend, klick on "Install/Update Plugins". The various "GoTo" cards appear in the Plugin list.
* Open the desired plugin (e.g. GoToWebinar) and write down the "Callback URL" from the grey box
    
## Authorization in GoTo Dev Account
* Go to https://developer.logmeininc.com/clients - using your main account in GoToWebinar (not just an organizer account!)
* From there, create a OAuth token to use for your Mautic, using the following steps:
* The client name/description can be chosen freely
* In "Forwarding URL", enter the "Callback URL" that you wrote down above (from the Mautic Plugin settings)
* In the "Permissions" setp, give rights for the desired apps e.g. GoToMeeting/Webinar
* At the end of the process, you will receive the Client ID and Client Secret. Make sure to store the Secret to a secure place immediately, it will not be displayed to you again.

## Apply Authorization
* Paste Client ID and Client Secret into the plugin settings in Mautic
* Now click "Authorize App", log in to GoToWebinar (if requested), and confirm

## Set up Syncing
* Try a first manual Sync: 

      cd [path-to-your-mautic]
      sudo -u www-data php  bin/console mautic:goto:sync

* Add Cron job for the syncing:

      [cron schedule settings] www-data php [path-to-your-mautic]/bin/console mautic:goto:sync

We suggest to do the sync every 15 minutes.
If you sync too frequently, you may run out of API calls on the GoTo side (number of allowed API calls can be increased, though)

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

## Known Issues / Missing Features

* If you're mapping fields in the Form-Action, it won't take the mapped fields to register the Lead at GoTo, it'll register the fields which will get persisted in the Lead-DB
* In the Form-Action you'll be able to select distinct webinars. This is useless because you want to register the Contact at the Webinar which the contact has chosen.
* Error Messages are getting displayed without formatting in the Form-Action
* You're not able to map DB-Fields to GoTo-Fields in the Campaign-Action

## API-Requests
For every ProductType k (Like Meeting-Integration, Assist-Integration, ...) there'll be 2\*n Requests for n-Events (e.g. a meeting, a webinar or a Sessions) happening.
For Every Event there'll m Requests for m-Registrants and o Requests for o-Attendees. So ~ *n\*(2\*k+m\*o)*
