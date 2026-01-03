<?php
/**
 * Overwriting DokuWiki template functions
 *
 * @license	GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author	Sascha Leib <sascha@leib.be>
 * @author	Andreas Gohr <andi@splitbrain.org>
 */

use dokuwiki\Extension\Event;
use dokuwiki\File\PageResolver;

/**
 * Print the specific HTML meta headers
 *
 * Overrides the original version by modifying the headers and the way it is printed
 *
 * @author Sascha Leib <sascha@leib.be>
 * @author Andreas Gohr <andi@splitbrain.org>
 *
 * @triggers TPL_METAHEADER_OUTPUT
 * @param	bool $alt Should feeds and alternative format links be added?
 * @return	bool
 */
function my_metaheaders($alt = true) {
	global $ID;
	global $REV;
	global $INFO;
	global $JSINFO;
	global $ACT;
	global $QUERY;
	global $lang;
	global $conf;
	global $updateVersion;
	/** @var Input $INPUT */
	global $INPUT;

	// prepare the head array
	$head = array();

	// prepare seed for js and css
	$tseed = $updateVersion;
	$depends = getConfigFiles('main');
	$depends[] = DOKU_CONF."tpl/".$conf['template']."/style.ini";
	foreach($depends as $f) $tseed .= @filemtime($f);
	$tseed = md5($tseed);

	// Open Graph information
	$meta = p_get_metadata($ID);
	if (is_array($meta) && array_key_exists('title', $meta) && $meta['title'] !== null) {
		$head['meta'][] = array('property' => 'og:title', 'content' => tpl_pagetitle($ID, true));
		$head['meta'][] = array('property' => 'og:site_name ', 'content' => $conf['title']);
		$head['meta'][] = array('property' => 'og:type', 'content' => 'website');
		$head['meta'][] = array('property' => 'og:url', 'content' => wl($ID, '', true, '&'));
	
		if (array_key_exists('description', $meta) && is_array($meta['description'])) {
			if (array_key_exists('abstract', $meta['description'])) {
				$parts = explode(NL, $meta['description']['abstract']);
				
				if (is_array($parts) && array_key_exists(2, $parts)) {
					$head['meta'][] = array('property' => 'og:description', 'content' => $parts[2]);
				
					// Bing insists in a non-og description:
					$head['meta'][] = array('name' => 'description', 'content' => $parts[2]);
				}
			}
		}
	}

	// the usual stuff
	$head['meta'][] = array('name'=> 'generator', 'content'=> 'DokuWiki');
	if(actionOK('search')) {
		$head['link'][] = array(
			'rel' => 'search', 'type'=> 'application/opensearchdescription+xml',
			'href'=> DOKU_BASE.'lib/exe/opensearch.php', 'title'=> $conf['title']
		);
	}

	$head['link'][] = array('rel'=> 'start', 'href'=> DOKU_BASE);
	if(actionOK('index')) {
		$head['link'][] = array(
			'rel' => 'contents', 'href'=> wl($ID, 'do=index', false, '&'),
			'title'=> $lang['btn_index']
		);
	}

	if (actionOK('manifest')) {
		$head['link'][] = array('rel'=> 'manifest', 'href'=> DOKU_BASE.'lib/exe/manifest.php');
	}

	$styleUtil = new \dokuwiki\StyleUtils();
	$styleIni = $styleUtil->cssStyleini();
	$replacements = $styleIni['replacements'];
	if (!empty($replacements['__theme_color__'])) {
		$head['meta'][] = array(
			'name' => 'theme-color',
			'content' => $replacements['__theme_color__']
		);
	}

	if($alt) {
		if(actionOK('rss')) {
			$head['link'][] = array(
				'rel'  => 'alternate', 'type'=> 'application/rss+xml',
				'title'=> $lang['btn_recent'], 'href'=> DOKU_BASE.'feed.php'
			);
			$head['link'][] = array(
				'rel'  => 'alternate', 'type'=> 'application/rss+xml',
				'title'=> $lang['currentns'],
				'href' => DOKU_BASE.'feed.php?mode=list&ns='.(isset($INFO) ? $INFO['namespace'] : '')
			);
		}
		if(($ACT == 'show' || $ACT == 'search') && $INFO['writable']) {
			$head['link'][] = array(
				'rel'  => 'edit',
				'title'=> $lang['btn_edit'],
				'href' => wl($ID, 'do=edit', false, '&')
			);
		}

		if(actionOK('rss') && $ACT == 'search') {
			$head['link'][] = array(
				'rel'  => 'alternate', 'type'=> 'application/rss+xml',
				'title'=> $lang['searchresult'],
				'href' => DOKU_BASE.'feed.php?mode=search&q='.$QUERY
			);
		}

		if(actionOK('export_xhtml')) {
			$head['link'][] = array(
				'rel' => 'alternate', 'type'=> 'text/html', 'title'=> $lang['plainhtml'],
				'href'=> exportlink($ID, 'xhtml', '', false, '&')
			);
		}

		if(actionOK('export_raw')) {
			$head['link'][] = array(
				'rel' => 'alternate', 'type'=> 'text/plain', 'title'=> $lang['wikimarkup'],
				'href'=> exportlink($ID, 'raw', '', false, '&')
			);
		}
	}

	// setup robot tags apropriate for different modes
	if(($ACT == 'show' || $ACT == 'export_xhtml') && !$REV) {
		if($INFO['exists']) {
			//delay indexing:
			if((time() - $INFO['lastmod']) >= $conf['indexdelay'] && !isHiddenPage($ID) ) {
				$head['meta'][] = array('name'=> 'robots', 'content'=> 'index,follow');
			} else {
				$head['meta'][] = array('name'=> 'robots', 'content'=> 'noindex,nofollow');
			}
			$canonicalUrl = wl($ID, '', true, '&');
			if ($ID == $conf['start']) {
				$canonicalUrl = DOKU_URL;
			}
			$head['link'][] = array('rel'=> 'canonical', 'href'=> $canonicalUrl);
		} else {
			$head['meta'][] = array('name'=> 'robots', 'content'=> 'noindex,follow');
		}
	} elseif(defined('DOKU_MEDIADETAIL')) {
		$head['meta'][] = array('name'=> 'robots', 'content'=> 'index,follow');
	} else {
		$head['meta'][] = array('name'=> 'robots', 'content'=> 'noindex,nofollow');
	}

	// set metadata
	if($ACT == 'show' || $ACT == 'export_xhtml') {
		// keywords (explicit or implicit)
		if(!empty($INFO['meta']['subject'])) {
			$head['meta'][] = array('name'=> 'keywords', 'content'=> join(',', $INFO['meta']['subject']));
		} else {
			$head['meta'][] = array('name'=> 'keywords', 'content'=> str_replace(':', ',', $ID));
		}
	}

	// load stylesheets
	$head['link'][] = array(
		'rel' => 'stylesheet',
		'href'=> DOKU_BASE . 'lib/exe/css.php?t='.rawurlencode($conf['template']).'&tseed='.$tseed,
		'defer' => 'defer'
	);

	$script = "var NS='".(isset($INFO)?$INFO['namespace']:'')."';".DOKU_LF.DOKU_TAB.DOKU_TAB;
	if($conf['useacl'] && $INPUT->server->str('REMOTE_USER')) {
		$script .= "var SIG=".toolbar_signature().";".DOKU_LF.DOKU_TAB.DOKU_TAB;
	}
	
	if($conf['basedir']) {
		$script .= 'var BASEDIR="'.$conf['basedir']."\";".DOKU_LF.DOKU_TAB.DOKU_TAB;
	}

	jsinfo();
	$script .= 'var JSINFO = ' . json_encode($JSINFO).';';
	$head['script'][] = array('_data'=> $script);

	// load jquery
	$jquery = getCdnUrls();
	foreach($jquery as $src) {
		$head['script'][] = array(
			/* 'charset' => 'utf-8', -- obsolete */
			'_data' => '',
			'src' => $src,
		) + ($conf['defer_js'] ? [ 'defer' => 'defer'] : []);
	}

	// load our javascript dispatcher
	$head['script'][] = array(
		/* 'charset'=> 'utf-8', -- obsolete */
		'_data'=> '',
		'src' => DOKU_BASE . 'lib/exe/js.php'.'?t='.rawurlencode($conf['template']).'&tseed='.$tseed,
	) + ($conf['defer_js'] ? [ 'defer' => 'defer'] : []);

	// trigger event here
	Event::createAndTrigger('TPL_METAHEADER_OUTPUT', $head, '_my_metaheaders_action', true);
	return true;
}

