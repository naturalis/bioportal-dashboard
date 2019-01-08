<?php

	class nbaQueries {

		private $n;
		private $dutchLands=[];
		private $dutchLandsImploded;

		public function __construct( $nbaInterface )
		{
			$this->n = $nbaInterface;
		}

		public function setSourceSystems( $type, $sourceSystems )
		{
			$this->sourceSystems[$type] = $sourceSystems;
			$this->sourceSystemsImploded[$type] = '"' . implode('","',array_map("addslashes", $this->sourceSystems[$type])) . '"';
		}

		public function setDutchlands( $dutchLands )
		{
			$this->dutchLands = $dutchLands;
			$this->dutchLandsImploded = '"' . implode('","',array_map("addslashes", $this->dutchLands)) . '"';
		}

		public function setLowerRanks( $lowerRanks )
		{
			$this->lowerRanks = $lowerRanks;
			$this->lowerRanksImploded = '"' . implode('","',array_map("addslashes", $this->lowerRanks)) . '"';			
		}

		public function setIgnorableFullScientificNames( $names )
		{
			$this->ignorableFullScientificNames = $names;
			$this->ignorableFullScientificNamesImploded = '"' . implode('","',array_map("addslashes", $this->ignorableFullScientificNames)) . '"';			
		}

		public function setIgnorableCollectors( $names )
		{
			$this->ignorableCollectors = $names;
			$this->ignorableCollectorsImploded = '"' . implode('","',array_map("addslashes", $this->ignorableCollectors)) . '"';			
		}

		public function nbaGetMainNumbers()
		{
			$this->n->resetQueryQueue();

			$this->n->registerQuery( [
				'label' => 'specimenCountBySourceSystem',
				'path' => '/specimen/getDistinctValues/sourceSystem.code',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] } ], "size" : 1000 }',$this->sourceSystemsImploded["specimen"])
			] );

			$this->n->registerQuery( [
				'label' => 'taxonCountBySourceSystem',
				'path' => '/taxon/getDistinctValues/sourceSystem.code',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] } ], "size" : 1000 }',$this->sourceSystemsImploded["taxon"])
			] );

			$this->n->registerQuery( [
				'label' => 'multimediaCountBySourceSystem',
				'path' => '/multimedia/getDistinctValues/sourceSystem.code',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] } ], "size" : 1000 }',$this->sourceSystemsImploded["multimedia"])
			] );

			$this->n->processQueries();
			$results = $this->n->getQueryResults();

			$specimen_totalCount=0;
			$taxon_totalCount=0;
			$multimedia_totalCount=0;

			if (isset($results["specimenCountBySourceSystem"]["result"]))
			{
				foreach($results["specimenCountBySourceSystem"]["result"] as $key=>$val)
				{
					$specimen_totalCount += $val;
				}
			}		

			if (isset($results["taxonCountBySourceSystem"]["result"]))
			{
				foreach($results["taxonCountBySourceSystem"]["result"] as $key=>$val)
				{
					$taxon_totalCount += $val;
				}
			}

			if (isset($results["multimediaCountBySourceSystem"]["result"]))
			{
				foreach($results["multimediaCountBySourceSystem"]["result"] as $key=>$val)
				{
					$multimedia_totalCount += $val;
				}
			}

			return [
				'specimen_totalCount' => $specimen_totalCount,
				'taxon_totalCount' => $taxon_totalCount,
				'multimedia_totalCount' => $multimedia_totalCount
			];
		}

		public function nbaGetCollectionOverview()
		{

			$this->n->resetQueryQueue();

			$this->n->registerQuery( [
				'label' => 'specimen_prepTypePerCollection',
				'path' => '/specimen/getDistinctValuesPerGroup/collectionType/preparationType',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] } ], "size" : 1000 }',
					$this->sourceSystemsImploded["specimen"])
			] );

			$this->n->registerQuery( [
				'label' => 'specimen_noPrepTypePerCollection',
				'path' => '/specimen/getDistinctValues/collectionType',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "preparationType", "operator" : "equals" } ], "size" : 1000 }',
					$this->sourceSystemsImploded["specimen"])
			] );

			$this->n->registerQuery( [
				'label' => 'specimen_kindOfUnitPerCollection',
				'path' => '/specimen/getDistinctValuesPerGroup/collectionType/kindOfUnit',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] } ], "size" : 1000 }',
					$this->sourceSystemsImploded["specimen"])
			] );

			$this->n->processQueries();

			$results = $this->n->getQueryResults();

			$specimen_prepTypePerCollection = array();
			$specimen_noPrepTypePerCollection = array();
			$specimen_kindOfUnitPerCollection = array();

			if (isset($results["specimen_prepTypePerCollection"]["result"]))
			{
				foreach($results["specimen_prepTypePerCollection"]["result"] as $key=>$val)
				{
					$specimen_prepTypePerCollection[$val['collectionType']]=[];
					foreach($val["values"] as $sVal)
					{
						$specimen_prepTypePerCollection[$val['collectionType']][$sVal["preparationType"]]=$sVal["count"];
					}
				}
			}

			if (isset($results["specimen_noPrepTypePerCollection"]["result"]))
			{
				foreach($results["specimen_noPrepTypePerCollection"]["result"] as $key=>$val)
				{
					$specimen_noPrepTypePerCollection[$key]=$val;
				}
			}

			if (isset($results["specimen_kindOfUnitPerCollection"]["result"]))
			{
				foreach($results["specimen_kindOfUnitPerCollection"]["result"] as $key=>$val)
				{
					$specimen_kindOfUnitPerCollection[$val['collectionType']]=[];
					foreach($val["values"] as $sVal)
					{
						$specimen_kindOfUnitPerCollection[$val['collectionType']][$sVal["kindOfUnit"]]=$sVal["count"];
					}
				}
			}

			return [
				'specimen_prepTypePerCollection' => $specimen_prepTypePerCollection,
				'specimen_noPrepTypePerCollection' => $specimen_noPrepTypePerCollection,
				'specimen_kindOfUnitPerCollection' => $specimen_kindOfUnitPerCollection
			];
		}

		public function nbaGetSpecimenOverview()
		{
			$this->n->resetQueryQueue();

			$this->n->registerQuery( [
				'label' => 'specimen_acceptedNamesCardinality',
				'path' => '/specimen/countDistinctValues/identifications.scientificName.fullScientificName',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "identifications.scientificName.fullScientificName", "operator" : "!=" } ] }',$this->sourceSystemsImploded["specimen"])
			] );

			$this->n->registerQuery( [
				'label' => 'specimen_typeStatusPerCollectionType',
				'path' => '/specimen/getDistinctValuesPerGroup/collectionType/identifications.typeStatus',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "collectionType", "operator" : "!=" }, { "field" : "identifications.typeStatus", "operator" : "!=" } ], "size" : 100 }',$this->sourceSystemsImploded["specimen"])
			] );


			$this->n->registerQuery( [
				'label' => 'specimen_perScientificName',
				'path' => '/specimen/getDistinctValues/identifications.scientificName.fullScientificName',
				'query' => sprintf('{ "conditions" : [
					{ "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, 
					{ "field" : "identifications.taxonRank", "operator" : "in", "value" : [ %s ] },
					{ "field" : "identifications.scientificName.fullScientificName", "operator" : "not_in", "value" : [ %s ] }
				], "size" : 40 }',$this->sourceSystemsImploded["specimen"],$this->lowerRanksImploded,$this->ignorableFullScientificNamesImploded)
			] );

			$this->n->processQueries();

			$results = $this->n->getQueryResults();

			$specimen_acceptedNamesCardinality = 0;
			$specimen_typeStatusPerCollectionType = [];
			$specimen_perScientificName = [];

			if (isset($results["specimen_acceptedNamesCardinality"]["result"]))
			{
				$specimen_acceptedNamesCardinality=$results["specimen_acceptedNamesCardinality"]["result"];
			}

			if (isset($results["specimen_typeStatusPerCollectionType"]["result"]))
			{
				$specimen_typeStatusPerCollectionType=$results["specimen_typeStatusPerCollectionType"]["result"];
			}

			if (isset($results["specimen_perScientificName"]["result"]))
			{
				$specimen_perScientificName=$results["specimen_perScientificName"]["result"];
			}

			return [
				'specimen_acceptedNamesCardinality' => $specimen_acceptedNamesCardinality,
				'specimen_typeStatusPerCollectionType' => $specimen_typeStatusPerCollectionType,
				'specimen_perScientificName' => $specimen_perScientificName
			];
		}

		public function nbaGetTaxonOverview()
		{
			$this->n->resetQueryQueue();

			$this->n->registerQuery( [
				'label' => 'taxon_groupByRank',
				'path' => '/taxon/getDistinctValues/taxonRank',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] } ], "size" : 100 }',
					$this->sourceSystemsImploded["taxon"])
			] );

			$this->n->registerQuery( [
				'label' => 'taxon_acceptedNamesCardinality',
				'path' => '/taxon/countDistinctValues/acceptedName.fullScientificName',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "acceptedName.fullScientificName", "operator" : "!=" } ] }',
					$this->sourceSystemsImploded["taxon"])
			] );

			$this->n->registerQuery( [
				'label' => 'taxon_synonymCardinality',
				'path' => '/taxon/countDistinctValues/synonyms.fullScientificName',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "synonyms.fullScientificName", "operator" : "!=" } ] }',
					$this->sourceSystemsImploded["taxon"])
			] );

			$this->n->registerQuery( [
				'label' => 'taxon_vernacularNamesCardinality',
				'path' => '/taxon/countDistinctValues/vernacularNames.name',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "vernacularNames.name", "operator" : "!=" } ] }',
					$this->sourceSystemsImploded["taxon"])
			] );

			$this->n->processQueries();

			$results = $this->n->getQueryResults();

			$taxon_groupByRank = array();
			$taxon_acceptedNamesCardinality = 0;
			$taxon_synonymCardinality = 0;
			$taxon_vernacularNamesCardinality = 0;

			if (isset($results["taxon_groupByRank"]["result"]))
			{
				$taxon_groupByRank=$results["taxon_groupByRank"]["result"];
			}

			if (isset($results["taxon_acceptedNamesCardinality"]["result"]))
			{
				$taxon_acceptedNamesCardinality=$results["taxon_acceptedNamesCardinality"]["result"];
			}

			if (isset($results["taxon_synonymCardinality"]["result"]))
			{
				$taxon_synonymCardinality=$results["taxon_synonymCardinality"]["result"];
			}

			if (isset($results["taxon_vernacularNamesCardinality"]["result"]))
			{
				$taxon_vernacularNamesCardinality=$results["taxon_vernacularNamesCardinality"]["result"];
			}

			return [
				'taxon_groupByRank' => $taxon_groupByRank,
				'taxon_acceptedNamesCardinality' => $taxon_acceptedNamesCardinality,
				'taxon_synonymCardinality' => $taxon_synonymCardinality,
				'taxon_vernacularNamesCardinality' => $taxon_vernacularNamesCardinality
			];
		}

		public function nbaGetNetherlandsCollectionKindOfUnit() 
		{
			return $this->nbaGetNetherlandsCollectionByThirdBucket("kindOfUnit");
		}

		public function nbaGetNetherlandsCollectionPreparationType() 
		{
			return $this->nbaGetNetherlandsCollectionByThirdBucket("preparationType");
		}

		private function nbaGetNetherlandsCollectionByThirdBucket( $thirdBucket ) 
		{
			$this->n->resetQueryQueue();

			$this->n->registerQuery( [
				'label' => 'specimen_netherlandsCollectionPerProvince',
				'path' => '/specimen/getDistinctValuesPerGroup/gatheringEvent.provinceState/collectionType',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "gatheringEvent.country", "operator" : "in", "value" : [ %s ] } ], "size" : 100 }',$this->sourceSystemsImploded["specimen"],$this->dutchLandsImploded)
			] );

			$this->n->processQueries();
			$results = $this->n->getQueryResults();

			$specimen_netherlandsCollectionThirdBucket = $results["specimen_netherlandsCollectionPerProvince"]["result"];

			$this->n->resetQueryQueue();

			foreach ($specimen_netherlandsCollectionThirdBucket as $key => $value)
			{
				$this->n->registerQuery( [
					'label' => 'specimen_netherlandsCollectionPerProvince_' . $value["gatheringEvent.provinceState"],
					'path' => '/specimen/getDistinctValuesPerGroup/collectionType/'.$thirdBucket,
					'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "gatheringEvent.country", "operator" : "in", "value" : [ %s ] }, { "field" : "gatheringEvent.provinceState", "operator" : "=", "value" : "%s" } ], "size" : 100 }',$this->sourceSystemsImploded["specimen"],$this->dutchLandsImploded,$value["gatheringEvent.provinceState"])
				] );
			}

			$this->n->processQueries();
			$results = $this->n->getQueryResults();

			foreach($specimen_netherlandsCollectionThirdBucket as $key=>$val)
			{
				foreach($val["values"] as $skey=>$sval)
				{
					if (isset($results["specimen_netherlandsCollectionPerProvince_" . $val["gatheringEvent.provinceState"]]))
					{
						foreach ($results["specimen_netherlandsCollectionPerProvince_" . $val["gatheringEvent.provinceState"]]["result"] as $ckey=>$cval)
						{
							if ($cval["collectionType"]==$sval["collectionType"])
							{
								$specimen_netherlandsCollectionThirdBucket[$key]["values"][$skey][$thirdBucket]=$cval["values"];
							}
						}
					}
				}
			}

			return $specimen_netherlandsCollectionThirdBucket;
		}

		public function nbaGetSpecimenCountPerCountryWorld()
		{

			$this->n->resetQueryQueue();

			$this->n->registerQuery( [
				'label' => 'specimen_countPerCountryWorld',
				'path' => '/specimen/getDistinctValues/gatheringEvent.country',
				'query' => sprintf('{ "conditions" : [ { "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] }, { "field" : "gatheringEvent.country", "operator" : "not_equals_ic", "value" : "Unknown" } ], "size" : 500 }',$this->sourceSystemsImploded["specimen"])
			] );


			$this->n->processQueries();

			$results = $this->n->getQueryResults();

			return $results['specimen_countPerCountryWorld']['result'];
		}

		public function nbaGetCollectors()
		{
			$this->n->resetQueryQueue();

			$this->n->registerQuery( [
				'label' => 'specimen_collectionTypeCountPerGatherer',
				'path' => '/specimen/getDistinctValuesPerGroup/gatheringEvent.gatheringPersons.fullName/collectionType',
				'query' => 
					sprintf('{ "conditions" : [
						{ "field" : "sourceSystem.code", "operator" : "in", "value" : [ %s ] },
						{ "field" : "gatheringEvent.gatheringPersons.fullName", "operator" : "not_in", "value" : [ %s ] }
					], "size" : 20 }', $this->sourceSystemsImploded["specimen"], $this->ignorableCollectorsImploded)
			] );

			$this->n->processQueries();

			return $this->n->getQueryResults()["specimen_collectionTypeCountPerGatherer"]["result"];
		}
	}
