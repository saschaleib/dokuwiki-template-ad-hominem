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

$meta['cookiemsg'] = array('string');

$meta['cookielink'] = array('string');

$meta['homelink'] = array('string');

$meta['langmenu'] = array('multichoice',
						'_choices' => array ('tb', 'sb'));

$meta['langfilter'] = array('multichoice',
						'_choices' => array ('all', 'existing'));