/**
 * prints the array build by my_metaheaders
 *
 * Overrides the original version by adding a tab before each line for neater HTML code
 *
 * @author Sascha Leib <sascha@leib.be>
 * @author Andreas Gohr <andi@splitbrain.org>
 *
 * @param array $data
 */
function _my_metaheaders_action($data) {
	foreach($data as $tag => $inst) {
		foreach($inst as $attr) {
			if ( empty($attr) ) { continue; }
			echo DOKU_TAB . '<', $tag, ' ', buildAttributes($attr);
			if($tag == 'script' && isset($attr['_data'])) {
				/* $attr['_data'] = "<![CDATA[".NL. DOKU_TAB . DOKU_TAB .
					$attr['_data'].
					NL . DOKU_TAB . ' ]]>'; */

				echo '>', $attr['_data'], '</', $tag, '>';
			} else {
				echo '>';
				if ($tag == 'script') {
					echo '</', $tag, '>';
				}
			}
			echo DOKU_LF;
		}
	}
}

/**
 * get a link to the homepage.
 *
 * wraps the original wl() function to allow overriding in the options
 *
 * @author Sascha Leib <sascha@leib.be>
 *
 * @returns string (link)
 */
function my_homelink() {
	global $conf;
	
	$hl = trim(tpl_getConf('homelink'));

	if ( $hl !== '' ) {
		return $hl;
	} else {
		return wl(); // default homelink
	}
}

