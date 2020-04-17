<?php

	class storageUnits
	{
		private $dbfile;
		private $db;
		private $queries;

		public function __construct() 
		{
			$this->db = new stdClass;
			$this->setQueries();
			$this->setDutchlands();
		}
		
		public function setDbParams( $hostOrObject, $user=null, $password=null, $database=null )
		{
			if ( is_object($hostOrObject) )
			{
				$this->_setDbParams( $hostOrObject->host, $hostOrObject->user, $hostOrObject->password, $hostOrObject->database );
			}
			else
			if ( is_array($hostOrObject) )
			{
				$this->_setDbParams( $hostOrObject['host'], $hostOrObject['user'], $hostOrObject['password'], $hostOrObject['database'] );
			}
			else
			{
				$this->_setDbParams( $hostOrObject, $user, $password, $database );
			}
		}

		public function connectDb()
		{
			if (!isset($this->db->connection))
			{
				$this->db->connection = mysqli_connect($this->db->host,$this->db->user,$this->db->password,$this->db->database);
			}
		}

		public function doQuery($query)
		{
			$r=[];

			if ($result = mysqli_query($this->db->connection,$query))
			{
				while ($row=mysqli_fetch_assoc($result))
				{
					$r[]=$row;
				}    
				mysqli_free_result($result);
			}

			return $r;
		}

		public function getQuery($label)
		{
			return str_replace(['%DUTCHLANDS%'], [$this->getDutchlands()], $this->queries->{$label});
		}

		private function setDutchlands()
		{
			$this->dutchlands=
				[ "Netherlands",
				  "Nederland",
				  "NETHERLANDS",
				  "Holland",
				  "Ned",
				  "Netherlands, North Sea",
				  "Nederland en België",
				  "Ned.",
				  "Nederland; België",
				  "Netherlands (prob.)",
				  "West Nederland",
				  "The Netherlands",
				  "the Netherlands",
				  "Netherlands, The",
				  "Country code: NL",
				  "Nederl.",
				  "NEDERLAND",
				  "NETHERLANDS-",
				  "Netherlands  (prob.)",
				  "Nederland (Z-H)",
				  "Netherlands, Waddenzee",
				  "NEDELRAND",
				  "NL.",
				  "NL",
				  "Netherlands ?",
				  "\"Netherlands\"",
				  "Netherlands, Dutch coast",
				  "The Nertherlands",
				  "The Nethrlands",
				  "NEDERLAND-Z.",
				  "Nederlandse kust",
				  "Amsterdam",
				  "NETHERLANDS ?",
				  "Nederl",
				  "Netherlands, Dutch beaches",
				  "Netherlands, Noordzee",
				  "Netherlans",
				  "(Nederland)",
				  "NEDERLAND-",
				  "NEDERLAND-O.",
				  "Nederland & België",
				  "Nederland (N.L.)",
				  "Nederland (ZH)",
				  "Nederland / Belgie",
				  "Nederland?",
				  "Netherlands/Germany",
				  "Unknown"
				  ];

			$this->dutchlands= array_map("utf8_encode", $this->dutchlands);
		}

		public function getDutchlands($imploded=true)
		{
			return $imploded ? "'".implode("','", $this->dutchlands)."'" : $this->dutchlands;
		}

		private function setQueries()
		{
			$this->queries = new stdClass;

			$this->queries->sumPerColl_withIndivCount =
				"select INST_COLL_SUBCOLL,count(*) as doc_count,sum(individualCount) as indiv_count from storageunits where individualCount is not null group by INST_COLL_SUBCOLL order by count(*) desc";

			$this->queries->docCountPerColl_withoutIndivCount =
				"select INST_COLL_SUBCOLL,count(*) as doc_count from storageunits where individualCount is null group by INST_COLL_SUBCOLL order by count(*) desc";

			$this->queries->docCountPerColl_withoutIndivCount_mounts =
				"select Mount,count(*) as doc_count from storageunits where INST_COLL_SUBCOLL = '%s' and individualCount is null group by Mount";

			$this->queries->docCountPerNetherlandsProvinces =
				"select trim(stateProvince) as stateProvince,count(*) as doc_count from storageunits where country in ( %DUTCHLANDS% ) and stateProvince not null group by trim(stateProvince) limit 10";

			$this->queries->docCountCollectionsPerProvince =
				"select INST_COLL_SUBCOLL,count(*) as doc_count from storageunits where country in ( %DUTCHLANDS% ) and stateProvince = '%s'  group by INST_COLL_SUBCOLL";

			$this->queries->docCountMountsPerCollectionPerProvince =
				"select Mount,count(*) as doc_count from storageunits where country in ( %DUTCHLANDS% ) and stateProvince = '%s' and INST_COLL_SUBCOLL = '%s' group by Mount";

			$this->queries->catNumberCardinality = "select count(distinct catalogNumber) as doc_count from storageunits";		
		}

		private function _setDbParams( $host, $user, $password, $database )
		{
			$this->db->host=$host;
			$this->db->user=$user;
			$this->db->password=$password;
			$this->db->database=$database;
		}
	}
