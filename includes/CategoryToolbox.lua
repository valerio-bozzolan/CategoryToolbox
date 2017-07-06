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
	return php.categoryHasPage( category, title.namespace, title.text )
end

function cattools.categoryPages( category, page_namespace, cl_sortkey_prefix, limit, offset )
	category = only_name(category, CAT_NS)
	return php.categoryPages( category, page_namespace, cl_sortkey_prefix, limit, offset )
end

return cattools