/**
 * Print the breadcrumbs trace
 *
 * Cleanup of the original code to create neater and more accessible HTML
 *
 * @author Sascha Leib <sascha@leib.be>
 * @author Andreas Gohr <andi@splitbrain.org>
 *
 * @param string $prefix inserted before each line
 *
 * @return void
 */
function my_breadcrumbs($prefix = '') {
	global $lang;
	global $conf;

	//check if enabled
	if(!$conf['breadcrumbs']) return false;

	$crumbs = breadcrumbs(); //setup crumb trace

	/* begin listing */
	echo $prefix . '<nav id="navBreadCrumbs">'.NL;
	echo $prefix . DOKU_TAB . '<h4>' . $lang['breadcrumb'] . '</h4>'.NL;
	echo $prefix . DOKU_TAB . '<ol reversed>'.NL;

	$last = count($crumbs);
	$i	= 0;
	foreach($crumbs as $id => $name) {
		$i++;
		echo $prefix . DOKU_TAB . DOKU_TAB . '<li' . ($i == $last ? ' class="current"' : '') . '><bdi>' . tpl_link(wl($id), hsc($name), '', true) .  '</bdi></li>'.NL;
	}
	echo $prefix . DOKU_TAB . '</ol>'.NL;
	echo $prefix . '</nav>'.NL;
}

/**
 * Hierarchical breadcrumbs
 *
 * Cleanup of the original code to create neater and more accessible HTML
 *
 * @author Sascha Leib <sascha@leib.be>
 * @author Andreas Gohr <andi@splitbrain.org>
 * @author Nigel McNie <oracle.shinoda@gmail.com>
 * @author Sean Coates <sean@caedmon.net>
 * @author <fredrik@averpil.com>
 *
 * @param  string $prefix to be added before each line
 *
 */
