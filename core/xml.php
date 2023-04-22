<?php
/**
 * XML export functions
 *
 * Lets you browse through your movie collection
 *
 * @package Core
 * @author  Andreas Götz    <cpuidle@gmx.de>
 * @author	Kokanovic Branko    <branko.kokanovic@gmail.com>
 * @version $Id: xml.php,v 1.34 2013/03/10 16:25:35 andig2 Exp $
 */

require_once './core/functions.php';
require_once './core/export.core.php';
require_once './core/xml.core.php';

/**
 *  Export XML data
 *
 * @param string  $where  WHERE clause for SQL statement
 */
function xmlexport($WHERE): void
{
    global $config;

    // get data
    $result = exportData($WHERE);

    // do adultcheck
    if (is_array($result)) {
        $result = array_filter($result, function ($video) {
            return adultcheck($video["id"]);
        });
    }
    
    $xml = '';
    
    // loop over items
    foreach ($result as $item) {
        $xml_item = '';
        
        // loop over attributes
        foreach ($item as $key => $value) {
            if (!empty($value)) {
                if ($key != 'owner_id' && $key != 'actors' && $key != 'genres') {
                    $tag       = strtolower($key);
                    $xml_item .= createTag($tag, trim(html_entity_decode_all_utf8($value)));
                }    
            }
        }

        // this is a hack for exporting thumbnail URLs
        if ($item['imgurl'] && $config['xml_thumbnails']) {
            $thumb = getThumbnail($item['imgurl']);
            if (preg_match('/cache/', $thumb)) {
                $xml_item .= createTag('thumbnail', trim($thumb));
            }
        }
        
        // genres
        if (count($item['genres'])) {
            $xml_genres = '';
            foreach ($item['genres'] as $genre) {
                $xml_genres .= createTag('genre', $genre['name']);
            }
            $xml_item .= createContainer('genres', $xml_genres);
        }
        
        // actors
        $actors = explode ("\n",$item['actors']);
        if (count($actors)) {
            $xml_actors = '';
            foreach ($actors as $actor) {
                $xml_actor_data = '';
                $actor_data = explode("::", $actor);
                $xml_actor_data .= createTag('name', $actor_data[0]);
                $xml_actor_data .= createTag('role', $actor_data[1]);
                $xml_actor_data .= createTag('imdbid', $actor_data[2]);
                $xml_actors .= createContainer('actor', $xml_actor_data);
            }
            $xml_item .= createContainer('actors', $xml_actors);
        }
        $xml .= createContainer('item', $xml_item);
    }

    $xml = '<?xml version="1.0" encoding="utf-8"?>'.
    		  "\n".createContainer('catalog', $xml);

    header('Content-Type: application/xml, charset=utf-8');
    header('Content-Length: '.strlen($xml));
    header('Content-Disposition: attachment; filename="videoDB.xml"');

    echo($xml);
    exit();
}

/**
 * Import XML data
 *
 * @param   string  $xmldata    XML input string
 */
