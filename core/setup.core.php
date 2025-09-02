<?php
/**
 * Functions for config options
 *
 * @package Core
 */

require_once './core/functions.php';
require_once './core/custom.php';
require_once './core/output.php';

$SETUP_GLOBAL = array('language', 'autoid', 'mediadefault', 'langdefault', 
                      'filterdefault', 'showtv', 'orderallbydisk', 'removearticles',
                      'localnet', 'IMDBage', 'thumbnail', 
                      'castcolumns', 'template', 'languageflags', 'custom1', 
                      'custom2', 'custom3', 'custom4', 'custom1type', 
                      'custom2type', 'custom3type', 'custom4type', 'enginedefault', 
                      'proxy_host', 'proxy_port', 'actorpics', 'thumbAge', 'listcolumns', 
                      'shownew', 'imdbBrowser', 'multiuser', 'denyguest', 'adultgenres',
                      'pageno', 'showtools', 'http_header_accept_language', 'diskid_digits',
                      'hierarchical', 'cache_pruning', 'xml', 'xls', 'pdf', 'rss', 'boxeeHost', 'boxeePort',
                      'lookupdefault_edit', 'lookupdefault_new', 'thumbnail_level', 'thumbnail_quality', 'offline',
                      'pdf_scale', 'pdf_image_max_height', 'pdf_image_max_width',
                      'debug', 'httpclientlog');

$SETUP_QUICK  = array('template');

$SETUP_USER   = array('language', 'mediadefault', 'langdefault', 'filterdefault', 
                      'showtv', 'orderallbydisk', 'template', 'languageflags', 
                      'listcolumns', 'castcolumns', 'shownew', 'pageno', 'removearticles', 'diskid_digits',
                      'boxeeHost', 'boxeePort', 'lookupdefault_edit', 'lookupdefault_new',
                      'thumbnail_level', 'thumbnail_quality');

/**
 * Build config options array
 *
 * @param   boolean   $isprofile  Determines if user-specific options are to be displayed
 *
 * @return  array   associative array of config options
 */