function my_youarehere($prefix = '') {
	global $conf;
	global $ID;
	global $lang;

	// check if enabled
	if(!$conf['youarehere']) return false;

	$parts = explode(':', $ID);
	$count = count($parts);
	$isdir = ( $parts[$count-1] == $conf['start']);
	
	$hl = trim(tpl_getConf('homelink'));

	echo $prefix . '<nav id="navYouAreHere">'.NL;
	echo $prefix . DOKU_TAB . '<h4>' . $lang['youarehere'] . '</h4>'.NL;
	echo $prefix . DOKU_TAB . '<ol>'.NL;

	// always print the startpage
	if ( $hl !== '' ) {
		echo $prefix . DOKU_TAB . DOKU_TAB . '<li class="home">' . tpl_link( $hl, htmlentities(tpl_getLang('homepage')), ' title="' . htmlentities(tpl_getLang('homepage')) . '"', true) . '</li>'.NL;
		echo $prefix . DOKU_TAB . DOKU_TAB . '<li>' . tpl_pagelink(':'.$conf['start'], null, true) . '</li>'.NL;
	} else {
		echo $prefix . DOKU_TAB . DOKU_TAB . '<li class="home">' . tpl_pagelink(':'.$conf['start'], null, true) . '</li>'.NL;
	}

	// print intermediate namespace links
	$page = '';
	for($i = 0; $i < $count - 1; $i++) {
		$part = $parts[$i];
		$page .= $part . ':';

		if ($i == $count-2 && $isdir)  break; // Skip last if it is an index page
	
		echo $prefix . DOKU_TAB . DOKU_TAB . '<li>' . tpl_pagelink($page, null, true) . '</li>'.NL;
	}

	// chould the current page be included in the listing?
	$trail = tpl_getConf('navtrail');
	
	if ($trail !== 'none' && $trail !== '') {

		echo $prefix . DOKU_TAB . DOKU_TAB . '<li class="current">';
		if ($trail == 'text') {
			echo tpl_pagetitle($page . $parts[$count-1], true);
		} else if ($trail == 'link') {
			echo tpl_pagelink($page . $parts[$count-1], null, true);
		}
		echo '</li>'.NL;
	}
	
	echo $prefix . DOKU_TAB . '</ol>'.NL;
	echo $prefix . '</nav>'.NL;
}

/**
 * My implementation of the basic userinfo (in the global banner)
 *
 *
 * @author Sascha Leib <sascha@leib.be>
 *
 * @param  string $prefix to be added before each line
 *
 * @return void
 */
function my_userinfo($prefix = '') {
	global $lang;
	global $INPUT;

	// add login/logout button:
	$items = (new \dokuwiki\Menu\UserMenu())->getItems();
	foreach($items as $it) {
		$typ = $it->getType();
		
		if ($typ === 'profile') { // special case for user profile:
		
			echo $prefix . '<li class="action profile"><span class="sronly">' . $lang['loggedinas'] .
				' </span><a href="' . htmlentities($it->getLink()) . '" title="' . $it->getTitle() . '">' .
				userlink() . "</a></li>".NL;

		} else {

			echo $prefix . "<li class=\"action $typ\">" . '<a href="' . htmlentities($it->getLink()) .
				'" title="' . $it->getTitle() . '">' . ($typ === 'profile'? userlink() : $it->getLabel() ) .
				'</a></li>'.NL;
		}
	}
}

/**
 *Inserts a cleaner version of the TOC
 *
 * This is an update of the original function that renders the TOC directly.
 *
 * @author Sascha Leib <sascha@leib.be>
 * @author Andreas Gohr <andi@splitbrain.org>
 *
 * @param  string $prefix to be added before each line
 *
 * @return void
 */
