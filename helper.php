<?php
/**
 * Helper Component for the Wrap Plugin
 *
 * @license	GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author	 Anika Henke <anika@selfthinker.org>
 * @author	 Sascha Leib <sascha.leib(at)kolmio.com>
 */

class helper_plugin_adhoctags extends DokuWiki_Plugin {

	/* list of languages which normally use RTL scripts */
	static protected $rtllangs = array('ar','dv','fa','ha','he','ks','ku','ps','ur','yi','arc');
	/* list of right-to-left scripts (may override the language defaults to rtl) */
	static protected $rtlscripts = array('arab','thaa','hebr','deva','shrd');
	/* selection of left-to-right scripts (may override the language defaults to ltr) */
	static protected $ltrscripts = array('latn','cyrl','grek','cyrs','armn');

	/* Helper plugins should return info about the methods supported. */
	public function getMethods() {
		$result = array();
		$result[] = array(
			'name' => 'buildAttributes',
			'desc' => 'writes out element attributes',
			'params' => array(
				'data' => 'string',
				'custom' => 'array',
				'addClass (optional)' => 'string',
				'mode (optional)' => 'string'
			),
			'return' => array('attributes' => 'array')
		);
		return $result;
	}

	/**
	 * build attributes (write out classes, width, lang and dir)
	 */
	function buildAttributes($data, $custom, $addClass='', $mode='xhtml') {

		$attList = $this->getAttributes($data, $custom);
		$out = '';
		
		//dbg('attList=' . print_r($attList, true));

		if ($mode=='xhtml') {
			
			foreach($attList as $key => $val) {
				
				switch ($key) {

					/* common HTML attributes (always enabled) */

					case 'id':			/* id */
					case 'title':		/* title */
					case 'style':		/* style */
					case 'lang':		/* language */
					case 'dir':			/* direction */
					case 'tabindex':	/* tabindex */
					case 'is':			/* is */
						$out .= ' '.$key.'="'.$val.'"';
						break;

					case 'class':		/* custom classes */
						if (trim($val) !== '') {
							$out .= ' '.$key.'="'.hsc($val).' '.$addClass.'"';
						}
						break;

					case 'hidden':		/* hidden */
						$out .= ' '.$key . ( is_null($val) ? '' : '="'.hsc($val).'"' );
						break;

					case 'width':	/* custom width (deprecated, not compatible with 'style'!)*/
						if(!isset($attList['style'])) {
							if (strpos($val,'%') !== false) {
								$out .= ' style="width: '.hsc($val).';"';
							} else {
								// anything but % should be 100% when the screen gets smaller
								$out .= ' style="width: '.hsc($val).'; max-width: 100%;"';
							}
						}
						break;

					/* specific HTML attributes (only allowed in certain contexts) */
					case 'datetime':		/* datetime attribute (only for <time> elements) */
					case 'rel':				/* link rel */
					case 'target':			/* link target */
					case 'hreflang':		/* link language */
						if (in_array($key, $custom)) {
							$out .= ' '.$key.'="'.hsc($val).'"';
						}
						break;
						
					case 'href':			/* link URL */
						if ($this->getConf('allowJSLinks') == '0'
						 && substr($val, 0, 11) === 'javascript:') {
							 break;
						} elseif (in_array($key, $custom)) {
							$out .= ' '.$key.'="'.hsc($val).'"';
						}
						break;

					case 'open':		/* open switch attribute (only for <details>) */
						if (in_array($key, $custom)) {
							$out .= ' '.$key . ( is_null($val) ? '' : '="'.hsc($val).'"' );
						}
						break;
						
					default:
						dbg('Unknown attribute: ' . $key);
				}
			}

			// special case: no class name specified, but there is one passed down from a plugin:
			if($addClass !== '' && !isset($attr['class'])) {
				$out .= ' class="'.$addClass.'"';
			}
			
		}

		return $out;
	}

