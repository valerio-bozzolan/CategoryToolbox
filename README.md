# CategoryToolbox

**CategoryToolbox** is a MediaWiki extension that want to allow categories to be read from Lua.

## Installation
As every MediaWiki extension. It obviusly needs Scribunto in order to extend Lua.

## Lua entry points
To know if the `Foo` page from namespace zero in in the `Category name` category:

    mw.ext.cattools.categoryHasPage('Category name', 0, 'Foo')

## License
Copyright (C) 2017 Valerio Bozzolan

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details. You should have received a copy of the GNU General Public License along with this program. If not, see <http://www.gnu.org/licenses/>.
