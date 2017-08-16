/*
 * Croissant Web Framework
 * 
 * @author Tom Gordon <tom.gordon@apsumon.com>
 * @copyright 2009-2017 Tom Gordon
 * 
 */
$(document).ready(function() {
	$('#debugoutput .debug_bar').mouseenter(function() {
		$('.debug_popout').stop(true, true).fadeIn(100);
	}).mouseleave(function(){
		$('.debug_popout').stop(true,true).fadeOut(500);
	});
});
