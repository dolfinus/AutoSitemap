# AutoSitemap
## Description
MediaWiki extension that automatically build sitemap.xml file at every page create/edit/delete event. Sitemap file helps search engines to observe your site's pages
Based on [ManualSitemap extension](https://www.mediawiki.org/wiki/Extension:ManualSitemap).

## Intall
Download the latest snapshot and extract it to your extensions directory. Then include it in your [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php) file as in the following example:
```php
wfLoadExtension( 'AutoSitemap' );
```

## Configure
### Filename
You can set filename of sitemap by setting:
```php
$wgAutoSitemap["filename"] = "sitemap.xml";
```
### Setting base url other then $wgServer
Set all sitemap entries get this as their base url? If not set, used $wgCanonicalServer or $wgServer instead:
```php
$wgAutoSitemap["server"] = "https://your-base-url.com";
```

### Search engines notification
You can notify web sites you want about the update of sitemap:
```php
$wgAutoSitemap["notify"] = [
    'https://www.google.com/webmasters/sitemaps/ping?sitemap=/sitemap.xml',
    'https://www.bing.com/webmaster/ping.aspx?sitemap=/sitemap.xml',
];
```
Sometimes web hoster does not allow the fopen command to call urls (allow_url_fopen=false).

### Exclude types of pages from sitemap
You can exclude namespaces or exact pages from including them to sitemap:
```php
$wgAutoSitemap["exclude_namespaces"] = [
    NS_TALK,
    NS_USER,   
    NS_USER_TALK,
    NS_PROJECT_TALK,
    NS_IMAGE_TALK,
    NS_MEDIAWIKI,   
    NS_MEDIAWIKI_TALK,
    NS_TEMPLATE,
    NS_TEMPLATE_TALK,
    NS_HELP,   
    NS_HELP_TALK,
    NS_CATEGORY_TALK
];      

$wgAutoSitemap["exclude_pages"] = ['page title to exclude', 'other one'];
```
### Set page update frequency
You can manually specify the frequency with which all addresses will be checked:
```php
$wgAutoSitemap["freq"] = "daily";
```
Available values are:
```
hourly
daily
weekly
monthly
yearly
adjust - for automatic determination of frequency based on page edits frequency
```

## Use
### Permissions
Your MediaWiki folder should be permitted for write operations (chmod +w).

# See also
* [Original extension](https://www.mediawiki.org/wiki/Extension:ManualSitemap)
* [Wikipedia about sitemaps](https://en.wikipedia.org/wiki/Sitemaps)
* [Google about sitemaps](https://support.google.com/webmasters/answer/156184)