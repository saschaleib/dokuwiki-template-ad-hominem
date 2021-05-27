/**
 *  Page scripts for Ad Hominem Info Template
 *
 * @author     Sascha Leib <sascha@leib.be>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */
'use strict';

/* everything is contained in the $p namespace: */
$p = {
	
	/* called to initialize the entire script */
	init:	function() {
		
		$p.togglers.init();
	},

		
	togglers: {
		
		/* initialize togglers */
		init:	function() {

			const togglers = document.getElementsByClassName("toggle");

			Array.prototype.forEach.call(togglers, function (t) {
			
				/* mark it as open, unless it was already marked otherwise */
				if (!t.classList.contains('closed')) {
					t.classList.add('open');
				}
				if (!t.classList.contains('mclosed')) {
					t.classList.add('mopen');
				}
				
				/* add a callback to the toggler buttons */
				var btn = t.getElementsByClassName('tg_button');
				Array.prototype.forEach.call(btn, function (b) {
					b.addEventListener('click', $p.togglers._buttonCallback);
					b.classList.add('active');
				});

			});
		},
		
		/* callback for the toggler button click */
		_buttonCallback: function() {
			console.log("Button clicked: " + Date.now());
			
			var t = this.parentNode;
			var op =  t.classList.contains('open');
			t.classList.toggle('closed', op); t.classList.toggle('mclosed', op);
			t.classList.toggle('open', !op); t.classList.toggle('mopen', !op);

		}
	}
}

/* load the script when the DOM is ready */ 

window.addEventListener("DOMContentLoaded", $p.init);

/* list of REST APIs for different link types: */
var kRestURLs = {
	'wikilink1'	: '%basedir%lib/tpl/ad-hominem/rest/pageinfo.php?id=%id%&v=preview',
	'iw_wp'		: 'https://en.wikipedia.org/api/rest_v1/page/summary/%ln%',
	'iw_wpde' 	: 'https://de.wikipedia.org/api/rest_v1/page/summary/%ln%'
};

/**
 * Loads title info for internal and Wikipedia links
 *
**/

function loadWikiPageInfo() {
	var a = jQuery(this);
	var hi = jQuery.data(this, 'has-info');
	var url = null;
	
	/* only if the info hasn't been set yet: */
	if (hi == undefined || hi == '') {
		
		// remember that we are now working on it:
		jQuery.data(this, 'has-info', '0');

		for (var cls in kRestURLs) {
			if (a.hasClass(cls)) {
				url = kRestURLs[cls];
				break;
			}
		};

		if (url !== null) {
		
			/* modify the URLs: */
			var href = jQuery(this).attr('href');
			
			var rp = {
				'basedir': BASEDIR,
				'id': jQuery(this).data('wiki-id'),
				'ln': href.substring(href.lastIndexOf('/')+1)
			};
			
			for (var p in rp) {
				url = url.replace('%'+p+'%', rp[p]);
			}

			/* load the page info */
			jQuery.ajax({
				url:		url,
				context:	a,
				dataType:	'json',
				error:		function(xhr, msg, e) {
								console.error(msg);
							},
				success:	function(data, msg, xhr) {
								// build the new title for the element:
								jQuery(this).attr('title', data.title + "\n" + data.extract);
								jQuery.data(this, 'has-info', '1')
							},
				complete:	function() {
								if (jQuery.data(this, 'has-info') == '0') {
									jQuery.removeData(this, 'has-info');
								}
							}
			});
		}
	}
}
 
jQuery(function(){
	
	/* lazy-load link information to mouse-overs: */
	jQuery('main a:link').hover(loadWikiPageInfo);
});