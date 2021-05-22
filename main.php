<?php
/**
 * Ad Hominem Template
 *
 * @link     https://ad.hominem.info/
 * @author   Sascha Leib <sascha@leib.be>
 * @author   Anika Henke <anika@selfthinker.org>
 * @author   Clarence Lee <clarencedglee@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

ini_set('display_errors', '1');

if (!defined('DOKU_INC')) die(); /* must be run from within DokuWiki */

require_once('my_template.php');

$hasSidebar = page_findnearest($conf['sidebar']);
$showSidebar = $hasSidebar && ($ACT=='show');

$htmlLang = ' lang="' . $conf['lang'] . ( $lang['direction'] != 'ltr' ? '" dir="'. $lang['direction'] : '') . '"';

?><!DOCTYPE html>
<html<?php echo $htmlLang ?>>
<head>
	<meta charset="utf-8" />
	<title><?php tpl_pagetitle() ?> &ndash; <?php echo str_replace(' ', ' ', strip_tags($conf['title'])) ?></title>
<?php my_metaheaders() ?>
	<meta name="viewport" content="width=device-width,initial-scale=1" />
<?php tpl_includeFile('meta.html') ?>
</head>
<body class="site <?php echo trim(tpl_classes()); ?>">
	<div id="skip-link">
		<a href="#main-content"><?php echo $lang['skip_to_content']; ?></a>
	</div>
	<div id="header-layout">
		<header>
			<h2 id="siteLogo"><? tpl_link( wl(), $conf['title'], 'accesskey="h" title="' . $conf['title'] . ' [H]"'); ?></h2>
			<div id="globalTools">
				<div id="gUserTools">
					<h3 class="sronly"><?php echo $lang['user_tools']; ?></h3>
					<ul>
<?php my_userinfo(str_repeat(chr(9),6)); ?>
					</ul>
				</div>
			</div>
			<div id="phSearch">
<?php include('tpl_searchform.php'); ?>
			</div>
			<div id="phTools"><!-- placeholder for additional tools --></div>
			<div id="phInclude"><?php tpl_includeFile('header.html') ?></div>
		</header>
	</div>
    <div id="main-layout"<?php echo ($showSidebar && $hasSidebar ? ' class="showSidebar hasSidebar"' : ''); ?>>
		<div id="sidebar">
			<h4 class="sronly"><?php echo $lang['sidebar'] ?></h4>
			<div class="content">
				<nav id="sbNavigation">
<!-- - - - - - - - - SIDEBAR CONTENT - - - - - - - -->
<?php
			tpl_flush();
			tpl_includeFile('sidebarheader.html');
			tpl_include_page($conf['sidebar'], true, true);
			tpl_includeFile('sidebarfooter.html');
?>				</nav>
<!-- - - - - - - - - END OF SIDEBAR  - - - - - - - -->
<?php		if($conf['breadcrumbs']) { my_breadcrumbs(str_repeat(chr(9),4)); } ?>
			</div>
		</div>
		<main>
<?php		my_toc(str_repeat(chr(9),3));
			if($conf['youarehere']) { my_youarehere(str_repeat(chr(9),3)); }
?>			<article id="main-content" itemscope itemtype="https://schema.org/Article">

<!-- - - - - - - - - ARTICLE CONTENT - - - - - - - -->
<?php tpl_flush() ?>
<?php tpl_includeFile('pageheader.html') ?>
<?php tpl_content(false) ?>
<?php tpl_includeFile('pagefooter.html') ?>
<!-- - - - - - - - - END OF ARTICLE  - - - - - - - -->

			</article>
			<footer>
				<p class="docInfo">
<?php my_lastchange(str_repeat(chr(9),5));
?>				</p>				
			</footer>
		</main>
	</div>
	<div id="docinfo-layout">
	</div>

	<div id="footer-layout">
		<footer>
			<div id="gMobileTools" class="ftSection">
				<h4><?php echo $lang['site_tools']; ?></h4>
				<?php echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']); ?>

			</div>
			<div id="ftInclude" class="ftSection">
<?php tpl_includeFile('footer.html'); ?>
			</div>
			<div id="ftSiteTools" class="ftSection">
				<h4><?php echo $lang['site_tools']; ?></h4>
				<ul>
					<?php echo (new \dokuwiki\Menu\SiteMenu())->getListItems('action ', false); ?>

				</ul>
			</div>
			<div id="ftPageTools" class="ftSection">
				<h4><?php echo $lang['page_tools']; ?></h4>
				<ul>
					<?php echo (new \dokuwiki\Menu\PageMenu())->getListItems('', false); ?>

				</ul>
			</div>
<?php include('tpl_footer.php') ?>
		</footer>
	</div>
	<div class="no"><?php tpl_indexerWebBug() /* provide DokuWiki housekeeping, required in all templates */ ?></div>
	<div id="screen__mode" class="no"></div><?php /* helper to detect CSS media query in script.js */ ?>
</body>
</html>
