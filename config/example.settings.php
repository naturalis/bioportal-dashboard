<?php

	class config {
		
		private static $elasticsearchAddress = '145.136.240.125:31016';
		private static $bioportalHomepage = 'http://bioportal.naturalis.nl/';
		private static $dbAccess = [ "host"=>"localhost", "user"=>"user", "password"=>"pass", "database"=>"nba_cache" ];

		 public static function elasticsearchAddress()
		{
			return self::$elasticsearchAddress;
		}

		static function bioportalHomepage()
		{
			return self::$bioportalHomepage;
		}
		
		static public function databasAccessParameters()
		{
			return (object)self::$dbAccess;
		}
		
	}