function setup_mkOptions($isprofile = false)
{
	global $config, $lang;

    // built list of setup options
	$setup = array();
    
    // isprofile, name, type (text|boolean|dropdown|special|link), data, set, helphl, helptxt
    $setup[] = setup_addSection('opt_general');
    $setup[] = setup_addOption($isprofile, 'offline', 'boolean');

	$setup[] = setup_addOption($isprofile, 'language', 'dropdown', setup_getLanguages(), null, $lang['help_langn'], $lang['help_lang']);
    $option  = setup_addOption($isprofile, 'template', 'dropdown', setup_getTemplates($thumbs));
    $option['thumbs'] = $thumbs;
    $setup[] = $option;

    $setup[] = setup_addOption($isprofile, 'listcolumns', 'text');
    $setup[] = setup_addOption($isprofile, 'castcolumns', 'text');

    $setup[] = setup_addOption($isprofile, 'autoid', 'boolean');
    $setup[] = setup_addOption($isprofile, 'orderallbydisk', 'boolean');
    $setup[] = setup_addOption($isprofile, 'diskid_digits', 'text');

    $setup[] = setup_addOption($isprofile, 'mediadefault', 'dropdown', setup_getMediatypes());
    $setup[] = setup_addOption($isprofile, 'langdefault', 'text');
    $setup[] = setup_addOption($isprofile, 'filterdefault', 'dropdown', array('all'=>$lang['radio_all'], 'unseen'=>$lang['radio_unseen'], 'new'=>$lang['radio_new'], 'wanted'=>$lang['radio_wanted']));
    $setup[] = setup_addOption($isprofile, 'showtv', 'boolean');
    $setup[] = setup_addOption($isprofile, 'shownew', 'text');
    $setup[] = setup_addOption($isprofile, 'pageno', 'text');
    $setup[] = setup_addOption($isprofile, 'languageflags', 'special', out_languageflags($config['languages']));
    $setup[] = setup_addOption($isprofile, 'removearticles', 'boolean');
    $setup[] = setup_addOption($isprofile, 'adultgenres', 'multi', setup_getGenres(), @explode('::', $config['adultgenres']));
    $setup[] = setup_addOption($isprofile, 'showtools', 'boolean');
    $setup[] = setup_addOption($isprofile, 'lookupdefault_edit', 'text');
    $setup[] = setup_addOption($isprofile, 'lookupdefault_new', 'text');

    if (!$isprofile) $setup[] = setup_addSection('opt_custom');
    $setup[] = setup_addOption($isprofile, 'custom', 'special', setup_mkCustoms());
    
    if (!$isprofile) $setup[] = setup_addSection('opt_engines');
    $setup[] = setup_addOption($isprofile, 'enginedefault', 'dropdown', setup_getEngines($config['engines']), null , $lang['help_defaultenginen'], $lang['help_defaultengine']);

    foreach ($config['engines'] as $engine => $meta) {
        $title      = $meta['name'];
        $enabled    = $config['engine'][$engine];        
        $helptext   = sprintf($lang['help_engine'], $title);
        $helptext  .= ' '.$lang['help_engine'.$engine];
        if (!$meta['stable']) $helptext .= ' '.$lang['help_engexperimental'];
        
        $setup[] = setup_addOption($isprofile, 'engine'.$engine, 'boolean', null, $enabled, $title, $helptext);

        // add engine-specific options
        if (is_array($meta['config'])) {
            foreach ($meta['config'] as $setting) {
                // NOTE: check setup_additionalSettings if you change the option naming
                if (is_array($setting['values'])) {
                    $setup[] = setup_addOption($isprofile, $engine.$setting['opt'],
                        'dropdown', $setting['values'], null, $setting['name'], $setting['desc']);
                } else {
                    $setup[] = setup_addOption($isprofile, $engine.$setting['opt'], 
                        'text', null, null, $setting['name'], $setting['desc']);
                }
            }
        }
    }

    if (!$isprofile) $setup[] = setup_addSection('opt_security');
	$setup[] = setup_addOption($isprofile, 'localnet', 'text');
	$setup[] = setup_addOption($isprofile, 'multiuser', 'boolean');
	$setup[] = setup_addOption($isprofile, 'denyguest', 'boolean');
    $setup[] = setup_addOption($isprofile, 'usermanager', 'link', 'users.php');
	$setup[] = setup_addOption($isprofile, 'proxy_host', 'text');
	$setup[] = setup_addOption($isprofile, 'proxy_port', 'text');
	$setup[] = setup_addOption($isprofile, 'http_header_accept_language', 'text');

    if (!$isprofile) $setup[] = setup_addSection('opt_caching');
    $setup[] = setup_addOption($isprofile, 'thumbnail', 'boolean');
    $setup[] = setup_addOption($isprofile, 'actorpics', 'boolean');
    $setup[] = setup_addOption($isprofile, 'imdbBrowser', 'boolean');
    $setup[] = setup_addOption($isprofile, 'cache_pruning', 'boolean');
    $setup[] = setup_addOption($isprofile, 'hierarchical', 'boolean');
    $setup[] = setup_addOption($isprofile, 'IMDBage', 'text');
    $setup[] = setup_addOption($isprofile, 'thumbAge', 'text');
    $setup[] = setup_addOption($isprofile, 'thumbnail_level', 'text');
    $setup[] = setup_addOption($isprofile, 'thumbnail_quality', 'text');

    if (!$isprofile) $setup[] = setup_addSection('opt_export');
    $setup[] = setup_addOption($isprofile, 'xml', 'boolean');
    $setup[] = setup_addOption($isprofile, 'rss', 'boolean');
    $setup[] = setup_addOption($isprofile, 'xls', 'boolean');
    $setup[] = setup_addOption($isprofile, 'pdf', 'boolean');

    if (!$isprofile) $setup[] = setup_addSection('opt_pdf');
    $setup[] = setup_addOption($isprofile, 'pdf_scale', 'boolean');
    $setup[] = setup_addOption($isprofile, 'pdf_image_max_height', 'text');
    $setup[] = setup_addOption($isprofile, 'pdf_image_max_width', 'text');

    if (!$isprofile) $setup[] = setup_addSection('opt_boxee');
    $setup[] = setup_addOption($isprofile, 'boxeeHost', 'text');
    $setup[] = setup_addOption($isprofile, 'boxeePort', 'text');

    if (!$isprofile) $setup[] = setup_addSection('opt_debug');
    $setup[] = setup_addOption($isprofile, 'debug', 'boolean');
    $setup[] = setup_addOption($isprofile, 'httpclientlog', 'boolean');

    // clean empty entries
    for ($i = count($setup); $i > 0; $i--) {
        if (empty($setup[$i]['name']) && empty($setup[$i]['group'])) {
            unset($setup[$i]);
        }
    }

	return $setup;
}

/**
 *  Add engine-specific config options for saving
 */
function setup_additionalSettings(): void
{
    global $config, $SETUP_GLOBAL;

    foreach ($config['engines'] as $engine => $meta) {
        // add engine-specific options
        if (is_array($meta['config'])) {
            foreach ($meta['config'] as $setting) {
                $SETUP_GLOBAL[] = $engine.$setting['opt'];
            }
        }
    }
}

/**
 *  Add a new section to the config options array
 *
 * @param array   $setup      The config array
 * @param string  $section    Name of the new section
 */
function setup_addSection($section): array
{
    $option['group']    = $section;
    return $option;
}