function xmlimport($xmldata, &$error)
{
    global $xmloptions, $config;

    // requires php5 xpath functions
    if (version_compare(phpversion(), '5.0') < 0) {
        errorpage('PHP version mismatch',
                  'At least PHP version 5.0.0 is required to run the XML module, please check the documentation!');
    }

    // create DOM document
    $xml = new domDocument();
    $xml->preserveWhiteSpace = false;

    // parse xml
    if (!$xml->loadXML($xmldata)) {
        $error = 'Error parsing input XML, import cancelled.';
        dlog($error);
        return false;
    }
    $xpath = new domXPath($xml);

    $items = $xpath->query('item');
    dlog("no of videos: ".$items->length);
    dlog("xml import options: ".print_r($xmloptions, true));

    // loop over items
    foreach ($items as $video) {
        $nodes = $xpath->query('*', $video);

        $data = [];
        $genre_ids = [];
        $seen_ids = [];

        // loop over item data
        foreach ($nodes as $node) {
            $key = $node->nodeName;
            $value = $node->nodeValue;

            // handle individual attributes
            switch ($key) {
                case 'id':
                    break;

                case 'owner':
                    // import owner?
                    if ($xmloptions['import_owner']) {
                        // check if owner exists
                        $owners = runSQL('SELECT id FROM '.TBL_USERS.' WHERE name=\''.escapeSQL($value).'\'');
                        if (count($owners)) {
                            $data['owner_id'] = $owners[0]['id'];
                        } else {
                            $error = "Owner $value doesn't exist!";
                            return false;
                        }
                    }
                    break;

                case 'custom1':
                case 'custom2':
                case 'custom3':
                case 'custom4':
                    // import custom fields?
                    if ($xmloptions['import_custom']) {
                        $data[$key] = $value;
                    }
                    break;

                case 'diskid':
                    // import disk ids?
                    if ($xmloptions['import_diskid']) {
                        $data[$key] = $value;
                    }
                    break;

                case 'genres':
                    $genres = $xpath->query('genre', $node);
                    // loop over item data
                    foreach ($genres as $genre) {
                        $value = $genre->nodeValue;
                        $id = getGenreId($value);

                        if (empty($id)) {
                            $error = "Genre: $value doesn't exist!";
                            return false;
                        }

                        $genre_ids[] = $id;
                    }
                    break;

                case 'mediatype':
                    $mediatypeId = getMediaTypeId($value);
                    if ($mediatypeId == null) {
                        $mediatypeId = $config['mediadefault'] ?? 0;
                    }
                    $data["mediatype"] = $mediatypeId;
                    break;

                case 'actors':
                    $actors = $xpath->query('actor', $node);

                    // loop over item data
                    $actors_string = '';
                    foreach ($actors as $actor) {
                        $tag_missing = false; /* flag to check all tags are present */
                        // get actor name
                        $actor_name_element = $xpath->query('name', $actor);
                        if ($actor_name_element->length > 0) {
                            $actor_name = $actor_name_element->item(0)->nodeValue;
                        } else {
                            $tag_missing = true;
                        }

                        // get actor role
                        $actor_role_element = $xpath->query('role', $actor);
                        if ($actor_role_element->length > 0) {
                            $actor_role = $actor_role_element->item(0)->nodeValue;
                        } else {
                            $tag_missing = true;
                        }

                        // get actor imdbid
                        $actor_imdbid_element = $xpath->query('imdbid', $actor);
                        if ($actor_imdbid_element->length > 0) {
                            $actor_imdbid = $actor_imdbid_element->item(0)->nodeValue;
                        } else {
                            $tag_missing = true;
                        }

                        if ($tag_missing == false) {
                            // form whole string now
                            if ($actors_string == '') {
                                $actors_string = $actor_name."::".$actor_role."::".$actor_imdbid;
                            } else {
                                $actors_string .= "\n".$actor_name."::".$actor_role."::".$actor_imdbid;
                            }
                        }
                    }
                    $data["actors"] = $actors_string;

                    break;

                default:
                    if ($key != "thumbnail") {
                        $data[$key] = $value;
                    }
            }
        }

        // data to import?
        if (count($data)) {
            $seen = false;
            if (array_key_exists('seen', $data)) {
                $seen = true;
                // Remove the SEEN flag as it does not belong in TBL_DATA
                $data = array_diff_key($data, ['seen' => 1]);
            }

            $keys = join(', ', array_keys($data));
            $values = '';
            foreach (array_values($data) as $value) {
                if ($values) {
                    $values .= ', ';
                }
                $values .= '\''.escapeSQL($value).'\'';
            }

            // import base table data
            $SQL = "INSERT INTO ".TBL_DATA." ($keys) VALUES ($values)";
            $video_id = runSQL($SQL);

            if ($video_id === false) {
                // shouldn't happen
                $error = "Error running import SQL query: $SQL";
                return false;
            }

            // import genres data
            setItemGenres($video_id, $genre_ids);

            if ($seen) {
                set_userseen($video_id, true, $data['owner_id']);
            }

            $imported++;
        }
    }

    if ($imported == 1) {
        // return last item created
        return $video_id;
    } else {
        // return true if > 1 item imported
        return true;
    }
}

/**
 * returns the genreID for a given name from the 'genres' table
 *
 * @todo                  check if this can be moved to edit.php
 * @param  string  $name  the name of the genre
 * @return integer $genre the genre id
 */
function getMediaTypeId($name)
{
	$name = escapeSQL($name);
    $result = runSQL("SELECT id FROM ".TBL_MEDIATYPES." WHERE LCASE(name) = LCASE('".$name."')");
	return $result[0]['id'];
}

/**
 *  Update RSS File
 *
 * @author Mike Clark    <mike.clark@cinven.com>
 */
function rssexport($WHERE): void
{
    global $config, $rss_timestamp_format, $filter;

    // make sure server doesn't specify something else
    header('Content-type: text/xml; charset=utf-8');

    if ($filter)
    {
        $result = exportData($WHERE);
    }
    else
    {
        // get the latest items from the DB according to config setting
        $SQL    = 'SELECT id, title, plot, created 
                     FROM '.TBL_DATA.' 
                 ORDER BY created DESC LIMIT '.$config['shownew'];
        $result = runSQL($SQL);
    }

    // script root
    $base = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
        
	// setup the RSS Feed
    $rssfeed  = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    $rssfeed .= '<rss version="2.0"  xmlns:atom="http://www.w3.org/2005/Atom">';
	$rssfeed .= '<channel>';
    $rssfeed .= '<atom:link href="'.$base.'/index.php?export=rss" rel="self" type="application/rss+xml" />';
	$rssfeed .= createTag('title', 'VideoDB');
	$rssfeed .= createTag('link', $base.'/index.php?export=rss');
	$rssfeed .= createTag('description', 'New items posted on VideoDB');
	$rssfeed .= createTag('language', 'en-us');
    $rssfeed .= createTag('lastBuildDate', date($rss_timestamp_format));

	// build the <item></item> section of the Feed
	foreach ($result as $item)
	{
        $xml_item  = createTag('title', $item['title']);
        $xml_item .= createTag('link', $base.'/show.php?id='.$item['id']);
        $xml_item .= createTag('description', $item['plot']);
        $xml_item .= createTag('guid', $base.'/show.php?id='.$item['id']);
        $xml_item .= createTag('pubDate', rss_timestamp($item['created']));

        $rssfeed  .= createTag('item', $xml_item, false);
	}
	$rssfeed .= '</channel>';
	$rssfeed .= '</rss>';

    header('Content-type: text/xml');
#   header('Content-length: '.rssfeed($xml));
#   header('Content-disposition: filename=rss.xml');
    echo $rssfeed;
}

?>
