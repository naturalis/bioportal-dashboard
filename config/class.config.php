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

	}
