/**
 *  Site scripts for Ad Hominem Info
 *
 * @author     Sascha Leib <sascha@leib.be>
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

/* list of REST APIs for different link types: */
var kRestURLs = {
	'wikilink1'	: '%basedir%lib/tpl/ad-hominem/json/pi.php?id=%id%&v=preview',
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