<?php

#
# Special:AutoSitemap MediaWiki extension
# Version 1.2
#
# Copyright  2006 Fran&ccedil;ois Boutines-Vignard, 2008-2012 Jehy.
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


if ( !defined( 'MEDIAWIKI' ) ) {
	die( 'This file is a MediaWiki extension, it is not a valid entry point' );
}

$wgExtensionCredits['AutoSitemap'][] = array (
	'path'=>__FILE__,
	'name' => 'AutoSitemap',
	'description' => 'Creates a XML Sitemap file automatically.',
	'url' => 'https://github.com/dolfinus/AutoSitemap',
	'author' => 'Dolfinus',
	'descriptionmsg' => 'ausmp-desc',
	'version' => '1.0',
	'license-name' => 'GPL-2.0+'
);

$wgExtensionMessagesFiles['AutoSitemap'] = __DIR__ . '/AutoSitemap.i18n.php';

$wgHooks['PageContentInsertComplete'][] = 'writeSitemap';
$wgHooks['AfterImportPage'][] = 'writeSitemap';
$wgHooks['ArticleDeleteComplete'][] = 'writeSitemap';
$wgHooks['ArticleUndelete'][] = 'writeSitemap';
$wgHooks['TitleMoveComplete'][] = 'writeSitemap';
$wgHooks['ArticleMergeComplete'][] = 'writeSitemap';
$wgHooks['ArticleRollbackComplete'][] = 'writeSitemap';
$wgHooks['UploadComplete'][] = 'writeSitemap';
$wgHooks['ArticleRevisionUndeleted'][] = 'writeSitemap';
$wgHooks['RevisionInsertComplete'][] = 'writeSitemap';
$wgHooks['PageContentSaveComplete'][] = 'writeSitemap';

        $file_name = $GLOBALS['wgAutoSitemap_Sitemap'] ? $GLOBALS['wgAutoSitemap_Sitemap'] : 'sitemap.xml'; // relative to $wgSitename (must be writable)
        /*
         * see http://www.manual.com/schemas/sitemap/0.84/sitemap.xsd for more details
         */
        $DEFAULT_SITEMAP_HEADER = '<?xml version="1.0" encoding="utf-8"?>
