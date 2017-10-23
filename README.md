# CategoryToolbox

**CategoryToolbox** is a MediaWiki extension that want to allow categories to be read from Lua.

## Installation
As every MediaWiki extension. It obviusly needs Scribunto in order to extend Lua in MediaWiki.

## Features
To know the most recent file added to `Category:Category name`:

    mw.ext.cattools.categoryNewerPage('Category:Category name', 6)
    -- Yes: { ns = 6, title = 'Example.svg', date = 'YYYY-MM-DD 23:59:59' }
    -- No:  ?

To know the less recent article added to `Category:Category name`

    mw.ext.cattools.categoryOlderPage('Category:Category name', 0)
    -- Yes: { ns = 0, title = 'Free software', date = 'YYYY-MM-DD 23:59:59' }
    -- No:  ?

To know if the `Foo` page is in the `Category:Category name`:

    mw.ext.cattools.categoryHasPage('Category:Category name', 'Foo')
    -- Yes: true
    -- No:  false

The above, but recursively:

    mw.ext.cattools.isPageInCategoryRecursively('Foo', 'Category:Category name')
    -- Yes: integer (page ID)
    -- No:  false

To know if the pages "A", "B", "C" are in all the categories "X", "Y", "Z":

    mw.ext.cattools.arePagesInCategoryRecursively(
     { 'Foo', 'Bar' },
     { 'Category:X`, `Category:Y`, `Category:Z` }
    )
    -- All Yes:       { 1234 = true, 1235 = true }
    -- Only "Bar" no: { 1234 = true, 1235 = false }

Note that, where the input is a category, you can insert "Category:Foo" as well as only "Foo".

## License
Copyright (C) 2017 Valerio Bozzolan

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
