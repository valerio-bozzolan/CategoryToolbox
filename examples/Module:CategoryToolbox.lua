local p = {}

local function wlink( page )
	if not page then
		return nil
	end
	return '[[:' .. mw.site.namespaces[ page.ns ].name .. ':' .. page.title .. ']]' ..
		' (' .. page.date .. ')'
end

function p.newestPage( frame )
	local args = frame:getParent( frame ).args
	return wlink( mw.ext.cattools.newestPage(
		args[1], -- category
		args[2], -- page
		args
	) )
end

function p.oldestPage( frame )
	local args = frame:getParent( frame ).args
	return wlink( mw.ext.cattools.oldestPage(
		args[1], -- category
		args[2], -- page
		args
	) )
end

function p.hasPage( frame )
	local args = frame:getParent( frame ).args
	args[3] = tonumber( args[3] )
	return mw.ext.cattools.hasPage(
		args[1], -- category
		args[2], -- page
		args[3]  -- depth
	) and 1 or nil
end


return p

