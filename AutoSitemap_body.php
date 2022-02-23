<?php

# Special:AutoSitemap MediaWiki extension
# Version 1.4
#
# Copyright  2006 Fran&ccedil;ois Boutines-Vignard, 2008-2012 Jehy, 2016-2017 Dolfinus.
#
# A special page to generate Google Sitemap XML files.
# see http://www.google.com/schemas/sitemap/0.84/sitemap.xsd for details.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along
# with this program; if not, write to the Free Software Foundation, Inc.,
# 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# http://www.gnu.org/copyleft/gpl.html
#
# Revisions:
#GoogleSitemap
# 0.0.2: date format correction, lighter markup. (2006/09/15)
# 0.0.3: added 'priority' and 'changefreq' tags management in the 'Options' form. (2006/09/16)
# 0.0.4: Unicode support, gmdate format, exponential and quadratic priorities. (2006/09/17)
# 0.0.5: Possibility to sort by last page revision. (2006/09/19)

#ManualSitemap
# 0.1: Jehy took maintenance. Bugfix, new options (2008/11/12)
# 0.2: Thomas added functions for excluding pages, warning if notify fails and setting of servers base url (2009/04/08)
# 1.0: Script rewritten, allowing easier usage (2009/11/30)
# 1.1: Added discussion pages exclusion option
# 1.2: Fixed compatibility issues for MW 1.19.2

#AutoSitemap
#1.0: Rewrote extension for automatic sitemap generation
#1.1: Upgrade to MediaWiki 1.25, code review
#1.2: Search engines notifications improvements & fixes
#1.2.1: Write sitemap to tempfile and then rename it
#1.2.2: Randomize temp file name
#1.3: Set priority for pages or namespaces
#1.4: MW 1.34 support
#1.4.1: Fix MW 1.34 support

if (!defined('MEDIAWIKI')) {
    die('This file is a MediaWiki extension, it is not a valid entry point');
}

global $wgAutoSitemap, $wgServer, $wgCanonicalServer, $wgScriptPath;
if (!isset($wgAutoSitemap["filename"]          )) $wgAutoSitemap["filename"]           = "sitemap.xml";
if (!isset($wgAutoSitemap["server"]            )) $wgAutoSitemap["server"]             = isset($wgCanonicalServer) ? $wgCanonicalServer : $wgServer;
if (!isset($wgAutoSitemap["notify"]            )) $wgAutoSitemap["notify"]             = [
                                                                                            'https://www.google.com/webmasters/sitemaps/ping?sitemap='.$wgAutoSitemap["server"].$wgScriptPath.'/'.$wgAutoSitemap["filename"],
                                                                                            'https://www.bing.com/webmaster/ping.aspx?sitemap='.$wgAutoSitemap["server"].$wgScriptPath.'/'.$wgAutoSitemap["filename"],
                                                                                         ];

if (!isset($wgAutoSitemap["exclude_namespaces"])) $wgAutoSitemap["exclude_namespaces"] = [
                                                                                            NS_TALK,
                                                                                            NS_USER,
                                                                                            NS_USER_TALK,
                                                                                            NS_PROJECT_TALK,
                                                                                            NS_FILE_TALK,
                                                                                            NS_MEDIAWIKI,
                                                                                            NS_MEDIAWIKI_TALK,
                                                                                            NS_TEMPLATE,
                                                                                            NS_TEMPLATE_TALK,
                                                                                            NS_HELP,
                                                                                            NS_HELP_TALK,
                                                                                            NS_CATEGORY_TALK
                                                                                         ];

if (!isset($wgAutoSitemap["exclude_pages"]    )) $wgAutoSitemap["exclude_pages"]       = [];
if (!isset($wgAutoSitemap["priority"]         )) $wgAutoSitemap["priority"]            = [];
if (!isset($wgAutoSitemap["freq"]             )) $wgAutoSitemap["freq"]                = "daily";

if (!isset($wgAutoSitemap["header"]           )) $wgAutoSitemap["header"]              =
'<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="'.$wgAutoSitemap["server"].$wgScriptPath.'/'.'extensions/AutoSitemap/sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
';

if (!isset($wgAutoSitemap["footer"]           )) $wgAutoSitemap["footer"]              = "\n</urlset>";

class AutoSitemap {