<?xml-stylesheet type="text/xsl" href="extensions/AutoSitemap/sitemap.xsl"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        $DEFAULT_PRIORITY = 0.5;
        $DEFAULT_CHANGE_FREQ = "daily";

        $file_handle;
        $file_exists;

        $count=$cursor_pos = 0;

        $form_action;
        $article_priorities = "reverse";
        $estimate_change_freq = false;
	$sorting_criterion = "REV";

        function utf8_write( $handle, $data ) {
                fwrite( $handle, utf8_encode( $data ) ) ;
        }

        function writeSitemap() {
		global $file_name,$DEFAULT_SITEMAP_HEADER,$DEFAULT_PRIORITY,$DEFAULT_CHANGE_FREQ,$file_handle,$file_exists,$count,$cursor_pos,$form_action,$article_priorities,$estimate_change_freq,$sorting_criterion;
                $file_exists = file_exists ( $file_name ) ;
                $count=$cursor_pos = 0;
                $file_handle = fopen( $file_name, 'w' ) or die( "Cannot write to '$file_name'." );
                utf8_write( $file_handle,$DEFAULT_SITEMAP_HEADER);

		$dbr =& wfGetDB( DB_SLAVE );
		$res = $dbr->query(GetSQL());
		$count = $dbr->numRows($res);

		while($row = $dbr->fetchObject( $res ))
			formatResult($row );

                $close_tag = "\n</urlset>";
                utf8_write( $file_handle, $close_tag ) ;

                fclose( $file_handle );
		notifySitemap();
        }

        function notifySitemap() {
		global $wgAutoSitemap_Notify;
		if(is_array($wgAutoSitemap_Notify)) {
			for ( $i = 0; $i < sizeof($wgAutoSitemap_Notify); $i++ ) {
				$handle = fopen($wgAutoSitemap_Notify[$i], 'r');
				if ( $handle)
					fclose($handle);
			}
		}
	}

	function getSQL() {
		global $wgwgAutoSitemap_ExcludeSites,$wgAutoSitemap_Exclude;
		global $file_name,$DEFAULT_SITEMAP_HEADER,$DEFAULT_PRIORITY,$DEFAULT_CHANGE_FREQ,$file_handle,$file_exists,$count,$cursor_pos,$form_action,$article_priorities,$estimate_change_freq,$sorting_criterion;
		$dbr =& wfGetDB( DB_SLAVE );
		$page = $dbr->tableName( 'page' );
		$revision = $dbr->tableName( 'revision' );

		$criterion = $sorting_criterion=="REV"?"rev_timestamp":"page_counter";
		$sql='SELECT "Popularpages" AS type,page_id AS id,page_namespace AS namespace, page_title AS title, ( MAX( rev_timestamp ) ) AS last_modification,'.$criterion.' AS value
				FROM '.$page.', '.$revision.' WHERE ( page_namespace <> 8 AND page_namespace <> 9';
      		if(is_array($wgAutoSitemap_Exclude))
        		foreach($wgAutoSitemap_Exclude as $key=>$val)
          			if($val)
            				$sql.=' AND page_namespace <>"'.$key.'"';

			// Exclude some pages by their title.
		if (sizeof($wgwgAutoSitemap_ExcludeSites)) {
			$sql.=" AND page_title NOT IN ('" .implode("','", $wgwgAutoSitemap_ExcludeSites). "')";
		}

		$sql.=') AND page_is_redirect = 0 AND rev_page = page_id GROUP BY page_id';
		return $sql;
        }

        function formatResult($result ) {
                global $wgLang, $wgContLang, $wgServer, $wgAutoSitemap_ServerBase;
		global $file_name,$DEFAULT_SITEMAP_HEADER,$DEFAULT_PRIORITY,$DEFAULT_CHANGE_FREQ,$file_handle,$file_exists,$count,$cursor_pos,$form_action,$article_priorities,$estimate_change_freq,$sorting_criterion;


		$serverBase = $wgServer;
		if ( strlen($wgAutoSitemap_ServerBase) > 0 ) {
			$serverBase = $wgAutoSitemap_ServerBase;
		}

                $title = Title::makeTitle( $result->namespace, $result->title );
                $link = Linker::linkKnown( $title, htmlspecialchars( $wgContLang->convert( $title->getPrefixedText() ) ) );

                $url = $title->getLocalURL();
                $form_action=$title->getLocalURL( 'action=submit' );

                // The date must conform to ISO 8601 (http://www.w3.org/TR/NOTE-datetime)
                // UTC (Coordinated Universal Time) is used, manual currently ignores time however
                $last_modification = gmdate( "Y-m-d\TH:i:s\Z", wfTimestamp( TS_UNIX, $result->last_modification ) );

				addURL( $serverBase, $url, $last_modification, $result->id );

                ++$cursor_pos;

				return;
        }

        function addURL( $base, $url, $last_modification, $page_id ) {
		global $file_name,$DEFAULT_SITEMAP_HEADER,$DEFAULT_PRIORITY,$DEFAULT_CHANGE_FREQ,$file_handle,$file_exists,$count,$cursor_pos,$form_action,$article_priorities,$estimate_change_freq,$sorting_criterion; // parameters must be valid XML data
                $result="  <url>\n    <loc>$base$url</loc>\n    <priority>".round(getPriority(),1)."</priority>\n    <lastmod>$last_modification</lastmod>\n    <changefreq>".getChangeFreq($page_id)."</changefreq>\n  </url>\n";
                utf8_write( $file_handle, $result );
        }

        function getPriority() { 
		global $file_name,$DEFAULT_SITEMAP_HEADER,$DEFAULT_PRIORITY,$DEFAULT_CHANGE_FREQ,$file_handle,$file_exists,$count,$cursor_pos,$form_action,$article_priorities,$estimate_change_freq,$sorting_criterion;// must return valid XML data
                $x = $cursor_pos / $count;

                switch( $article_priorities ) {
                        case "constant"    : $p= $DEFAULT_PRIORITY;break;
                        case "linear"      : $p= 1.0 - $x;break;
                        case "quadratic"   : $p= pow( 1.0 - $x, 2.0 ) ;break;
                        case "cubic"       : $p= 3.0 * pow( ( 1.0 - $x ), 2.0 ) - 2.0 * pow( ( 1.0 - $x ), 3.0 );break;
                        case "exponential" : $p= exp( -6 * $x );break;# exp(-6) ~= 0,002479
                        case "smooth"      : $p= cos( $x * pi() / 2.0 );break;
                        case "random"      : $p= mt_rand() / mt_getrandmax();break;
                        case "reverse"     : $p= $x;break;

                        default: $p= $DEFAULT_PRIORITY;break;
                }
                return $p;
        }

        function getChangeFreq( $page_id ) { 
		global $file_name,$DEFAULT_SITEMAP_HEADER,$DEFAULT_PRIORITY,$DEFAULT_CHANGE_FREQ,$file_handle,$file_exists,$count,$cursor_pos,$form_action,$article_priorities,$estimate_change_freq,$sorting_criterion;// must return valid XML data
                if( $estimate_change_freq ) {
                        $dbr =& wfGetDB( DB_SLAVE );

                        $revision = $dbr->tableName( 'revision' );

                        $sql = "SELECT
                                        MIN(rev_timestamp) AS creation_timestamp,
                                        COUNT(rev_timestamp) AS revision_count
                                        FROM $revision WHERE rev_page = $page_id";

                        $res = $dbr->query( $sql );
                        $count = $dbr->numRows( $res );

                        if( $count < 1 ) {
                                return $DEFAULT_CHANGE_FREQ;
                        } else {
                                $item1 =( $dbr->fetchObject( $res ) );

                                $cur = time() ; // now
                                $first = wfTimestamp( TS_UNIX, $item1->creation_timestamp );

                                // there were $item1->revision_count revisions in ($cur - $first) seconds
                                $diff = ($cur - $first) / $item1->revision_count ;

                                switch( true ) {
                                        # case $diff < 60: return "always"; // I suspect Manual to ignore these pages more often...
                                        case $diff < 3600: return "hourly";
                                        case $diff < 24*3600: return "daily";
                                        case $diff < 7*24*3600: return "weekly";
                                        case $diff < 30.33*24*3600: return "monthly";
                                        case $diff < 365.25*24*3600: return "yearly";
                                        default: return $DEFAULT_CHANGE_FREQ;
                                        # return "never"; // for archived pages only
                                }
                        }
                } else {
                        return $DEFAULT_CHANGE_FREQ;
                }
        }

?>
