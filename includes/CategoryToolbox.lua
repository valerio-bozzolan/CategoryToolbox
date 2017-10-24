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

function cattools.categoryHasPage( category, page_title )
	category   = mw.title.new( category )
	page_title = mw.title.new( page_title )
	return category and page_title and php.categoryHasPage( category.text, page_title.namespace, page_title.text )
end

local function normalize_sortkey(sortkey)
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

function cattools.categoryPages( category, page_namespace, args )
	category = mw.title.new( category )
	return category and php.categoryPages( category.text, page_namespace, normalize_args(args) )
end

local function only_first( results )
	return results and results[1] or nil
end

function cattools.categoryNewestPage( category_name, page_namespace, args )
	args.newer = true
	args.limit = 1
	return only_first( cattools.categoryPages( category_name, page_namespace, args ) )
end

function cattools.categoryOldestPage( category_name, page_namespace, args )
	args.newer = false
	args.limit = 1
	return only_first( cattools.categoryPages( category_name, page_namespace, args ) )
end

function cattools.arePagesInCategoriesRecursively( page_titles, categories, mode )

	mode = 'AND' == mode and 'AND' or 'OR'

	-- retrieve page IDs from page titles
	local page_IDs = {}
	for _, page_title in pairs( page_titles ) do
		local title = mw.title.new( page_title )
		if title then
			local id = title.id -- expensive
			if nil ~= id then
				page_IDs[ #page_IDs + 1 ] = id
			end
		end
	end

	-- use category names instead of categories with prefixes
	local category_titles = {}
	for k, category in pairs( categories ) do
		local category = mw.title.new( category )
		if category then
			category_titles[ #category_titles + 1 ] = category.text
		end
	end

	return php.arePagesInCategories( page_IDs, category_titles, mode )
end

function cattools.isPageInCategoryRecursively( page_title, category, mode )
	return cattools.arePagesInCategoriesRecursively( { page_title }, { category }, mode )[1]
end

return cattools
