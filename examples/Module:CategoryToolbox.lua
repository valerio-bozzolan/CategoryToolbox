-------------------------------------------------------------------
-- This is an example of a [[Module:CategoryToolbox]]
-- that can be useful to use `mw.ext.cattools` tools from wikicode.
--
-- @url    https://github.com/valerio-bozzolan/CategoryToolbox
-- @author [[:w:it:Utente:Valerio Bozzolan]]
-------------------------------------------------------------------

local p = {}

-- Get a wikilink of a page from a CategoryToolbox title object.
local function wlink( page )
	if not page then
		return nil
	end
	return '[[:' .. mw.site.namespaces[ page.ns ].name .. ':' .. page.title .. ']]' ..
		' (' .. page.date .. ')'
end

-- Check if a category has a page (no recursion).
function p.categoryHasPage( frame )
	local args = frame:getParent( frame ).args
	return mw.ext.cattools.categoryHasPage(
		args[1], -- category
		args[2]  -- page
	) and 1 or nil
end

-- Get a CategoryToolbox title object of the newest page in a category.
function p.categoryNewestPage( frame )
	local args = frame:getParent( frame ).args
	return wlink( mw.ext.cattools.categoryNewestPage(
		args[1], -- category
		args[2], -- page
		args
	) )
end

-- Get a CategoryToolbox title object of the oldest page in a category.
function p.categoryOldestPage( frame )
	local args = frame:getParent( frame ).args
	return wlink( mw.ext.cattools.categoryOldestPage(
		args[1], -- category
		args[2], -- page
		args
	) )
end

-- Check if a page is in a certain category (recursively).
function p.isPageInCategoryRecursively( frame )
	local args = frame:getParent( frame ).args
	return mw.ext.cattools.isPageInCategoryRecursively(
		args[1], -- page
		args[2], -- category
		args[3]  -- mode
	)
end

return p
