<?php
/**
 * Configuration settings for the Ad Hominem template
 *
 * @author     Sascha Leib <sascha@leib.be>
 */

$meta['darkmode'] = array(
						'multichoice',
						'_choices' => array ('allow', 'disable'));
$meta['navtrail'] = array(
						'multichoice',
						'_choices' => array ('none', 'text', 'link'));

$meta['cookiepos'] = array(
						'multichoice',
						'_choices' => array ('hide', 'top', 'bottom'));

$meta['cookiemsg'] = array('');

$meta['cookielink'] = array('string');

$meta['homelink'] = array('string');
