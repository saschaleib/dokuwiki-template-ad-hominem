<?php
/**
 * Configuration settings for the Ad Hominem template
 *
 * @author     Sascha Leib <sascha@leib.be>
 */

$meta['darkmode'] = array('multichoice',
						'_choices' => array ('allow', 'disable'));

$meta['navtrail'] = array('multichoice',
						'_choices' => array ('none', 'text', 'link'));

$meta['printstyle'] = array('multichoice',
						'_choices' => array ('basic', 'compact'));

$meta['cookiepos'] = array('multichoice',
						'_choices' => array ('hide', 'top', 'bottom'));

/* there seems to be a bug with arrays. so here's a workaround: */

/*$meta['darkmode'] =  = array('string');

$meta['navtrail'] =  = array('string');

$meta['printstyle'] =  = array('string');

$meta['cookiepos'] = array('string');*/

/* the following are expected to be strings: */

$meta['cookiemsg'] = array('string');

$meta['cookielink'] = array('string');

$meta['homelink'] = array('string');

