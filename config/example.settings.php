<?php

	class config {
		
		private static $elasticsearchAddress = '145.136.240.125:31016';
		private static $bioportalRootUrl = 'http://145.136.242.149/';
		private static $dbAccess = [ "host"=>"localhost", "user"=>"user", "password"=>"pass", "database"=>"nba_cache" ];
		private static $searchUrls = [ "bioportalSearchAdvancedSpecimen" =>
"/result?s_andOr=0&s_scientificName=%s&s_vernacularName=&s_family=&s_genusOrMonomial=&s_specificEpithet=&s_unitID=&s_sourceSystem=&s_collectionType=&s_typeStatus=&s_localityText=&s_phaseOrStage=&s_sex=&s_gatheringAgent=&s_collectorsFieldNumber=&s_kingdom=&s_phylum=&s_className=&s_order=&s_infraspecificEpithet=&t_andOr=0&t_scientificName=&t_vernacularName=&t_family=&t_genusOrMonomial=&t_specificEpithet=&t_sourceSystem=&t_kingdom=&t_phylum=&t_className=&t_order=&t_subgenus=&t_infraspecificEpithet=&m_andOr=0&m_scientificName=&m_vernacularName=&m_family=&m_genusOrMonomial=&m_specificEpithet=&m_sourceSystem=&m_collectionType=&m_kingdom=&m_phylum=&m_className=&m_order=&m_infraspecificEpithet=&op=Zoeken&form_id=ndabio_advanced_taxonomysearch" ];		
		
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

		static public function searchUrls()
		{
			return (object)self::$searchUrls;
		}		

	}
