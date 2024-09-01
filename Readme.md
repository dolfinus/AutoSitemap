# [AutoSitemap](https://www.mediawiki.org/wiki/Extension:AutoSitemap)
## Description
MediaWiki extension that automatically build sitemap.xml file every time a page is created, edited or deleted. A sitemap file helps search engines to observe and focus on a web sites page content.

### Note

This extension can be used on small MediaWiki instances (up to 10k pages). If your instance is larger than 50k pages, you should use [GenerateSitemap.php](https://www.mediawiki.org/wiki/Manual:GenerateSitemap.php) instead because it does support sitemap index file, can create separated files for each namespace, compress sitemap files and also designed to be run on schedule instead of triggering after each page modification.

## Intall
Download the latest snapshot and extract it to your extensions directory. Then include it in your [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php) file as in the following example:
```php
wfLoadExtension( 'AutoSitemap' );
```

## Configure
There are some optional parameters that changes the sitemap generation. You can set them in your LocalSettings.php.

### Filename
You can set filename of sitemap by setting:
```php
$wgAutoSitemap["filename"] = "sitemap.xml"; //default value
```
### Setting base url
By default all urls in sitemap use $wgCanonicalServer (or $wgServer, if it doesn't set) as domain prefix. If you want to set it to another one, you can change it manually by setting:
```php
$wgAutoSitemap["server"] = "https://your-site.com";
```

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
You can manually specify the recommended frequency with which all addresses will be checked by search engine:
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
### Set page priority
You can manually specify priority for certain pages or namespaces:
```php
$wgAutoSitemap["priority"] = 0.7;
# or
$wgAutoSitemap["priority"][NS_MAIN] = 1;
$wgAutoSitemap["priority"][NS_CATEGORY] = 0.8;
...
# or
$wgAutoSitemap["priority"]['Main page'] = 1;
$wgAutoSitemap["priority"]['Other page'] = 0.8;
...
```
### Rate-limit recreation of the sitemap
For wikis with many pages, generating the sitemap may consume significant resources, so you may not want it to happen too frequently.  With this option, you can specify that the sitemap should only be recreated if it's at least a certain number of seconds old.
```php
$wgAutoSitemap["min_age"] = 3600; // 1 hour, default 0 (no rate-limit)
```

## Use
### Permissions
Your MediaWiki folder should be permitted for write operations (`chmod +w` with `chown apache` or `chown nginx`).

### Htaccess, Nginx
If you want to see a human-readable sitemap, allow read access for sitemap.xsl file in your site config (`.htaccess` file or other).

# See also
* [Very old original extension](https://www.mediawiki.org/wiki/Extension:ManualSitemap)
* [Wikipedia about sitemaps](https://en.wikipedia.org/wiki/Sitemaps)
* [Google about sitemaps](https://support.google.com/webmasters/answer/156184)
