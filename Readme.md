# [AutoSitemap](https://www.mediawiki.org/wiki/Extension:AutoSitemap)
## Description
MediaWiki extension that automatically builds a sitemap.xml file every time a page is created, edited or deleted. A sitemap file helps search engines to observe and focus on a web sites page content. 
This along with a robots.txt helps to direct what you want and don't want search engines to index.

## Install
Currently this project does not have matching release tags that syncs up with the Mediawiki version. Meaning, just simple clone master.
1. Download the latest snapshot
    1. ```cd /path/to/mediawiki/extensions```
    2. ```git clone https://github.com/dolfinus/AutoSitemap.git```
2. Set Ownership of extension directory as needed
    1. ``chown -R apache:apache /path/to/mediawiki/extensions/AutoSitemap```
    2.  **OR**
    3. ``chown -R nginx:nginx /path/to/mediawiki/extensions/AutoSitemap```
3. Add extension load statement to the Mediawiki configuration [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php).<br>
    ```php
    wfLoadExtension( 'AutoSitemap' );
    ```

## Configure
These are all optional configurations. If they are not set the default values will kick in. All of these options are syntax added to the Mediawiki configuration [LocalSettings.php](https://www.mediawiki.org/wiki/Manual:LocalSettings.php) after the **wfLoadExtension( 'AutoSitmap' );**.

### Filename
You can set the filename of sitemap by setting: (Default = "sitemap.xml")

```php
$wgAutoSitemap["filename"] = "sitemap.xml";
```

### Setting Base URL
By default all URLs generated for the sitemap file use the **$wgCanonicalServer** or **$wgServer** variables as the domain prefix for your page links. 
The extension will set the **$wgAutoSitemap["server"]** to your value if exists in LocalSettings.php. 
If the value is not set in the LocalSettings.php then it will use the value is of **$wgCanonicalServer**. 
If **$wgCanonicalServer** is not found it will set the value to **$wgServer**. 

If you want all of your page links to be prefixed with a different URL then use the following parameter: 
 
```php
$wgAutoSitemap["server"] = "https://my-site.com";
```
### Change Default Search Engines That are Notified
You can notify web sites you want about the update of sitemap. Just write all notify urls as array:

```php
$wgAutoSitemap["notify"] = [
    'https://www.google.com/webmasters/sitemaps/ping?sitemap=https://my-site.com/sitemap.xml',
    'https://www.bing.com/webmaster/ping.aspx?sitemap=https://my-site.com/sitemap.xml',
];
```
The two above search engines are currently the defaults (Google and Bing). 
With the URL replaced with the resulting value of **$wgAutoSitemap["server"]**.
So, if all you require is Google and Bing notifications; then there is no need to change/overwrite this value. 

### Disable Search Engine Notifications
Sometimes web server host provider does not allow the fopen command to call urls **allow_url_fopen=false** in the php.ini configuration. 
If you can't or don't want to use notification, set this setting to empty array.

```php
$wgAutoSitemap["notify"] = [ ];
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

## Troubleshooting
* To trigger the extension to generate for the first time or re-generate the sitemap.xml file simply make a small change on a page such as adding a space or carriage return and save changes from your wiki.
* Remove all Optional parameters and see if problem solved.
* Temporarily enable Debug options in the LocalSettings.php including writing to log. Then reproduce the issue. Finally search log or read displayed error messages for clues.
    ```php
    // Debug
    $wgShowExceptionDetails = true;
    $wgShowSQLErrors = true;
    $wgDebugDumpSql = true;
    $wgShowDBErrorBacktrace = true;
    $wgDebugLogFile = "/var/log/mediawiki.log";
    ```
* For Nginx 1.x and php-fpm 7.0 on Linux; review their logs
    * ```less /var/log/php-fpm/7.0/www-error.log```
    * ```less /var/log/nginx/error.log```
    * ```less /var/log/nginx/access.log```

# See also
* [Very old original extension](https://www.mediawiki.org/wiki/Extension:ManualSitemap)
* [Wikipedia about sitemaps](https://en.wikipedia.org/wiki/Sitemaps)
* [Google about sitemaps](https://support.google.com/webmasters/answer/156184)
