<?php
/**
 * Google Image Parser
 *
 * Lookup cover images from Google
 *
 * @package Engines
 * @author  Andreas Götz    <cpuidle@gmx.de>
 *
 * @link    http://images.google.com  Google image search
 * @link    http://code.google.com/apis/ajaxsearch/documentation/   API doc
 *
 * @version $Id: google.php,v 1.13 2013/03/16 14:29:47 andig2 Exp $
 */

/**
 *  Get meta information about the engine
 *
 * @todo Include image search capabilities etc in meta information
 *
 * @return (int|string|string[])[]
 *
 * @psalm-return array{name: 'Google', stable: 1, capabilities: array{0: 'image'}}
 */
function googleMeta(): array
{
    return array('name' => 'Google', 'stable' => 1, 'capabilities' => array('image'));
}

/**
 * Search an image on Google
 *
 * Searches for a given title on the google and returns the found links in
 * an array
 *
 * @param   string    The search string
 * @return  array     Associative array with id and title
 */
function googleSearch($title)
{
    global $CLIENTERROR;
    global $cache;

    $page = 1;
    $data = array();
    $data['encoding'] = 'utf-8';

    do
    {
        $url  = "http://ajax.googleapis.com/ajax/services/search/images?v=1.0&rsz=large&q=".urlencode($title)."&start=".count($data);
        $resp = httpClient($url, $cache);
        if (!$resp['success']) $CLIENTERROR .= $resp['error']."\n";

        $json = json_decode($resp['data']);
#       dump($resp['data']);
#       dump($page);
#       dump($json);

        // prevent caching  invalid responses
        if ($json->responseStatus != 200 && $cache) {
            $cache_file = cache_get_filename($url, CACHE_HTML);
            @unlink($cache_file);
        }

        foreach ($json->responseData->results as $row)
        {
    #       dump($row);
            $res            = array();
            $res['title']   = $row->width.'x'.$row->height; // width x height
            $res['imgsmall']= $row->tbUrl;                  // small thumbnail url
            $res['coverurl']= $row->url;                    // resulting target url
            $data[]       = $res;
        }
    }
    // Google does not return more than 4 pages of results. Limiting to 2 for performance
    while ($page++ < 3);
    
#   dump($data);
    
    return $data;
}

?>