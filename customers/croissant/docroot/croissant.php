<?php
/*
 * Croissant Web Framework
*
* @author Tom Gordon <tom.gordon@apsumon.com>
* @copyright 2009-2017 Tom Gordon
*
*/
namespace Croissant;

/*
 * Customer-specific code to be used just prior to display rendering. Can be completely blank.
 * The filename must exactly match (including case) the "customer" folder name.
 *
 * Typically used to handle logged-in/logged-out variations
 */

if (User::UserID() != 0) {
	// user is logged in
} else {
	// user is not logged in
}
