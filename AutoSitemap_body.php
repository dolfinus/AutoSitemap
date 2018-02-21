<?php

# Special:AutoSitemap MediaWiki extension
# Version 1.1
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
#1.0: Rewrited extension for automatic sitemap generation
#1.1: Upgrade to MediaWiki 1.25, code review

if ( !defined( 'MEDIAWIKI' ) ) {
    die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}


global $wgAutoSitemap, $wgServer, $wgServer, $wgCanonicalServer, $wgScriptPath;
if (!isset($wgAutoSitemap["filename"]          )) $wgAutoSitemap["filename"]           = "sitemap.xml";
if (!isset($wgAutoSitemap["server"]            )) $wgAutoSitemap["server"]             = isset($wgCanonicalServer) ? $wgCanonicalServer : $wgServer;
if (!isset($wgAutoSitemap["notify"]            )) $wgAutoSitemap["notify"]             = [
                                                                                            'https://www.google.com/webmasters/sitemaps/ping?sitemap='.$wgAutoSitemap["server"].$wgScriptPath.'/'.$wgAutoSitemap["filename"],
                                                                                            'https://www.bing.com/webmaster/ping.aspx?sitemap='.$wgAutoSitemap["server"].$wgScriptPath.'/'.$wgAutoSitemap["filename"],
                                                                                            'https://blogs.yandex.ru/pings/?status=success&url='.$wgAutoSitemap["server"].$wgScriptPath.'/'.$wgAutoSitemap["filename"],
                                                                                         ];