function my_toc($prefix = '') {
	global $TOC;
	global $ACT;
	global $ID;
	global $REV;
	global $INFO;
	global $conf;
	global $lang;
	$toc = array();

	// default TOC State:
	$tocState = 'hide';

	if(is_array($TOC)) {
		// if a TOC was prepared in global scope, always use it
		$toc = $TOC;
	} elseif(($ACT == 'show' || substr($ACT, 0, 6) == 'export') && !$REV && $INFO['exists']) {

		// read TOC state from the user config:
		$tocState = tpl_getConf('tocstyle', $tocState);

		// get TOC from metadata, render if neccessary
		$meta = p_get_metadata($ID, '', METADATA_RENDER_USING_CACHE);
		if(isset($meta['internal']['toc'])) {
			$tocok = $meta['internal']['toc'];
		} else {
			$tocok = true;
		}
		$toc = isset($meta['description']['tableofcontents']) ? $meta['description']['tableofcontents'] : null;
		if(!$tocok || !is_array($toc) || !$conf['tocminheads'] || count($toc) < $conf['tocminheads']) {
			$toc = array();
		}
	} elseif($ACT == 'admin') {
		// try to load admin plugin TOC
		/** @var $plugin AdminPlugin */
		if ($plugin = plugin_getRequestAdminPlugin()) {
			$toc = $plugin->getTOC();
			$TOC = $toc; // avoid later rebuild
		}
	}

	/* Build the hierarchical list of headline links: */
	if (count($toc) >= intval($conf['tocminheads'])) {
		echo $prefix . '<aside id="toc" class="toggle '.$tocState.'">'.NL;
		echo $prefix . DOKU_TAB . '<button type="button" id="toc-menubutton" class="tg_button" title="' . htmlentities($lang['toc']) . '" aria-haspopup="true" aria-controls="toc-menu"><span>' . htmlentities($lang['toc']) . '</span></button>'.NL;
		echo $prefix . DOKU_TAB . '<nav id="toc-menu" class="tg_content" role="menu" aria-labelledby="toc-menubutton">';
		$level = 0;
		foreach($toc as $it) {
			
			$nl = intval($it['level']);
			$cp = ($nl <=> $level);

			if ($cp > 0) {
				while ($level < $nl) {
					echo DOKU_LF . $prefix . str_repeat(DOKU_TAB, $level*2 + 2) . '<ol>'.NL;
					$level++;
				}
			} else if ($cp < 0) {
				while ($level > $nl) {
					echo DOKU_LF . $prefix . str_repeat(DOKU_TAB, $level*2) . '</ol>'.NL;
					echo $prefix . str_repeat(DOKU_TAB, $level*2-1) . '</li>'.NL;
					$level--;
				}
			} else {
				echo '</li>'.NL;
			}
			
			$href = ( array_key_exists('link', $it ) ? $it['link'] : '' ) . ( array_key_exists('hid', $it) && $it['hid'] !== '' ? '#' . $it['hid'] : '' );

			echo $prefix . str_repeat(DOKU_TAB, $nl*2 + 1) . '<li role="presentation">' . "<a role=\"menuitem\" href=\"{$href}\">" . htmlentities($it['title']) . '</a>';
			$level = $nl;
		}
		
		for ($i = $level-1; $i > 0; $i--) {
			echo '</li>'.NL;
			echo $prefix . str_repeat(DOKU_TAB, $i*2 + 1) . '</ol>';
		}
			
		echo '</li>'.NL;
		echo $prefix . DOKU_TAB . DOKU_TAB . '</ol>'.NL;
		echo $prefix . DOKU_TAB . '</nav>'.NL;
		echo $prefix . '</aside>'.NL;
	}
}

/**
 * Print last change date 
 *
 * @author Sascha Leib <sascha@leib.be>
 *
 * @param  string $prefix to be added before each line
 *
 * @return void
 */