    static public function writeSitemap() {
        global $wgAutoSitemap;

        $server       = $wgAutoSitemap["server"];
        $filename     = $wgAutoSitemap["filename"];
        $tmp_filename = $filename.'.tmp'.bin2hex(random_bytes(16)).'.tmp';

        $file_handle = fopen($tmp_filename, 'w') or die('Cannot write to '.$tmp_filename.'.');

        $dbr = wfGetDB(DB_REPLICA);
        $res = $dbr->query(self::getSQL());

        $count = $dbr->numRows($res);
        $pos   = 0;

        $data = $wgAutoSitemap["header"];
        while($row = $dbr->fetchObject($res)) {
            $data .= self::formatResult($server, $row, $pos, $count);
            ++$pos;
        }
        $data .= $wgAutoSitemap["footer"];

        fwrite($file_handle, utf8_encode($data));
        fclose($file_handle);
        rename($tmp_filename, $filename);

        self::notifySitemap();
    }

    static function getSQL() {
        global $wgAutoSitemap;

        $dbr = wfGetDB(DB_REPLICA);
        $page = $dbr->tableName('page');
        $revision = $dbr->tableName('revision');

        $sql='SELECT "Popularpages" AS type,
                     page_id AS id,
                     page_namespace AS namespace,
                     page_title AS title,
                     (MAX(rev_timestamp)) AS last_modification,
                     rev_timestamp AS value
              FROM
                     '.$page.',
                     '.$revision.'
              WHERE
                     page_is_redirect = 0
              AND    rev_page = page_id
              ';

        if(is_array($wgAutoSitemap["exclude_namespaces"])) {
            if (count($wgAutoSitemap["exclude_namespaces"]) > 0) {
                $sql.='AND page_namespace NOT IN ('.implode(",", $wgAutoSitemap["exclude_namespaces"]). ")\n";
            }
        }

        if (is_array($wgAutoSitemap["exclude_pages"])) {
            if (count($wgAutoSitemap["exclude_pages"]) > 0) {
                $sql.='AND page_title NOT IN (\'' .implode("','", $wgAutoSitemap["exclude_pages"]). "')\n";
            }
        }

        $sql .= 'GROUP BY page_id';

        return $sql;
    }


    static function getPriority($title, $pos, $count) {
        global $wgAutoSitemap;
        $priority = $wgAutoSitemap["priority"];
        if (!is_array($priority)) {
            return $priority;
        }
        $namespace = $title->getNamespace();
        if (array_key_exists($namespace, $priority)) {
            return $priority[$namespace];
        }
        $pageName = $title->getPrefixedText();
        if (array_key_exists($pageName, $priority)) {
            return $priority[$pageName];
        }
        return $pos/$count;
    }

    static function getChangeFreq($page_id) {
        global $wgAutoSitemap;

        if ($wgAutoSitemap["freq"] !== "adjust") {
            return $wgAutoSitemap["freq"];
        }


        $dbr = wfGetDB(DB_REPLICA);
        $revision = $dbr->tableName('revision');

        $sql = "SELECT
        MIN(rev_timestamp) AS creation_timestamp,
        COUNT(rev_timestamp) AS revision_count
        FROM $revision WHERE rev_page = $page_id";

        $res   = $dbr->query($sql);
        $count = $dbr->numRows($res);

        if($count < 1) {
            return "daily";
        } else {
            $item1 = $dbr->fetchObject($res);
            $cur = time() ;
            $first = wfTimestamp(TS_UNIX, $item1->creation_timestamp);

            $diff = ($cur - $first) / $item1->revision_count;
            switch(true) {
                case $diff < 3600:           return "hourly";
                case $diff < 24*3600:        return "daily";
                case $diff < 7*24*3600:      return "weekly";
                case $diff < 30.33*24*3600:  return "monthly";
                case $diff < 365.25*24*3600: return "yearly";
                default:                     return "daily";
            }
        }
    }

    static function formatResult($server, $result, $pos, $count) {
        global $wgContLang;

        $title = Title::makeTitle($result->namespace, $result->title);
        $url   = $title->getLocalURL();

        $priority = self::getPriority($title, $pos, $count);
        $last_modification = gmdate("Y-m-d\TH:i:s\Z", wfTimestamp(TS_UNIX, $result->last_modification));
        $freq = self::getChangeFreq($result->id);

        return self::prepareLine($server.$url, $priority, $last_modification, $freq);
    }

    static function prepareLine($url, $priority, $last_modification, $freq) {
        return '
  <url>
    <loc>'.self::encodeUrl($url).'</loc>
    <priority>'.str_replace(",", ".", round($priority,1)).'</priority>
    <lastmod>'.$last_modification.'</lastmod>
    <changefreq>'.$freq.'</changefreq>
  </url>';
    }

    static function encodeUrl($url) {
        return str_replace(array('(',')'),array('%28','%29'), $url);
    }

    static public function notifySitemap() {
        global $wgAutoSitemap;
        $notify = $wgAutoSitemap["notify"];
        if(is_array($notify)) {
            foreach ($notify as $item) {
                $handle = fopen($item, 'r');
                if ($handle) {
                    fclose($handle);
                }
            }
        }
    }
}
