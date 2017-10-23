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
			'categoryHasPage'      => [ $this, 'categoryHasPage' ],
			'categoryPages'        => [ $this, 'categoryPages' ],
			'arePagesInCategories' => [ $this, 'arePagesInCategories' ]
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
	public function categoryHasPage( $category_name, $page_namespace, $page_title ) {
		$results = $this->selectCategoryLinks( $category_name, $page_namespace, $page_title );
		return [ 0 < $results->numRows() ];
	}

	/**
	 * Check if some pages are in some categories.
	 *
	 * @param array $page_IDs Page IDs
	 * @param array $category_names Category names without prefix
	 * @param array $mode 'AND' means that the page must be in all categories;
	 *                    'OR' means that the page must be at least in one category.
	 * @return mixed Lua table
	 */
	public function arePagesInCategories( $page_IDs, $category_names, $mode = 'AND' ) {
		$cf = new CategoryFinder;
		$cf->seed( $page_IDs, $category_names, $mode );
		$result = $cf->run();

		$this->incrementExpensiveFunctionCount();

		return [ self::toLuaTable( $result ) ];
	}

	/**
	 * Retrieve pages contained in a category.
	 *
	 * This is *not* a way to count the pages in a category. Use `mw.site.stats.pagesInCategory` instead.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param int $page_namespace Page namespace (number)
	 * @param array $args More arguments
	 * @param int $limit Result limit. Even if this method is intended only to retrieve a couple of sub-categories, it can be used also for pages.
	 * @param int $offset Result offset.
	 * @return mixed Lua table
	 */
	public function categoryPages($category_name, $page_namespace = null, $args = [] ) {
		return [
			self::categoryLinksToLuaTable(
				$this->selectCategoryLinks($category_name, $page_namespace, null, $args )
			)
		];
	}

	/**
	 * Having more than some results is not the intended use of this class. So here is the limit.
	 */
	const DEFAULT_LIMIT = 25;

	/**
	 * To retrieve what is linked into a category.
	 *
	 * @param string $category_name Category name (without prefix)
	 * @param int $page_namespace Restrict to a specific namespace (number)
	 * @param string $page_title Restrict to a specific page title (without prefix)
	 * @param array $args More arguments:
		* 'sortkey' => string|null: Can be used to filter category entries basing on which character index them.
		* 'newer'   => bool|null:   Can be used to order by the latest update.
	 	* 'limit'   => int|null:    Intended only to retrieve a couple of sub-categories, can be used to limit the result.
		* 'offset'  => int|null:    Can be used to skip n results.
	 * @return IResultWrapper|bool
	 */
	private function selectCategoryLinks($category_name, $page_namespace = null, $page_title = null, $args = [] ) {

		// Database fields to be selected
		$select = [
			'cl_type', // 'page' 'subcat' 'file'
			'page_id',
			'page_title',
			'page_namespace' // int
		];

		// Database fields to be eventually selected
		if ( isset( $args['newer'] ) ) {
			$select[] = 'cl_timestamp';
		}

		$conditions = [
			// Restrict to a certain category
			'cl_to' => self::space2underscore( $category_name ),

			// Join category and pages
			'cl_from = page_id'
		];

		// Restrict to a certain namespace?
		if ( null !== $page_namespace ) {
			$conditions['page_namespace'] = $page_namespace;
		}

		// Restrict to a certain page title?
		if ( null !== $page_title ) {
			$conditions['page_title'] = self::space2underscore( $page_title );
		}

		// Restrict to a certain prefix sortkey?
		if ( isset( $args['sortkey'] ) ) {
			$conditions['cl_sortkey_prefix'] = $args['sortkey'];
		}

		$options = [];

		// Order by timestamp?
		if ( isset( $args['newer'] ) ) {
			$options['ORDER BY'] = 'cl_timestamp ' . (
				$args['newer'] ? 'DESC' : 'ASC'
			);
		}

		// Limit results
		$options['LIMIT'] = isset( $args['limit'] )
			? (int)$args['limit']
			: self::DEFAULT_LIMIT;

		// Set an offset?
		if ( isset( $args['offset'] ) ) {
			$options['OFFSET'] = (int)$args['offset'];
		}

		// Where are we from? Boh!
		// Is there life on Mars? Boh!
		// Should a simple read-only query being considered expensive? Boh!
		// How many minutes have you wasted in thinking if the next line should be commented? 23!
		$this->incrementExpensiveFunctionCount();

		// The user want more?
		if ( null == $page_namespace || null == $page_title || $options['LIMIT'] > self::DEFAULT_LIMIT ) {
			$this->incrementExpensiveFunctionCount();
		}

		return $this->db->select( ['categorylinks', 'page'] , $select, $conditions, __METHOD__, $options );
	}

	/**
	 * Get a clean Lua array of objects from `::selectCategoryLinks()` results.
	 *
	 * @param IResultWrapper|bool $results Result from the Wikimedia\Rdbms\Database::select
	 * @return array Lua object
	 */
	private static function categoryLinksToLuaTable($results) {
		$rows = [];
		foreach ($results as $result) {
			$row = [
				'id'    => (int)$result->page_id,
				'ns'    => (int)$result->page_namespace,
				'type'  =>      $result->cl_type
			];

			// Cleaning title
			$row['title'] = self::underscore2space( $result->page_title );

			// Optional columns
			if ( isset( $result->cl_timestamp ) ) {
				$row['date'] = $result->cl_timestamp;
			}

			$rows[] = $row;
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
	 * Normalize a page title. E.g. "Category foo" → "Category_foo".
	 *
	 * @param string
	 * @return string
	 */
	private static function space2underscore($page_title) {
		return str_replace(' ', '_', $page_title);
	}

	/**
	 * Ripristinate the spaces. E.g. "Category_foo" → "Category foo".
	 *
	 * @param string
	 * @return string
	 */
	private static function underscore2space($page_title) {
		return str_replace('_', ' ', $page_title);
	}
}
