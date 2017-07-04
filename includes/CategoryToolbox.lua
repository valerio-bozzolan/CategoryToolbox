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

-- ask
function cattools.hello( parameters )
	return php.hello( parameters )
end

return cattools

