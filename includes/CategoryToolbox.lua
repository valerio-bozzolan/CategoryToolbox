local cattools = {}
local php

local CAT_NS = 14

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

local function has_prefix(title, prefix)
	return nil ~= mw.ustring.find(title, prefix .. ':')
end

local function strip_prefix(title, prefix)
	local len = mw.ustring.len(prefix) + 2
	return mw.ustring.sub(title, len)
end

--[[
* @param string title Title in various form e.g. "Category:Foo", "Categoria:Foo", or only "Foo"
* @param int namespace Namespace number
* @return string Title without namespace prefix e.g. "Foo" without "Category:"
]]--
local function only_name(title, namespace)
	if title == nil then
		error('CategoryToolbox: nil as category?')
	end

	local ns = mw.site.namespaces[namespace]

	local ns_name, ns_canonical = ns.name, ns.canonicalName

	if has_prefix(title, ns_name) then
		return strip_prefix(title, ns_name)
	end

	if has_prefix(title, ns_canonical) then
		return strip_prefix(title, ns_canonical)
	end

	return title
end

function cattools.categoryHasPage( category, page_namespace, page_title )
	category = only_name(category, CAT_NS)
	return php.categoryHasPage( category, page_namespace, page_title )
end

function cattools.categoryHasTitleObject( category, title )
	title = title or mw.title.getCurrentTitle()
	if not title.namespace or not title.text then
		error("Not a valid title object")
	end
	return cattools.categoryHasPage( category, title.namespace, title.text )
end

local function normalize_sortkey(sortkey)
	return sortkey == 'SPACE_PREFIXED' and ' ' or sortkey
end

local function normalize_args(args)
	return {
		newer   = args.newer,
		sortkey = normalize_sortkey(args.sortkey),
		limit   = args.limit,
		offset  = args.offset
	}
end

function cattools.categoryPages( category, page_namespace, args )
	category = only_name( category, CAT_NS )
	return php.categoryPages( category, page_namespace, normalize_args(args) )
end

local function only_first( results )
	return results and results[1] or nil
end

function cattools.categoryNewerPage( category_name, page_namespace, args )
	args.newer = true
	args.limit = 1
	return only_first( cattools.categoryPages( category_name, page_namespace, args ) )
end

function cattools.categoryOlderPage( category_name, page_namespace, args )
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
		if nil ~= title then
			local id = title.id -- expensive
			if nil ~= id then
				page_IDs[ #page_IDs + 1 ] = id
			end
		end
	end

	-- use category names instead of categories with prefixes
	for k, category in pairs( categories ) do
		categories[ k ] = only_name( category, CAT_NS )
	end

	return php.arePagesInCategories( page_IDs, categories, mode )
end

function cattools.isPageInCategoryRecursively( page_title, category, mode )
	return cattools.arePagesInCategoriesRecursively( { page_title }, { category }, mode )
end

return cattools
