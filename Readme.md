# [AutoSitemap](https://www.mediawiki.org/wiki/Extension:AutoSitemap)
## Description
MediaWiki extension that automatically build sitemap.xml file at every page create/edit/delete event. Sitemap file helps search engines to observe your site's pages.

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
### Setting base url
By default all urls in sitemap use $wgCanonicalServer (or $wgServer, if it doesn't set) as domain prefix. If you want to set it to another one or simple doesn't want to set $wgCanonicalServer, you can change it manually by setting:
```php
$wgAutoSitemap["server"] = "https://your-base-url.com";
```

### Search engines notification
You can notify web sites you want about the update of sitemap. Just write all notify urls as array:
```php
$wgAutoSitemap["notify"] = [
    'https://www.google.com/webmasters/sitemaps/ping?sitemap=/sitemap.xml',
    'https://www.bing.com/webmaster/ping.aspx?sitemap=/sitemap.xml',
];
```
Sometimes web hoster does not allow the fopen command to call urls (allow_url_fopen=false). If you can't or doesn't want to use notification, set this setting to empty array.

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
]; //default values

$wgAutoSitemap["exclude_pages"] = ['page title to exclude', 'other one'];
```
### Set page update frequency
You can manually specify the frequency with which all addresses will be checked by search engine:
```php
$wgAutoSitemap["freq"] = "daily"; //default
```
Available values are:
```
hourly
daily
weekly
monthly
yearly
adjust - for automatic determination of frequency based on page edits count
```

## Use
### Permissions
Your MediaWiki folder should be permitted for write operations (chmod +w).

### Htaccess, Nginx
If you want to see a human-readable sitemap, allow read access for sitemap.xsl file in your site config.

# See also
* [Very old original extension](https://www.mediawiki.org/wiki/Extension:ManualSitemap)
* [Wikipedia about sitemaps](https://en.wikipedia.org/wiki/Sitemaps)
* [Google about sitemaps](https://support.google.com/webmasters/answer/156184)
