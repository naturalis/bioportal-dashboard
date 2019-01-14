<?php

	define('ELASTIC_SEARCH_ADDRESS','145.136.242.167:5000');
	define('BIOPORTAL_ROOT_URL','http://145.136.242.149/');
	define('NBA_ADDRESS','http://api.biodiversitydata.nl');
	define('DB_ACCESS_HOST','localhost');
	define('DB_ACCESS_USER','user');
	define('DB_ACCESS_PASSWORD','pass');
	define('DB_ACCESS_DATABASE','nba_cache');

	define('LOWER_RANKS',[ "species", "subspecies", "subsp", "ssp." ]); // [ "species", "subspecies", "var.", "subsp", "forma", "cv.", "f.", "subvar."]
	define('SCI_NAMES_TO_IGNORE',[ "Gen. indet. sp. indet.","GEN.INDET. SP.INDET."]);
	define('COLLECTORS_TO_IGNORE',[ "Unknown", "Unreadable", "Stud bio" ]);
	define('SOURCE_SYSTEMS_SPECIMEN',["BRAHMS","CRS"]);
	define('SOURCE_SYSTEMS_TAXON',["COL","NSR"]);
	define('SOURCE_SYSTEMS_MULTIMEDIA',["BRAHMS","CRS","NSR"]);