function my_lastchange($prefix = '') {
	
	global $lang;
	global $INFO;
	global $conf;
	global $_REQUEST;

	$lastmod = $INFO['lastmod'];
	$doAdmin = (array_key_exists('do', $_REQUEST) && $_REQUEST['do'] == 'admin');

	if (!$doAdmin && intval($lastmod) > 0) { // is valid date?

		$longDate = htmlentities(dformat($lastmod));

		echo $prefix . '<p class="docInfo">'.NL;
		echo $prefix . DOKU_TAB . '<bdi>' . $lang['lastmod'] . '</bdi>'.NL;
		echo $prefix . DOKU_TAB . '<time datetime="' . date('c', $lastmod) . '" title="' . $longDate . '"><span class="print-only">' . $longDate . '</span><span class="noprint">' . datetime_h($lastmod) . "</span></time>".NL;
		echo $prefix . '</p>'.NL;
	}
	
	/* user name for last change (is this really interesting to the visitor?) */
	/* echo $prefix .'<span class="editorname" tabindex="0">' . $lang['by'] . ' <bdi>' . editorinfo($INFO['editor']) . '</bdi></span>'.NL; */
}

/**
 * Returns a description list of the metatags of the current image
 *
 * @return string html of description list
 */
function my_img_meta($prefix = '') {
	global $lang;

	$format = '%Y-%m-%dT%T%z';	/* e.g. 2021-21-05T16:45:12+02:00 */

	$tags = tpl_get_img_meta();

	foreach($tags as $tag) {
		$label = $lang[$tag['langkey']];
		if(!$label) $label = $tag['langkey'] . ':';

		echo $prefix . "<tr><th>{$label}</th><td>";
		if ($tag['type'] == 'date') {
			echo '<time datetime="' . strftime($format, $tag['value']) . '">' . dformat($tag['value']) . '</time>';
		} else {
			echo hsc($tag['value']);
		}
		echo '</td></tr>'.NL;
	}
}

/**
 * Creates the Site logo image link
 *
 */
function my_sitelogo() {
	global $conf;

	// get logo either out of the template images folder or data/media folder
	$logoSize = array();
	$logo = tpl_getMediaFile(array(':logo.svg', ':wiki:logo.svg', ':logo.png', ':wiki:logo.png', 'images/sitelogo.svg'), false, $logoSize);
	tpl_link( my_homelink(),
		"<img src=\"{$logo}\" " . (is_array($logoSize) && array_key_exists(3, $logoSize) ? $logoSize[3] : '') . ' alt="' . htmlentities($conf['title']) . '" />', 'accesskey="h" title="[H]" class="logo"');
}

/**
 * Creates the various favicon and similar links:
 *
 * @param  string $color overwrite the theme color.
 *
 * @return null
 */
function my_favicons($color = null) {

	$logoSize = array();

	/* Theme color:
	if ($color == null) {
		
		// get the style config:
		$styleUtil = new \dokuwiki\StyleUtils();
		$styleIni = $styleUtil->cssStyleini();
		$replacements = $styleIni['replacements'];
		$color = $replacements['__theme_color__'];
		
		if ($color== null) { $color = '#2b73b7'; }
	}
	echo DOKU_TAB . "<meta name=\"theme-color\" content=\"" . $color . "\" />".NL; */

	// get the favicon:
	$link = tpl_getMediaFile(array(':favicon.ico', ':favicon.png', ':favicon.svg', ':wiki:favicon.ico', ':wiki:favicon.png', ':wiki:favicon.svg'), false, $logoSize);
	echo DOKU_TAB . "<link rel=\"icon\" href=\"{$link}\" />".NL;

	// Apple Touch Icon
	$logoSize = array();
	$link = tpl_getMediaFile(array(':apple-touch-icon.png', ':wiki:apple-touch-icon.png', 'images/apple-touch-icon.png'), false, $logoSize);
	echo DOKU_TAB . "<link rel=\"apple-touch-icon\" href=\"{$link}\" />".NL;

}

/**
 * inserts the Cookies banner, if appropriate.
 * This is based on Michal Koutny’s "cookielaw" plugin
 *
 * @param  string $prefix to be added before each line
 */
