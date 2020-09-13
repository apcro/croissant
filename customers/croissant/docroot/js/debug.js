/**
 * Croisssant Web Framework
 *
 * @copyright 2009-present Tom Gordon
 * @author Tom Gordon
 * @version 2.0
 */
document.addEventListener('DOMContentLoaded', event => {
	const popout = document.getElementsByClassName('debug_popout')[0];
	const debugBar = document.getElementById('debugoutput');
	if (typeof(debugBar) != undefined) {

		document.getElementsByClassName('debug_bar')[0].addEventListener('mouseenter', e => {
			popout.style.display = "flex";
		});
		document.getElementsByClassName('debug_bar')[0].addEventListener('mouseleave', e => {
			popout.style.display = "none";
		});
	}
});	