# Changelog

## 3.4.2
 - Update uap-core

## 3.4.1
 - Fix a regex delimiter issue with uap-core
 - Bundle regex.php with uap-php

## 3.4.0
 - ADD: device Parsing with brand model and case insensitive testing
 - Use `peer_name` instead of `CN_match` for PHP >=5.6
 - Advertise Gitter chat for support requests
 - Include uap-core as a git submodule

## 3.3.1
 - PSR-4 ready
 - Self repository, less useless files

## 3.3.0
 - Use composer for dependency management
 - Introduce namespaces
 - Removing legacy library dependencies
 - Use PHPUnit for testing
 - Make all tests pass, fix all the remaining bugs
 - Introduce specific result objects
 - Comply with PSR-X coding standards:
   * `UAParser` class is now `UAParser\Parser`
   * Typed result objects: `Parser::parse()` returns `UAParser\Result\Client`, `Client::$os` is a  `UAParser\Result\OperatingSystem` and `Client::$device` is a `UAParser\Result\Device`
   * `toString()` and `toVersion()` are now methods
   * Properties now use camelCase, not underscore_case.
 - Use Travis for CI
 - Update README
 - Port command line tool to Symfony CLI
 - Secure updating: SSL certificate verification, hashing, try to do atomic updates
 - Restore fetching YAML file only (without generating JSON)

## 2.1.1
 - FIX: making sure patch minor is being populated correctly when showing a mismatch

## 2.1.0
 - ADD: support for custom regexes.json files (via @pravindahal)
 - FIX: formerly private vars/functions are now protected (via @pravindahal)
 - FIX: command line tool gets 'pretty' option for PHP 5.4 users (via @skyzyx)
 - FIX: refactored the regexes.yaml test suite
 - FIX: now check to see if allow_url_fopen is set to 'On' before trying to download the YAML file from the command line
 - THX: thanks to @pravindahal and @skyzyx for the pull requests

## 2.0.1
 - FIX: renamed uaParser, osParser, & deviceParser to uaParse, osParse, & deviceParse to address a bug with uaParser being recognized as the contruct function for the overall lib
 - FIX: updated the test lib so that device failures are properly formatted

## 2.0.0
 - Summary:
   * the `UAParser` class is now dynamic
   * properties are nested _(e.g. $result->family is now $result->ua->family)_
   * a user agent string is now required when using `parse()`. the auto-magical "use the server provided UA" is no longer supported.
   * `uaParse()`, `osParse()`, and `deviceParse()` are public and can be used to just return those select bits for a given user agent string.
   * the `is*` boolean properties _(e.g. isMobile)_ have been dropped. they now exist as part of the `ua-classifier` project.
 - ADD: toString() converts the version bits and family into a simple string
 - ADD: toVersionString() converts the version bits into a simple string
 - ADD: toFullString() combines the UA and OS family and version bits
 - ADD: "convert" flag for uaparser-cli.php
 - ADD: "pull & save just regexes.yaml" flag for uaparser-cli.php
 - FIX: library is now a dynamic class
 - FIX: attributes are now nested & populated like the other ua-parser libraries (e.g. $result->family is now $result->ua->family)
 - FIX: uaParser(), osParser(), and deviceParser() are now public functions
 - FIX: saves regexes.yaml as JSON for better performance
 - FIX: removed the __DIR__ "fix"
 - FIX: Apache log parsing now returns results when UA or OS families are set to "Other" and the device is listed as a smartphone or generic feature phone
 - FIX: all tabs are now spaces
 - FIX: a UA is now run against all three parsers
 - FIX: renamed $debug var to $log to better reflect what it does
 - DEL: is* boolean attributes (e.g. isMobile) have been removed
 - DEL: will no longer auto-parse $_SERVER['HTTP_USER_AGENT'] if available
 - DEL: tests no longer run against pgts_browser_list.yaml
 - THX: thanks to @rjd22 for the dynamic class code/fix

## 1.5.0
 - ADD: command line interface is now in its own file (via @Synchro)
 - ADD: command line utility now supports parsing an Apache log file & recording the results
 - ADD: command line utility can now parse a supplied user-agent string and push out a simple list or JSON
 - ADD: test suite that uses the ua-parser project's test resources
 - FIX: numerous comment & spacing fixes (via @Synchro & @Krinkle)
 - FIX: remove PHP4 version of spyc (via @Synchro)
 - FIX: remove .svn dirs in spyc (via @lopezdonaque)
 - FIX: notes that the PHP 5.2 fix really was for 5.1 (via @Synchro) (knew this, i was lazy)
 - FIX: lib now returns an object no matter what. now matches other ua-parser libs (via @Krinkle)
 - FIX: checks that $_SERVER attr is set before including it. should be better for command line use. 
 - FIX: family attr now properly set in an edge case
 - FIX: if regexes.yaml picks up bad slashes the PHP lib will account for it (e.g. GoogleTV regex)
 - THX: thanks to @Krinkle and @Synchro for the numerous fixes

