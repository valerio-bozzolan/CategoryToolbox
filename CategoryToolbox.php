<?php
/*
 * CategoryToolbox - Access to Categories from Lua
 * Copyright (C) 2017 Valerio Bozzolan
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

defined( 'MEDIAWIKI' )
	or exit;

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CategoryToolbox' );

	// Lua Autoloaddable class
	$wgHooks['ScribuntoExternalLibraries'][] = function( $engine, array &$extraLibraries ) {
		$extraLibraries['mw.ext.cattools'] = 'CategoryToolbox_LuaLibrary';
		return true;
	};

	wfWarn(
		'Deprecated PHP entry point used for CategoryToolbox extension. Please use wfLoadExtension ' .
		'instead, see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);

	return true;
} else {
	die( 'This version of the CategoryToolbox extension requires MediaWiki 1.25+' );
}
