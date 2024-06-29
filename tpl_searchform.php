<?php
/**
 * Search Form Include
 */

// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();
?>				<form action="<?php echo wl(); ?>" method="get" role="search" class="search doku_form" id="dw__search" accept-charset="utf-8" style="padding-left: 705px;">
					<input type="hidden" name="do" value="search" />
					<input type="hidden" name="id" value="<?php echo htmlentities($ID); ?>" />
					<div class="search-field">
						<label for="qsearch__in" class="sronly"><?php echo htmlentities($lang['btn_search']); ?></label>
						<input name="q" type="text" class="edit" title="<?php echo $lang['btn_search']; ?> [F]" accesskey="f" placeholder="<?php echo $lang['btn_search']; ?>" autocomplete="off" id="qsearch__in" value="<?php echo ($ACT === 'search' ? $QUERY : ''); ?>" /><button value="0" type="reset" title="<?php echo htmlentities($lang['btn_delete']); ?>"><?php echo htmlentities($lang['btn_delete']); ?>"</button><button value="1" type="submit" title="<?php echo htmlentities($lang['btn_search']); ?>"><?php echo htmlentities($lang['btn_search']); ?>"</button>
					</div>
					<div class="search-popup">
						<div id="qsearch__out" class="ajax_qsearch JSpopup"></div>
					</div>
				</form>