## 1.4.5
 - FIX: an embarrassing debug print survived the last edit
 - THX: thanks to @memakeit for dropping the bug report

## 1.4.4
 - FIX: made sure that the regex file is only loaded once if running the library multiple times. performance boost.
 - FIX: added support for identifying various game devices as mobile devices
 - THX: thanks to @rjd22 for pointing out the perf issue

## 1.4.3
 - FIX: added support for patch & family attributes to sort of match the other libraries

## 1.4.2
 - FIX: notice if regexes.yaml is missing parens (e.g. match $1) for device & os names

## 1.4.1
 - FIX: notice when using UAParser from the command line

## 1.4.0
 - ADD: silent mode for the UA::get() method
 - ADD: nobackup mode for the UA::get() method
 - ADD: example of how to do a redirect with ua-parser-php
 - The following were changes to regexes.yaml:
   * ADD: support for Google Earth browser
   * ADD: another regex for Firefox Mobile
   * ADD: support for Firefox Alpha builds
   * ADD: support for Sogou Explorer
   * ADD: support for the Raven for Mac browser
   * ADD: support for WebKit Nightly builds (though slightly pointless)
   * FIX: better pattern matching for the Pale Moon browser

## 1.3.2
 - FIX: addressed false "tablet" classification for opera mobile & mini on android
 - The following were changes to regexes.yaml:
   * ADD: support for Tizen Browser (aka SLP Browser) from Samsung
   * FIX: support for a new look Polaris 8.0 user agent string
   * FIX: modified where the Epiphany Browser check happens

## 1.3.1
 - FIX: now doing some sanity cleaning on the user agent strings
 - FIX: added a smarter default if the user agent isn't recognized at all

## 1.3.0
 - FIX: now points to Tobie's ua-parser project for the latest greatest YAML file
 - FIX: YAML file is now standardized as regexes.yaml instead of user_agents_regex.yaml
 - FIX: minor NOTICE issues resolved for very select UAs

## 1.2.2
 - The following were changes to user_agents_regex.yaml:
   * ADD: support for UC Browser

## 1.2.1
 - The following were changes to user_agents_regex.yaml:
   * ADD: support for android 4 user agents that have a dash in them

## 1.2.0
 - FIX: should be compatible with PHP 5.2
 - FIX: addressed STRICT mode errors
 - FIX: addressed NOTICE for a missing variable
 - FIX: if isTablet is set to true then isMobile is set to false (mobile to me means phone)
 - THX: Thanks to Mike Bond of WVU Libraries for pointing out the 5.2 incompatibility

## 1.1.0
 - FIX: a number of fixes from bryan shelton
 - The following were changes to user_agents_regex.yaml:
   * ADD: support for Chrome Mobile

## 1.0.0
 - just because i don't expect to update this anytime soon and ppl report it's working

## 0.3.1
 - FIX: swapped nil for null in parse()
 - FIX: smarter/saner defaults
 - FIX: now using isset() for family_replacement
 - THX: thanks to bryan shelton for these fixes 

## 0.3.0
 - ADD: can now supply a specific UA to be checked
 - ADD: if the UA contains 'tablet' isTablet is marked true
 - ADD: for certain mobile OSs they report a desktop browser. marking them mobile now.
 - FIX: tablet listing should now be better
 - FIX: the list of mobile browsers was updated
 - FIX: made sure that certain checks won't fail as "false" if a version number was a 0
 - FIX: for the device check, if it returns spider as a result it no longer marks it as mobile
 - FIX: added more mobile browsers to that specific check
 - The following were changes to user_agents_regex.yaml:
   * ADD: symphony, minimo, teleca, semc, up.browser, bunjaloo, jasmine, & brew browsers supported
   * ADD: windows mobile 6.5 os support
   * ADD: amoi, asus, bird, dell, docomo, huawei, i-mate, kyocera, lenovo, lg, microsoft kind,
       motorola, philips, samsung, softbank, & sony ericsson device checks
   * FIX: mobile firefox, opera mobile & mini, obigo, polaris, nokiabrowser, ie mobile,
       android, & mobile safari browser checks
   * FIX: iOS support
   * FIX: htc, android, palm/hp, kindle, ios, generic feature phones & spider device checks

## 0.2.0
 - ADD: added isMobile support
 - ADD: added isTablet support
 - ADD: added isComputer support
 - ADD: added isSpider support

## 0.1.0
 - The following were changes to user_agents_regex.yaml:
   * expanded support for Symbia & Nokia Devices, 
   * cleaned up some slashies, 
   * added Mobile Safari as the family replacement for iOS devices, 
   * better support for longer HTC device names
   * added feature phones to the device check
   * added generic smartphones to the device check
   * added AvantGo to the ua check
   * tweaked a lot of the nokia checks
   * added kindle support to the device section
   * added a generic catch for android devices.
   * added support for blackberry devices
   * changed the blackberry family to 'blackberry webkit' when it's a webkit browser