if (!isset($wgAutoSitemap["exclude_namespaces"])) $wgAutoSitemap["exclude_namespaces"] = [
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

if (!isset($wgAutoSitemap["exclude_pages"]    )) $wgAutoSitemap["exclude_pages"]       = [];
if (!isset($wgAutoSitemap["freq"]             )) $wgAutoSitemap["freq"]                = "daily";

if (!isset($wgAutoSitemap["header"]           )) $wgAutoSitemap["header"]              =
'<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="'.$wgAutoSitemap["server"].$wgScriptPath.'/'.'extensions/AutoSitemap/sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';


if (!isset($wgAutoSitemap["footer"]           )) $wgAutoSitemap["footer"]              =
"\n</urlset>";

$wgAutoSitemap["file_handle"]='';
$wgAutoSitemap["file_exists"]='';

$wgAutoSitemap["count"]=0;
$wgAutoSitemap["cursor_pos"]=0;

class AutoSitemap {

    static public function writeSitemap() {
        global $wgAutoSitemap;

        $wgAutoSitemap["count"]       = 0;
        $wgAutoSitemap["cursor_pos"]  = 0;
        $wgAutoSitemap["file_exists"] = file_exists ( $wgAutoSitemap["filename"] ) ;
        $wgAutoSitemap["file_handle"] = fopen( $wgAutoSitemap["filename"], 'w' ) or die( 'Cannot write to '.$wgAutoSitemap["filename"].'.' );

        self::utf8_write( $wgAutoSitemap["file_handle"] ,$wgAutoSitemap["header"]);

        $dbr = wfGetDB( DB_SLAVE );
        $res = $dbr->query(self::getSQL());

        $wgAutoSitemap["count"] = $dbr->numRows($res);

        while($row = $dbr->fetchObject( $res )) {
            self::formatResult($row );
        }

        self::utf8_write( $wgAutoSitemap["file_handle"] , $wgAutoSitemap["footer"] ) ;

        fclose( $wgAutoSitemap["file_handle"] );
        self::notifySitemap();
    }

    static public function getSQL() {
        global $wgAutoSitemap;

        $dbr = wfGetDB( DB_SLAVE );
        $page = $dbr->tableName( 'page' );
        $revision = $dbr->tableName( 'revision' );

        $sql='SELECT "Popularpages" AS type,
                     page_id AS id,
                     page_namespace AS namespace,
                     page_title AS title,
                     ( MAX( rev_timestamp ) ) AS last_modification,
                     rev_timestamp AS value
              FROM
                     '.$page.',
                     '.$revision.'
              WHERE
                     page_is_redirect = 0
              AND    rev_page = page_id
              ';

        if(is_array($wgAutoSitemap["exclude_namespaces"])) {
            if (count($wgAutoSitemap["exclude_namespaces"]) > 0 ) {
                $sql.='AND page_namespace NOT IN ('.implode(",", $wgAutoSitemap["exclude_namespaces"]). ")\n";
            }
        }

        if (is_array($wgAutoSitemap["exclude_pages"]) ) {
            if (count($wgAutoSitemap["exclude_pages"]) > 0 ) {
                $sql.="AND page_title NOT IN ('" .implode("','", $wgAutoSitemap["exclude_pages"]). "')\n";
            }
        }

        $sql.='GROUP BY page_id';

        return $sql;
    }


    static public function getPriority() {
        global $wgAutoSitemap;
        return ($wgAutoSitemap["cursor_pos"] / $wgAutoSitemap["count"]);
    }

    static public function getChangeFreq( $page_id ) {
        global $wgAutoSitemap;

        if ($wgAutoSitemap["freq"] !== "adjust" ) return $wgAutoSitemap["freq"];


        $dbr =& wfGetDB( DB_SLAVE );

        $revision = $dbr->tableName( 'revision' );

        $sql = "SELECT
        MIN(rev_timestamp) AS creation_timestamp,
        COUNT(rev_timestamp) AS revision_count
        FROM $revision WHERE rev_page = $page_id";

        $res = $dbr->query( $sql );
        $count = $dbr->numRows( $res );

        if( $count < 1 ) {
            return "daily";
        } else {
            $item1 =( $dbr->fetchObject( $res ) );
            $cur = time() ;
            $first = wfTimestamp( TS_UNIX, $item1->creation_timestamp );

            $diff = ($cur - $first) / $item1->revision_count ;
            switch( true ) {
                case $diff < 3600: return "hourly";
                case $diff < 24*3600: return "daily";
                case $diff < 7*24*3600: return "weekly";
                case $diff < 30.33*24*3600: return "monthly";
                case $diff < 365.25*24*3600: return "yearly";
                default: return "daily";
            }
        }
    }

    static public function formatResult($result ) {
        global $wgAutoSitemap, $wgLang, $wgContLang, $wgServer;

        $title = Title::makeTitle( $result->namespace, $result->title );
        $link = Linker::linkKnown( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );

        $url = $title->getLocalURL();

        $last_modification = gmdate( "Y-m-d\TH:i:s\Z", wfTimestamp( TS_UNIX, $result->last_modification ) );

        self::addURL( $wgAutoSitemap["server"], $url, $last_modification, $result->id );

        ++$wgAutoSitemap["cursor_pos"];

        return;
    }

    static public function addURL( $base, $url, $last_modification, $page_id ) {
        global $wgAutoSitemap;

        $result="  <url>\n    <loc>".str_replace(array('(',')'),array('%28','%29'),$base.$url)."</loc>\n    <priority>".round(self::getPriority(),1)."</priority>\n    <lastmod>$last_modification</lastmod>\n    <changefreq>".(self::getChangeFreq($page_id))."</changefreq>\n  </url>\n";
        self::utf8_write( $wgAutoSitemap["file_handle"], $result );
    }

    static public function utf8_write( $handle, $data ) {
        fwrite( $handle, utf8_encode( $data ) ) ;
    }

    static public function notifySitemap() {
        global $wgAutoSitemap;
        $notify = $wgAutoSitemap["notify"];
        if(is_array($notify)) {
            foreach ($notify as $item) {
                $handle = fopen($item, 'r');
                if ( $handle)
                    fclose($handle);
            }
        }
    }
}
