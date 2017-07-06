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

	/**
	 * Having more than some results is not the intended use of this class. So here is the limit.
	 *
	 * Note: Optimize yourself your results specifing all the useful filters as e.g. `$cl_sortkey_prefix`.
	 */
	const DEFAULT_LIMIT = 25;

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
	 * Check if a category contains a certain page.
	 *
	 * To maintain the query clean you always have to specify the namespace and you have
	 * to remove the prefix from the wanted page title.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param int $page_namespace Page namespace (number)
	 * @param string $page_title Page title (without prefix)
	 * @return mixed Lua boolean
	 */
	public function categoryHasPage($category_name, $page_namespace, $page_title) {
		return [
			1 === $this->selectCategoryLinks($category_name, $page_namespace, $page_title)->numRows()
		];
	}

	/**
	 * Retrieve pages contained in a category.
	 *
	 * When you want to retrieve only the top pages
	 * (e.g. when the category is callle from these pages as `[[Category:Name| ]]` or whatever)
	 * just specify the sortkey prefix using `$cl_sortkey_prefix` (e.g. specify a single space).
	 *
	 * This is *not* a way to count the pages in a category. Use `mw.site.stats.pagesInCategory` instead.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param int $page_namespace Page namespace (number)
	 * @param string $cl_sortkey_prefix Value of the `categorylinks`.`cl_sortkey_prefix` field. Usually it is '' or ' '. E.g. if set to ' ' it can retrieves only sub-pages calling `[[Category:Name| ]]`
	 * @param int $limit Result limit. Even if this method is intended only to retrieve a couple of sub-categories, it can be used also for pages.
	 * @param int $offset Result offset.
	 * @return mixed Lua table
	 */
	public function categoryPages($category_name, $page_namespace = null, $cl_sortkey_prefix = null, $limit = null, $offset = null) {
		return [
			self::categoryLinksToLuaTable(
				$this->selectCategoryLinks($category_name, $page_namespace, null, $cl_sortkey_prefix, $limit, $offset)
			)
		];
	}

	/**
	 * To retrieve what is linked into a category.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param int $page_namespace Restrict to a specific namespace (number)
	 * @param string $page_title Restrict to a specific page title (without prefix)
	 * @param string $cl_sortkey_prefix Value of the `categorylinks`.`cl_sortkey_prefix` field. Usually it is '' or ' '.
	 * @param int $limit Result limit. Even if this method is intended only to retrieve a couple of sub-categories, it can be used also for pages.
	 * @param int $offset Result offset.
	 * @return IResultWrapper|bool
	 */
	private function selectCategoryLinks($category_name, $page_namespace = null, $page_title = null, $cl_sortkey_prefix = null, $limit = null, $offset = null) {

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

		if( null !== $page_title ) {
			$conditions['page_title'] = $page_title;
		}

		if( null !== $cl_sortkey_prefix ) {
			$conditions['cl_sortkey_prefix'] = $cl_sortkey_prefix;
		}

		if( null === $limit ) {
			$limit = self::DEFAULT_LIMIT;
		}

		if( null == $page_title && null === $cl_sortkey_prefix || $limit > self::DEFAULT_LIMIT ) {
			// I don't know why you are here. We encourage smaller result sets!
			$this->incrementExpensiveFunctionCount();
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
	 * Get a clean Lua array of objects from `::selectCategoryLinks()` results.
	 *
	 * @param IResultWrapper|bool $results Result from the Wikimedia\Rdbms\Database::select
	 * @return array Lua object
	 */
	private static function categoryLinksToLuaTable($results) {
		$rows = [];
		foreach($results as $result) {
			$rows[] = [
				'id'    => (int)$result->page_id,
				'title' =>      $result->page_title,
				'ns'    => (int)$result->page_namespace,
				'type'  =>      $result->cl_type
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
}