	/**
	 * get attributes (pull apart the string between '<wrap' and '>')
	 *  and identify classes, width, lang and dir
	 *
	 * @author Anika Henke <anika@selfthinker.org>
	 * @author Christopher Smith <chris@jalakai.co.uk>
	 *   (parts taken from http://www.dokuwiki.org/plugin:box)
	 */
	function getAttributes($data, $custom, $useNoPrefix=true) {

		//dbg('getAttributes("$data="' . $data . '",  $custom=' . print_r($custom, true));

		// store the attributes here:
		$attr = array();
		// split up the attributes string (keep quoted and square brackets intact):
		$tokens = $this->tokenizeAttr($data);
		
		dbg('$tokens = ' . print_r($tokens, true));

		foreach ($tokens as $token) {

			//get width (depracated, may be removed later!)
			if (preg_match('/^\d*\.?\d+(%|px|em|rem|ex|ch|vw|vh|pt|pc|cm|mm|in)$/', $token)) {
				$attr['width'] = $token;
				continue;
			}

			//get language attribute
			if (preg_match('/^:([a-z\-]+)/', $token)) {
				$attr['lang'] = trim($token,':');
				continue;
			}

			//get id (IDs can not start with a number!)
			if (preg_match('/^#([A-Za-z]\w+)/', $token)) {
				$attr['id'] = trim($token,'#');
				continue;
			}

			// get title (any quoted string)
			if (preg_match('/^\"(.*)\"$/', $token)) {
				$attr['title'] = trim($token,'"');
				continue;
			}

			/* custom attributes */
			if (preg_match('/^\[([^\]]+)\]$/', $token)) {
				
				$cAttr = $this->parseCustomAttribute(trim($token,'[]'));
				//dbg('$token = ' . $token . ', $cAttr = ' . print_r($cAttr, true));
				if ($cAttr) {
					$attr[$cAttr[0]] = ( isset($cAttr[1]) ? $cAttr[1] : null );
				}
				continue;
			}

			//add to list of classes if it matches the pattern for class names:
			if (preg_match('/^[\w\d\-\\_]*$/',$token)) {
				$attr['class'] = (isset($attr['class']) ? $attr['class'].' ' : '') . $token;
			}
		}

		 /* improved RTL detection to make sure it covers more cases: */
		if(!array_key_exists('dir', $attr) && array_key_exists('lang', $attr) && $attr['lang'] !== '') {

			// turn the language code into an array of components:
			$arr = explode('-', $attr['lang']);

			// is the language iso code (first field) in the list of RTL languages?
			$rtl = in_array($arr[0], self::$rtllangs);

			// is there a Script specified somewhere which overrides the text direction?
			$rtl = ($rtl xor (bool) array_intersect( $rtl ? self::$ltrscripts : self::$rtlscripts, $arr));

			$attr['dir'] = ( $rtl ? 'rtl' : 'ltr' );
		}

		return $attr;
	}

