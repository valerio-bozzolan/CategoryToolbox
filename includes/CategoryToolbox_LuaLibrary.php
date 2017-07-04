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

class CategoryToolbox_LuaLibrary extends Scribunto_LuaLibraryBase {
	function register() {
		$entry_points = [
			'hello' => [ $this, 'hello'],
		];

		$this->getEngine()->registerInterface(
			__DIR__ . '/CategoryToolbox.lua',
			$entry_points,
			[]
		);
	}

	public function hello() {
		return [ $this->convertArrayToLuaTable( [1, 2, 3, 4, 5, 'banana'] ) ];
	}

	/**
	 * This takes an array and converts it so, that the result is a viable lua table.
	 * I.e. the resulting table has its numerical indices start with 1
	 * If `$ar` is not an array, it is simply returned
	 * @param mixed $ar
	 * @return mixed array
	 * @see https://github.com/SemanticMediaWiki/SemanticScribunto/blob/master/src/ScribuntoLuaLibrary.php
	 */
	private function convertArrayToLuaTable( $ar ) {
		if ( is_array( $ar) ) {
			foreach ( $ar as $key => $value ) {
				$ar[$key] = $this->convertArrayToLuaTable( $value );
			}
			array_unshift( $ar, '' );
			unset( $ar[0] );
		}
		return $ar;
	}
}