function my_cookiebanner($prefix = '') {

	// get the configuration settings:
	$msg = tpl_getConf('cookiemsg', '(no message configured)');
	$position = tpl_getConf('cookiepos', 'bottom');
	$link = tpl_getConf('cookielink', 'about:cookies');

	// if the cookie is already set or position is set to hide, do nothing.
	if ( isset($_COOKIE['cookielaw']) or $position == 'hide') {
		return;
	}
	
	// define the cookie icon:
	$svg = '<svg width="100%" height="100%" viewBox="0 0 64 64" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" xml:space="preserve" xmlns:serif="http://www.serif.com/" style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:1.5;"><path d="M17.272,19.443c6.035,-0.582 10.759,-5.673 10.759,-11.858c-0,-1.843 -0.42,-3.588 -1.168,-5.146c1.668,-0.289 3.385,-0.439 5.137,-0.439c16.557,-0 30,13.443 30,30c0,16.557 -13.443,30 -30,30c-16.557,-0 -30,-13.443 -30,-30c0,-0.925 0.042,-1.84 0.124,-2.743c1.061,0.31 2.183,0.476 3.345,0.476c6.025,0 11.011,-4.482 11.803,-10.29Z" style="fill:#d5944b;stroke:#26251d;stroke-width:4px;"/><circle cx="17.927" cy="41.07" r="3.488" style="fill:#443017;"/><circle cx="31.33" cy="30.835" r="3.488" style="fill:#443017;"/><circle cx="32" cy="49.883" r="3.488" style="fill:#443017;"/><circle cx="43.952" cy="41.07" r="3.488" style="fill:#443017;"/><circle cx="49.092" cy="27.347" r="3.488" style="fill:#443017;"/><circle cx="38.306" cy="16.056" r="3.488" style="fill:#443017;"/></svg>';
	
	// output the HTML code:
	echo $prefix . "<div id=\"cookiebanner\" class=\"cb_{$position}\">".NL;
	echo $prefix . DOKU_TAB . '<p class="cb_info">'.NL;
	echo $prefix . DOKU_TAB . DOKU_TAB . "<span class=\"cb_icon\">{$svg}</span>".NL;
	echo $prefix . DOKU_TAB . DOKU_TAB . "<span class=\"cb_msg\">{$msg}</span>".NL;
	echo $prefix . DOKU_TAB . '</p>'.NL;
	echo $prefix . DOKU_TAB . '<p class="cb_action">'.NL;
	echo $prefix . DOKU_TAB . DOKU_TAB . '<button>' . hsc(tpl_getLang('cookie_consent')) . '</button>'.NL;
	echo $prefix . DOKU_TAB . DOKU_TAB;
	if ( substr($link, 0, 7) == 'http://' || substr($link, 0, 8) == 'https://') {
		echo "<a href=\"{$link}\" target=\"_blank\">" . hsc(tpl_getLang('cookie_linktext')) . '</a>';
	} else {
		tpl_pagelink($link, tpl_getLang('cookie_linktext'));
	}
	echo $prefix . DOKU_LF . DOKU_TAB.'</p>'.NL;
	echo $prefix . '</div>'.NL;

}

/**
 * inserts the Languages menu, if appropriate.
 *
 * @author Sascha Leib <sascha@leib.be>
 * @author Andreas Gohr <andi@splitbrain.org>
 *
 * @param  string $prefix to be added before each line
 * @param  string $place the location from where it is called
 * @param  string $checkage should the age of the translation be checked?
 */