	/**
	 * Parse custom attributes into key-value pairs
	 *
	 * @author Sascha Leib <sascha.leib(at)kolmio.com>
	 */
	function parseCustomAttribute($token) {
		global $conf;

		$kvp = explode('=', $token, 2);
		//dbg('$kvp = ' . print_r($kvp, true));
		
		$r = null; // return value
		if ($kvp && count($kvp) > 0) {
			
			$kvp[0] = strtolower($kvp[0]); // always use lowercase name!
			$val = ( count($kvp) > 1 ? $kvp[1] : $kvp[0] );

			// only explicitely allowed attribute names are passed on:
			switch ($kvp[0]) {
			case 'datetime': // datetime (for <time> only)
				if (preg_match('/^[\w\d\s_+-:]+$/i', $val)) {
					$r = $kvp;
				}
				break;
			case 'dir': // direction override
				if (in_array($val, array('auto', 'rtl', 'ltr'))) {
					$r = $kvp;
				}
				break;
			case 'hidden': // hidden element
				if (count($kvp) == 1 || in_array($val, array('', 'hidden', 'until-found'))) {
					$r = $kvp;
				}
				break;
			case 'href': // links in <a> elements
				if (preg_match('/^[\w\.:;%#~@\/\(\)\']+$/i', $val)) {
					$r = $kvp;
				}
				break;
			case 'rel': // rel in <a> elements
				if (preg_match('/^[\w\d\-_]+$/i', $val)) {
					$r = $kvp;
				}
				break;
			case 'hreflang': // link language in <a> elements
				if (preg_match('/^[\w\-]+$/i', $val)) {
					$r = $kvp;
				}
				break;
			case 'is': // custom elements
				if (preg_match('/^[a-z]+\-\w+$/i', $val)) {
					$r = $kvp;
				}
				break;
			case 'open': // disclosure only
				if (count($kvp) == 1 || in_array($val, array('','open'))) {
					$r = $kvp;
				}
				break;
			case 'role': // ARIA Role
				if (preg_match('/^\w+$/', $val)) {
					$r = $kvp;
				}
				break;
			case 'style': // style attribute (experimental, not compatible with 'width'!)
				if ($this->getConf('allowStyle') == '1'
				 && preg_match('/^[\w\-\_\'#\;\:\.\(\)]+$/', $val)) {
					$r = $kvp;
				}
				break;
			case 'tabindex': // tabindex must be integer
				if (filter_var($val, FILTER_VALIDATE_INT)) {
					$r = $kvp;
				}
				break;
				
			case 'itemid': // embedded microdata attributes (simplified check, not fully implemented yet)
			case 'itemprop':
			case 'itemref':
			case 'itemscope': // may be empty
			case 'itemtype':
				if (count($kvp) == 1 || preg_match('/^[\w:%~/]+$/', $val)) {
					$r = $kvp;
				}
				break;
				
			default: // one more special case:
				
				if (preg_match('/^data-[\w]+$/i', $kvp[0])) {  // data-* attributes
					$r = $kvp;
				}
			}
		}
		return $r;
	}

	/**
	 * Split the input data into suitable tokens
	 *
	 * @author Sascha Leib <sascha.leib(at)kolmio.com>
	 */
	 function tokenizeAttr($data) {
		 
		// key characters:
		define('SPACECHAR', ' ');
		define('QUOTECHAR', '"');
		define('BRACHAR',   '[');
		define('KETCHAR',   ']');
		define('ESCAPECHAR','\\');

		// this parser is a simple state-machine:
		define('NEITHER',   0);
		define('INQUOT',    1);
		define('INBRACKET', 2);

		$result = array();
		$token = ''; // temporary storage of each item
		$escaped = false; // should the next character be treated "as is"?
		$state = NEITHER; // parser state

		// loop over all characters:
		forEach(str_split($data) as $c) {
			
			switch($c) {
			 case SPACECHAR: // _
			
				if (!$escaped && $state == NEITHER) {
					if (trim($token)!==''){array_push($result, $token);}
					$token = '';
				} else {
					$token = $token . $c;
					$escaped = false;
				}
				break;

			 case QUOTECHAR: // "
				
				switch ($state) {
				 case NEITHER:
					if (trim($token)!==''){array_push($result, $token);}
					$state = INQUOT;
					$token = $c;
					break;
					
				 case INQUOT:
					$token .= $c;
					array_push($result, $token);
					$state = NEITHER;
					$token = '';
					break;
			
				 case INBRACKET:
					$token .= $c;
					break;
			
				 default:
					// should never happen!
				}
				break;
				
				
			 case BRACHAR: // [

				if (!$escaped && $state == NEITHER) {
					
					if (trim($token)!==''){array_push($result, $token);}
					$token = $c;
					$state = INBRACKET;				
				
				} else {
					$token .= $c;
				}
				break;

			 case KETCHAR: // ]
			
				if (!$escaped && $state == INBRACKET) {
					
					$token .= $c;
					array_push($result, $token);
					$token = '';
					$state = NEITHER;
					
				} else {
					$token .= $c;
				}
				break;

			 case ESCAPECHAR: // \
			
				if (!$escaped) {
					// next character is escaped:
					$escaped = true;
				} else {
					$token .= $c;
				}
				break;

			 default:
				$token .= $c;
			}
		}

		if (trim($token)!=='') {array_push($result, $token);}
		
		return $result;
	 }

	/* Does anyone really need ODT ? */
}
