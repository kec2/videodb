<?php
/**
 * Delete a video
 *
 * Handles the deletion of a video
 *
 * @package videoDB
 * @author  Andreas Gohr <a.gohr@web.de>
 * @version $Id: delete.php,v 2.22 2013/03/10 16:20:31 andig2 Exp $
 */

require_once './core/functions.php';

/**
 *  Remove image from cache
 *
 * @author Andreas Goetz <cpuidle@gmx.de>
 */
function removeCacheFile($url): void
{
    // get extension
    if (preg_match("/\.(jpe?g|gif|png)$/i", $url, $matches)) 
    {
        // check if file exists
        if (cache_file_exists($url, $cache_file, CACHE_IMG, $matches[1]))
        {
            @unlink($cache_file);
        }
    }
}

// check for localnet
localnet_or_die();

// multiuser permission check
permission_or_die(PERM_WRITE, get_owner_id($id));

/*
// remove old cover image from cache
$SQL = 'SELECT imgurl FROM '.TBL_DATA.' WHERE id = '.$id;
$res = runSQL($SQL);
if (count($res))
{
    removeCacheFile($res[0]['imgurl']);
}
*/

// remove actual data
runSQL('DELETE FROM '.TBL_DATA.' WHERE id = '.$id);
runSQL('DELETE FROM '.TBL_VIDEOGENRE.' WHERE video_id = '.$id);

// Disabled because on a large collection, this takes hours.
// Delete from actors what is not found in any movie.
// runSQL('delete from '.TBL_ACTORS
//       .' where not exists ('
//             .' select actors'
//             .' from '.TBL_DATA.' as D'
//             .' where position('.TBL_ACTORS.'.actorid in D.actors) <> 0)');

// clear smarty cache for this item
#!! this does not work- at least not with Smarty3
#$smarty->cache->clear($id);

// goto index instead of delete template
if ($redirect)
{
    header("Location: index.php?deleteid=$id");
    exit;
}

// prepare templates
tpl_page();

// display templates
tpl_display('delete.tpl');

?>