function my_langmenu($prefix, $place, $checkage = true) {

	global $INFO;
	global $conf;

	// the current page language: 
	$lang = $conf['lang'];

	/* get the config option: */
	$config = tpl_getConf('langmenu', 'none');

	/* only shw the menu if this is called from the right place */
	if ($config == $place) {

		/* collect the output: */
		$out = '';

		/* try to load the plugin: */
		$trans = plugin_load('helper','translation');
		
		/* plugin available? */
		if ($trans) {
			
			if (!$trans->istranslatable($INFO['id'])) return '';
			if ($checkage) $trans->checkage();

			[, $idpart] = $trans->getTransParts($INFO['id']);
			
			$asMenu = ($place == 'tb'); // display as menu only in toolbar!
			
			$out .= $prefix . "<div id=\"{$place}Languages\">".NL;
			
			// create the header item 
			
			if ($asMenu) { // show as menu (toolbar)
			
				// get the language name (in the local language)
				$langName = htmlentities($trans->getLocalName($lang));
				$langId = substr($lang, 0, 2);
			
				/* prepare the menu icon (note that the language code and name are embedded! */
				$svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'><title>{$langName}</title><path d='M20,2H4A2,2 0 0,0 2,4V22L6,18H20A2,2 0 0,0 22,16V4C22,2.89 21.1,2 20,2Z' /><text lengthAdjust='spacingAndGlyphs' x='50%' y='47%' dominant-baseline='middle' text-anchor='middle' style='font-size:50%'>{$langId}</text></svg>";
			
				// prepare the menu button:
				$out .= $prefix . DOKU_TAB . '<button id="langButton" aria-haspopup="menu" aria-controls="langMenuWrapper" aria-expanded="false">'.NL;
				$out .= $prefix . DOKU_TAB . DOKU_TAB . $svg . NL;
				$out .= $prefix . DOKU_TAB . DOKU_TAB . '<span class="sronly">' . $trans->getLang('translations') . '</span>'.NL;
				$out .= $prefix . DOKU_TAB . '</button>'.NL;
			
			} else { // show as list (sidebar)

				// show title (only if the option is configured)
				if (isset($trans->opts['title'])) {
					
					// get a localized headline text
					$headline = tpl_getLang('languages');
					
					// should a link to the about page be added?
					$about = $trans->getConf('about'); /* get the about link */
					if ($about !== '') {
						/* localized about links? */
						if ($trans->getConf('localabout')) {
							[, $aboutid] = $trans->getTransParts($about);
							[$about, ] = $trans->buildTransID($lang, $aboutid);
							$about = cleanID($about);
						}
						// build the link:
						$headline = html_wikilink($about, $headline);
					}
					/* complete the headline */
					$out .= $prefix . DOKU_TAB . "<h3><span>{$headline}</span></h3>".NL;
				} 
			}

			/* build the menu content */
			$out .= $prefix . DOKU_TAB . '<div id="langMenu' . ( $asMenu ? 'Wrapper" role="menu" style="display: none"' : 'List"') . '>'.NL;
			$out .= $prefix . DOKU_TAB . DOKU_TAB . '<ul id="lang' . ( $asMenu ? 'Menu" role="group"' : 'List"' ) . '>'.NL;

			// loop over each language and add it to the menu:
			foreach ($trans->translations as $t) {
				$l = ( $t !== '' ? $t : $lang );
				
				[$trg, $lng] = $trans->buildTransID($t, $idpart);
				$langName = $trans->getLocalName($lng);
				$lngId = substr($lng, 0, 2);
				$trg = cleanID($trg);
				$exists = page_exists($trg, '', false);
				$filter = tpl_getConf('langfilter', 'all');
				
				/* only show if translation exists? */
				if ($exists || $filter === 'all') {
					$class = 'wikilink' . ( $exists ? '1' : '2');
					$link = wl($trg);
					$current = ($lng == $lang);
					
					$out .= $prefix . DOKU_TAB . DOKU_TAB . DOKU_TAB .'<li' . ( $asMenu ? ' role="presentation"' : '' ). ( $current ? ' class="current"' : '' ) . '>'.NL;
					$out .= $prefix . DOKU_TAB . DOKU_TAB . DOKU_TAB . DOKU_TAB . "<a href=\"{$link}\" hreflang=\"{$lngId}\" class=\"{$class}\"" . ( $asMenu ? ' role="menuitem"' : '' ) . "><bdi lang=\"{$lngId}\">{$langName}</bdi></a>".NL;
					$out .= $prefix . DOKU_TAB . DOKU_TAB . DOKU_TAB . '</li>'.NL;
				}
			}

			// close all open elements:
			$out .= $prefix . DOKU_TAB . DOKU_TAB . '</ul>'.NL
				 .	$prefix . DOKU_TAB . '</div>'.NL
				 .	$prefix . '</div>'.NL;
		}
		echo $out; // done.
	}
}