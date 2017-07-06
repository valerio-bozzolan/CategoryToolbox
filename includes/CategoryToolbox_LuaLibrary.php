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

	protected $db;

	function register() {

		$this->getEngine()->registerInterface( __DIR__ . '/CategoryToolbox.lua', [
			'categoryHasPage' => [ $this, 'categoryHasPage' ],
			'categoryPages'   => [ $this, 'categoryPages' ]
		] );

		// DB_REPLICA is not defined in MediaWiki 1.27.3
		$this->db = wfGetDB( defined('DB_REPLICA') ? DB_REPLICA : DB_MASTER );
	}

	/**
	 * To retrieve all the pages in a certain category.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param int $page_namespace Page namespace (number)
	 * @return mixed Lua object
	 */
	public function categoryPages($category_name, $page_namespace = null, $limit = 50, $offset = null) {
		return self::categoryLinksToLuaTable( $this->selectCategoryLinks($category_name, $page_namespace, null, $limit, $offset) );
	}

	/**
	 * To retrieve if a certain page is linked into a category.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param string $page_title Page title (without prefix)
	 * @param int $page_namespace Page namespace (number)
	 */
	public function categoryHasPage($category_name, $page_namespace, $page_title) {
		return self::toLuaBool( 1 === $this->selectCategoryLinks($category_name, $page_namespace, $page_title)->numRows() );
	}

	/**
	 * To retrieve what is linked into a category.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param int $page_namespace Restrict to a specific namespace (number)
	 * @param string $page_title Restrict to a specific page title (without prefix)
	 * @param int $limit Result limit. Even if this method is intended only to retrieve a couple of sub-categories, it should be used also for pages.
	 * @return IResultWrapper|bool
	 */
	private function selectCategoryLinks($category_name, $page_namespace = null, $page_title = null, $limit = 50, $offset = null) {

		$fields = [
			'cl_type',
			'page_id',
			'page_title',
			'page_namespace'
		];

		$conditions = [
			'cl_to' => $category_name,
			'cl_from = page_id'
		];

		if( null !== $page_namespace ) {
			$conditions['page_namespace'] = $page_namespace;
		}

		if( null === $page_title ) {
			// Too generic means more results
			$this->incrementExpensiveFunctionCount();
		} else {
			$conditions['page_title'] = $page_title;
		}

		$options = [
			'LIMIT' => $limit
		];

		if( null !== $offset ) {
			$options['OFFSET'] = $offset;
		}

		return $this->db->select( ['categorylinks', 'page'] , $fields, $conditions, __METHOD__, $options );
	}

	/**
	 * Giving a database result from `::selectCategoryLinks()` it returns a flat array.
	 *
	 * @param IResultWrapper|bool $results Result from the Wikimedia\Rdbms\Database::select
	 * @return array Lua object
	 */
	private static function categoryLinksToLuaTable($results) {
		$rows = [];
		foreach($results as $row) {
			$rows[] = [
				'id'   => (int)$row->page_id,
				'ns'   => (int)$row->page_namespace,
				'type' =>      $row->cl_type
			];
		}
		return self::toLuaTable( $rows );
	}

	/**
	 * This takes an array and converts it so, that the result is a viable Lua table.
	 * I.e. the resulting table has its numerical indices start with 1
	 * If `$ar` is not an array, it is simply returned.
	 *
	 * @param mixed $ar
	 * @return mixed Lua object
	 * @see https://github.com/SemanticMediaWiki/SemanticScribunto/blob/master/src/ScribuntoLuaLibrary.php
	 */
	private static function toLuaTable( $ar ) {
		if ( is_array( $ar ) ) {
			foreach ( $ar as $key => $value ) {
				$ar[$key] = self::toLuaTable( $value );
			}
			array_unshift( $ar, '' );
			unset( $ar[0] );
		}
		return $ar;
	}

	/**
	 * Workaround for a Scribunto_LuaLibraryBase bug with boolean values.
	 *
	 * @param int $is Boolean value
	 * @return mixed|null
	 */
	private static function toLuaBool($is) {
		return $is ? [ 1 ] : null;
	}
}
