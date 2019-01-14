<?php

	include_once('settings.php');

	class config {
		
		private static $nbaAddress = NBA_ADDRESS;
		private static $elasticsearchAddress = ELASTIC_SEARCH_ADDRESS;
		private static $bioportalRootUrl = BIOPORTAL_ROOT_URL;
		private static $dbAccess = [ "host"=>DB_ACCESS_HOST, "user"=>DB_ACCESS_USER, "password"=>DB_ACCESS_PASSWORD, "database"=>DB_ACCESS_DATABASE ];
		private static $searchUrls = [
			"bioportalSearchAdvancedSpecimen" => "/result?s_andOr=0&s_scientificName=%s&s_vernacularName=&s_family=&s_genusOrMonomial=&s_specificEpithet=&s_unitID=&s_sourceSystem=&s_collectionType=&s_typeStatus=&s_localityText=&s_phaseOrStage=&s_sex=&s_gatheringAgent=&s_collectorsFieldNumber=&s_kingdom=&s_phylum=&s_className=&s_order=&s_infraspecificEpithet=&t_andOr=0&t_scientificName=&t_vernacularName=&t_family=&t_genusOrMonomial=&t_specificEpithet=&t_sourceSystem=&t_kingdom=&t_phylum=&t_className=&t_order=&t_subgenus=&t_infraspecificEpithet=&m_andOr=0&m_scientificName=&m_vernacularName=&m_family=&m_genusOrMonomial=&m_specificEpithet=&m_sourceSystem=&m_collectionType=&m_kingdom=&m_phylum=&m_className=&m_order=&m_infraspecificEpithet=&op=Zoeken&form_id=ndabio_advanced_taxonomysearch",
			"bioportalSearchCollectionAndType" =>	"/result?s_andOr=0&s_scientificName=&s_vernacularName=&s_family=&s_genusOrMonomial=&s_specificEpithet=&s_unitID=&s_sourceSystem=&s_collectionType=%s&s_typeStatus=%s&s_localityText=&s_phaseOrStage=&s_sex=&s_gatheringAgent=&s_collectorsFieldNumber=&s_kingdom=&s_phylum=&s_className=&s_order=&s_infraspecificEpithet=&t_andOr=0&t_scientificName=&t_vernacularName=&t_family=&t_genusOrMonomial=&t_specificEpithet=&t_sourceSystem=&t_kingdom=&t_phylum=&t_className=&t_order=&t_subgenus=&t_infraspecificEpithet=&m_andOr=0&m_scientificName=&m_vernacularName=&m_family=&m_genusOrMonomial=&m_specificEpithet=&m_sourceSystem=&m_collectionType=&m_kingdom=&m_phylum=&m_className=&m_order=&m_infraspecificEpithet=&op=Search&form_id=ndabio_advanced_taxonomysearch",
			"bioportalSearchCollectorAndCollection" =>	"/result?s_andOr=0&s_scientificName=&s_vernacularName=&s_family=&s_genusOrMonomial=&s_specificEpithet=&s_unitID=&s_sourceSystem=&s_collectionType=%s&s_typeStatus=&s_localityText=&s_phaseOrStage=&s_sex=&s_gatheringAgent=%s&s_collectorsFieldNumber=&s_kingdom=&s_phylum=&s_className=&s_order=&s_infraspecificEpithet=&t_andOr=0&t_scientificName=&t_vernacularName=&t_family=&t_genusOrMonomial=&t_specificEpithet=&t_sourceSystem=&t_kingdom=&t_phylum=&t_className=&t_order=&t_subgenus=&t_infraspecificEpithet=&m_andOr=0&m_scientificName=&m_vernacularName=&m_family=&m_genusOrMonomial=&m_specificEpithet=&m_sourceSystem=&m_collectionType=&m_kingdom=&m_phylum=&m_className=&m_order=&m_infraspecificEpithet=&op=Search&form_id=ndabio_advanced_taxonomysearch"
		];	
		private static $storageUnitDbPath = STORAGEUNITS_SQLITE_DB_PATH;
		private static $lowerRanks = LOWER_RANKS;
		private static $ignorableFullScientificNames = SCI_NAMES_TO_IGNORE;
		private static $ignorableCollectors = COLLECTORS_TO_IGNORE;
		private static $sourceSystemsSpecimen = SOURCE_SYSTEMS_SPECIMEN;
		private static $sourceSystemsTaxon = SOURCE_SYSTEMS_TAXON;
		private static $sourceSystemsMultimedia = SOURCE_SYSTEMS_MULTIMEDIA;
		
		public static function elasticsearchAddress()
		{
			return self::$elasticsearchAddress;
		}

		static function bioportalRootUrl()
		{
			return self::$bioportalRootUrl;
		}
		
		static function nbaAddress()
		{
			return self::$nbaAddress;
		}
		
		static public function databasAccessParameters()
		{
			return (object)self::$dbAccess;
		}

		static public function searchUrls()
		{
			return (object)self::$searchUrls;
		}		

		static public function storageUnitDbPath()
		{
			return self::$storageUnitDbPath;
		}		

		static public function lowerRanks()
		{
			return (array)self::$lowerRanks;
		}		

		static public function ignorableFullScientificNames()
		{
			return (array)self::$ignorableFullScientificNames;
		}		

		static public function ignorableCollectors()
		{
			return (array)self::$ignorableCollectors;
		}		

		static public function sourceSystemsSpecimen()
		{
			return (array)self::$sourceSystemsSpecimen;
		}		

		static public function sourceSystemsTaxon()
		{
			return (array)self::$sourceSystemsTaxon;
		}		

		static public function sourceSystemsMultimedia()
		{
			return (array)self::$sourceSystemsMultimedia;
		}		
	}
