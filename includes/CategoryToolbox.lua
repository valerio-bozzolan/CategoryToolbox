local cattools = {}
local php

function cattools.setupInterface( options )
	-- Remove setup function
	cattools.setupInterface = nil

	-- Copy the PHP callbacks to a local variable, and remove the global
	php = mw_interface
	mw_interface = nil

	-- Do any other setup here

	-- Install into the mw global
	mw = mw or {}
	mw.ext = mw.ext or {}
	mw.ext.cattools = cattools

	-- Indicate that we're loaded
	package.loaded['mw.ext.cattools'] = cattools
end

local function normalize_sortkey( sortkey )
	return sortkey == 'SPACE_PREFIXED' and ' ' or sortkey
end

local function normalize_args( args )
	return {
		newer   = args.newer,
		sortkey = normalize_sortkey( args.sortkey ),
		limit   = args.limit,
		offset  = args.offset
	}
end

--- Retrieve pages from a certain category filtered by a certain namespace
--
-- @param  category Category name with or without namespace
-- @param  ns       Namespace number or nil
-- @param  args     Additional arguments
-- @return bool
function cattools.pages( category, ns, args )
	local cat = mw.title.new( category )
	return category and php.categoryPages( cat.text, ns, normalize_args( args ) )
end

--- Obtain the latest page inserted in a certain category
--
-- @param  category Category name with or without namespace
-- @param  ns       Namespace number
-- @param  args     Additional arguments
-- @return bool
function cattools.newestPage( category, ns, args )
	if not category then error('missing category') end
	args.newer = true
	args.limit = 1
	return cattools.pages( category, ns, args )[ 1 ]
end

--- Obtain the latest page inserted in a certain category
--
-- @param  category category name with or without prefix
-- @param  ns       namespace number or nil
-- @param  args     additional arguments
-- @return table
function cattools.oldestPage( category, ns, args )
	if not category then error('missing category') end
	args.newer = false
	args.limit = 1
	return cattools.pages( category, ns, args )[ 1 ]
end

--- Find out if some categories have some pages.
--
-- @param categories table of some categories with or without prefix
-- @param pages      table of some page titles
-- @param depth      optional maximum recursion depth:
--                   	 0    : no recursion (default)
--                      -1    : deep recursion (very espensive)
--                   	 1..n : limited recursion (less expensive)
-- @param mode       optional way in which accept each page. Where:
--                   	'AND': must be in all the categories (default)
--                   	'OR' : just fine if it's in whatever category
-- @return table of matching page IDs
function cattools.havePages( categories, pages, depth, mode )

	if not categories then error('missing categories') end
	if not pages      then error('missing pages')      end

	if not depth or depth < -1 then
		error('wrong depth')
	end

	mode = mode or 'AND'
	if mode ~= 'AND' and mode ~= 'OR' then
		error('wrong mode')
	end

	-- use category names instead of categories with prefixes
	local cats = {}
	for k, category in pairs( categories ) do
		local category = mw.title.new( category ) or error('invalid category')
		cats[ #cats + 1 ] = category.text
	end

	-- retrieve page IDs from page titles
	local ids = {}
	for _, page in pairs( pages ) do
		local title = mw.title.new( page )
		if title then
			local id = title.id -- expensive
			if nil ~= id then
				ids[ #ids + 1 ] = id
			end
		end
	end

	return php.categoriesHavePages( cats, ids, depth, mode )
end

--- Find out if a category contains a certain page (eventually with recursion).
--
-- @param page     page title
-- @param category category name with or without prefix
-- @param depth    optional maximum recursion depth:
--                 	 0:    no recursion (default)
--                 	-1:    deep recursion (very expensive)
--                 	 1..n: limited recursion (less expensive)
-- @return bool
function cattools.hasPage( category, page, depth )

	if not category then error('missing category') end
	if not page     then error('missing page')     end

	if depth and depth > 0 or depth == -1 then
		local results = cattools.havePages( { category }, { page }, depth )
		return #results > 0
	end

	local c = mw.title.new( category ) or error('invalid category')
	local p = mw.title.new( page )

	if c and p then
		return php.categoryHasPage( c.text, p.namespace, p.text )
	end

	return false
end

return cattools