/**
 *  Adds an entry for the config option array
 *
 *  returns NULL on global options if $isprofile is true 
 *  so global options will not be added to user profile settings
 *
 * @param array   $setup      The config array
 * @param boolean $isprofile  Do we prepare a profile array?
 * @param string  $name       Name of the config option
 * @param string  $type       Type of option (text|boolean|dropdown|special|link)
 * @param string  $data       Current value of this option
 * @param string  $set        Default value of this option
 * @param string  $hl         Help text headline
 * @param string  $help       Help text
 *
 * @return array|null
 */
function setup_addOption($isprofile, $name, $type, 
                         $data='', $set=NULL, $hl=NULL, $help=NULL)
{
	global $config, $lang;
    global $SETUP_USER;

    // user-specific setting?
    $isuser = in_array($name, $SETUP_USER);
    
	if ($isprofile and !$isuser) return;

	$option['isuser']   = $isuser;
	$option['name']     = $name;
	$option['type']     = $type;
	$option['data']     = $data;
    
    $option['set']  = ($set)  ? $set  : $config[$name];
    $option['hl']   = ($hl)   ? $hl   : $lang['help_'.$name.'n'];
    $option['help'] = ($help) ? $help : $lang['help_'.$name];

    return $option;
}

/**
 * Find available languages
 */
function setup_getLanguages()
{
    if ($dh = opendir('language')) {
        while (($file = readdir($dh)) !== false)  {
            if (preg_match("/(.*)\.php$/", $file, $matches)) {
                $languages[$matches[1]] = $matches[1];
            }
        }
        closedir($dh);
    }
    return $languages;
}

/**
 * Find available templates/styles
 * Extended to search for template screenshots
 *
 * @author  Andreas G�tz    <cpuidle@gmx.de>
 */
function setup_getTemplates(&$screenshots)
{
    $screenshots = array();
    
	if ($dh = @opendir('templates')) {
		while (($file = readdir($dh)) !== false) {
			if (preg_match("/^\./", $file)) continue;

			if (is_dir('templates/'.$file)) {
                $template = 'templates/'.$file;
				if ($dh2 = opendir($template)) {
                    $style_name = '';

					while (($style = readdir($dh2)) !== false) {
						if (preg_match("/(.*)\.css$/", $style, $matches)) {
                            $thumb = $template.'/screenshot_'.$matches[1].'.jpg';
                            if (file_exists($thumb)) {
                                $screenshots[] = array('name' => "$file::".$matches[1], 'img' => $thumb);
                            } elseif (empty($style_name)) {
                                // remember first style found
                                $style_name = $matches[1];
                            }
							$templates[$file.'::'.$matches[1]] = $file.' ('.$matches[1].')';
						}
					}
	    			closedir($dh2);
                    
                    if ($style_name) {
                        $thumb = $template.'/screenshot.jpg';
                        if (file_exists($thumb)) {
                            $screenshots[] = array('name' => "$file::$style_name", 'img' => $thumb);
                        }
                    }

				}
			}
		}
		closedir($dh);
	}
	return $templates;
}

/**
 *  Mediatypes
 */
function setup_getMediatypes(): array
{
    $SELECT = 'SELECT id, name FROM '.TBL_MEDIATYPES.' ORDER BY name';
    $result = runSQL($SELECT);
        
    return array_associate($result, 'id', 'name');
}

/**
 * Genres
 */
function setup_getGenres(): array
{
    $SELECT = 'SELECT id, name FROM '.TBL_GENRES.' ORDER BY name';
    $result = runSQL($SELECT);
    
    return array_associate($result, 'id', 'name');
}

/**
 *  Get list of engines for default engine selection
 */
function setup_getEngines($engines_ary): array
{
	$engines = array();
	
	foreach ($engines_ary as $engine => $meta) {
        if (engine_get_capability($engine, 'movie')) {
            $engines[$engine] = $meta['name'];
        }
    }
    
    return $engines;
}

/**
 *  Prepare customfields
 */
function setup_mkCustoms(): string
{
    global $config;
    global $allcustomtypes;
    
    $setup_custom = '';
    
    for ($i=1; $i<5; $i++) {
        $setup_custom .= $i.'. <input type="text" size="20" name="custom'.$i.'" id="custom'.$i.'" value="'.htmlspecialchars($config['custom'.$i]).'"/>';
        $setup_custom .= '<select name="custom'.$i.'type">';
    
        foreach($allcustomtypes as $ctype) {
            $selected       = ($ctype == $config['custom'.$i.'type']) ? ' selected="selected"' : '';
            $setup_custom  .= '<option value="'.$ctype.'"'.$selected.'>'.$ctype.'</option>';
        }
        $setup_custom .= '</select>';
        $setup_custom .= "<br />\n";
    }
    
    return $setup_custom;
}

/**
 *  Update session variables with configuration values
 *
 * @author Andreas Goetz
 */
function update_session(): void
{
    global $listcolumns, $showtv;
    
    if ($listcolumns) {
        $_SESSION['vdb']['listcolumns'] = $listcolumns;
    }
    if ($showtv) {
        $_SESSION['vdb']['showtv'] = $showtv;
    }
}

?>
