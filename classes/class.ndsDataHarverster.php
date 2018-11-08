<?php

	/*

	requires: class.nds-interface.php
	example:
	$n=new ndsDataHarvester;
	$n->setServer( '145.136.242.167:9200' );
	$n->setServicePaths( [ 'taxon' => '/taxon/', 'specimen' => '/specimen/', 'multimedia'=>'/multimedia/', 'geo'=>'/geo/', 'storageunits'=>'/storageunits/' ] );
	$n->setQueryParameterField( 'query' );
	$n->setNdsInterface( new ndsInterface );
	$n->initialize();
	$n->runQueries();
	$data = $n->getData();

	*/

	class ndsDataHarvester {
		
		private $cfg;
		private $services;
		private $queries;
		private $nds;
		private $data;

		public function __construct()
		{
			$this->cfg=new StdClass;
			$this->queries=new StdClass;
			$this->data=new StdClass;
		}

		public function initialize()
		{
			if ( is_null($this->cfg->server)) die( "no server set" );
			if ( is_null($this->nds)) die( "no nds interface set" );
			
			$this->nds->setEsSever( $this->cfg->server );
			$this->nds->resetQueryQueue();

			$this->initConfig();
			$this->initServices();
			$this->initQueries();
			$this->queueQueries();

		}

		public function setServer( $server )
		{			
			$this->cfg->server=trim(str_ireplace('http://','',$server)," /");
		}

		public function setServicePaths( $paths )
		{
			$this->services=new StdClass;

			if (isset($paths['taxon'])) $this->services->taxon=$paths['taxon'];
			if (isset($paths['specimen'])) $this->services->specimen=$paths['specimen'];
			if (isset($paths['multimedia'])) $this->services->multimedia=$paths['multimedia'];
			if (isset($paths['geo'])) $this->services->geo=$paths['geo'];
			if (isset($paths['storageunits'])) $this->services->storageunits=$paths['storageunits'];
		}

		public function setQueryParameterField( $fields )
		{			
			$this->fields=new StdClass;

			if ( is_string($fields) )
			{
				$this->fields->taxon=$this->fields->specimen=$this->fields->multimedia=$this->fields->geo=$this->fields->storageunits=$fields;
			}
			else
			if ( is_array($fields) )
			{
				if (isset($fields['taxon'])) $this->fields->taxon=$fields['taxon'];
				if (isset($fields['specimen'])) $this->fields->specimen=$fields['specimen'];
				if (isset($fields['multimedia'])) $this->fields->multimedia=$fields['multimedia'];
				if (isset($fields['geo'])) $this->fields->geo=$fields['geo'];
				if (isset($fields['storageunits'])) $this->fields->storageunits=$fields['storageunits'];
			}
		}
		
		public function setNdsInterface( $nds )
		{			
			$this->nds=$nds;
		}
		
		public function runQueries()
		{			
			$this->nds->run();
			$this->setData();
		}
			
		public function getData()
		{			
			return $this->data;
		}

		private function initConfig()
		{
			$this->cfg->dutchlands=
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
				  "Netherlands/Germany"
				  ];

			$this->cfg->dutchlands= array_map("utf8_encode", $this->cfg->dutchlands);

			//$this->cfg->subspeciesetceteras=[ "species", "subspecies", "var.", "subsp", "forma", "cv.", "f.", "subvar."];
			$this->cfg->subspeciesetceteras=[ "species", "subspecies", "subsp", "ssp." ];

		}

		private function initServices()
		{
			if ( !isset($this->services->taxon) ) die( "no taxon service set" );
			if ( !isset($this->services->specimen) ) die( "no specimen service set" );
			if ( !isset($this->services->multimedia) ) die( "no multimedia service set" );
			//if ( !isset($this->services->geo) ) die( "no geo service set" );
			//if ( !isset($this->services->storageunits) ) die( "no storageunits service set" );
		}

		private function initQueries()
		{
			$this->queries->specimen_totalCount=$this->makeQueryObject($this->services->specimen, '{}', 'total');
			$this->queries->taxon_totalCount=$this->makeQueryObject($this->services->taxon, '{}', 'total');
			$this->queries->multimedia_totalCount=$this->makeQueryObject($this->services->multimedia, '{}', 'total');

			$this->queries->taxon_groupByRank=$this->makeQueryObject($this->services->taxon, '{ "size": 0, "aggs": { "taxon_groupByRank": { "terms": { "field": "taxonRank" } } } }');
			$this->queries->taxon_vernacularNamesCardinality=$this->makeQueryObject($this->services->taxon, '{ "size": 0, "query": { "nested": { "path": "vernacularNames", "query": { "exists" : { "field" : "vernacularNames.name" } } } }, "ext" : { }, "aggs" : { "vernacularName": { "nested": { "path": "vernacularNames" }, "aggs": { "vernacularName": { "cardinality" : { "field" : "vernacularNames.name" }}}}}}');
			$this->queries->taxon_acceptedNamesCardinality=$this->makeQueryObject($this->services->taxon, '{ "size" : 0, "query": { "exists" : { "field" : "acceptedName.fullScientificName" } }, "ext" : { }, "aggs": { "acceptedName": { "cardinality" : { "field" : "acceptedName.fullScientificName" } } }}');
			$this->queries->taxon_synonymCardinality=$this->makeQueryObject($this->services->taxon, '{ "size" : 0, "query": { "nested": { "path": "synonyms", "query": { "exists" : { "field" : "synonyms.fullScientificName" } } } }, "ext" : { }, "aggs" : { "synonym": { "nested": { "path": "synonyms" }, "aggs": { "synonym": { "cardinality" : { "field" : "synonyms.fullScientificName" } } } } }}');

			$this->queries->specimen_perCollectionType=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "ext" : {}, "aggs" : { "collectionType" : { "terms" : { "field" : "collectionType", "size": 20 } } } }');
			$this->queries->specimen_typeStatusPerCollectionType=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query" : { "nested": { "path": "identifications", "query": { "exists" : { "field" : "identifications.typeStatus" } } } }, "ext" : {}, "aggs" : { "collectionType" : {"terms" : { "field" : "collectionType", "size": 100 },"aggs" : {"identifications" : {"nested": {"path": "identifications"},"aggs" : {"typeStatus" : {"terms" : { "field" : "identifications.typeStatus", "size": 100 }}}}}}}}');
			$this->queries->specimen_recordBasisPerCollectionType=$this->makeQueryObject($this->services->specimen, '{"size" : 0,"query": {"exists" : { "field" : "recordBasis" }},"ext" : {},"aggs" : {"collectionType" : {"terms" : { "field" : "collectionType", "size": 100 },"aggs" : {"recordBasis" : {"terms" : { "field" : "recordBasis", "size": 100 }}}}}}');
			$this->queries->specimen_acceptedNamesCardinality=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "nested": { "path": "identifications", "query": { "exists" : { "field" : "identifications.scientificName.fullScientificName" } } } }, "ext" : { }, "aggs" : { "fullScientificName": { "nested": { "path": "identifications" }, "aggs": { "fullScientificName": { "cardinality" : { "field" : "identifications.scientificName.fullScientificName" }}}}}}');

			$this->queries->specimen_collectionTypeCountPerGatherer=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "nested": { "path": "gatheringEvent.gatheringPersons", "query": { "exists" : { "field" : "gatheringEvent.gatheringPersons.fullName" } } } }, "ext" : { }, "aggs" : { "gatheringPersons": { "nested": { "path": "gatheringEvent.gatheringPersons" }, "aggs": { "gatheringPersons": { "terms" : { "field" : "gatheringEvent.gatheringPersons.fullName","exclude": ["Unknown","Unreadable"], "order": { "_count": "desc" }, "size": 2000 }, "aggs": { "collectionType": { "reverse_nested": {}, "aggs" : { "collectionType_count" : { "cardinality" : { "field" : "collectionType" } } } } } } } } }}');
			$this->queries->specimen_collectionTypePerGatherer=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "nested": { "path": "gatheringEvent.gatheringPersons", "query": { "term": { "gatheringEvent.gatheringPersons.fullName": { "value": "%COLLECTOR%" } } } }}, "ext" : { }, "aggs" : { "collectionType_count" : { "terms" : { "field" : "collectionType", "size" : 100 } } } }');
			$this->queries->specimen_collectionTypePerGatherer->secondary=true;

			$this->queries->specimen_perScientificName=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "nested": { "path": "identifications", "query": { "bool": { "must": [ { "exists" : { "field" : "identifications.scientificName.fullScientificName" } }, { "terms": { "identifications.taxonRank": [ %SUBSPECIES_ETC_RANKS% ] } } ] } } } }, "aggs": { "fullScientificName": { "nested": { "path": "identifications" }, "aggs": { "fullScientificName": { "terms": {"field" : "identifications.scientificName.fullScientificName", "exclude": ["?"], "size" : 25} } } } }}');
//			$this->queries->specimen_perScientificName=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "nested": { "path": "identifications", "query": { "bool": { "must": [ { "exists" : { "field" : "identifications.scientificName.scientificNameGroup" } }, { "terms": { "identifications.taxonRank": [ %SUBSPECIES_ETC_RANKS% ] } } ] } } } }, "aggs": { "fullScientificName": { "nested": { "path": "identifications" }, "aggs": { "fullScientificName": { "terms": {"field" : "identifications.scientificName.scientificNameGroup", "exclude": ["?"], "size" : 25} } } } }}');
			$this->queries->specimen_countPerCountryWorld=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "bool": { "must_not": [ { "terms": { "gatheringEvent.country": [ "Unknown" ] } } ] } }, "aggs": { "country": { "terms": {"field" : "gatheringEvent.country", "size" : 100 } } }}');
			$this->queries->specimen_countPerProvince_NL=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "bool": { "must": [ { "terms": { "gatheringEvent.country": [ %DUTCHLANDS% "Unknown" ] } } ] } }, "aggs": { "country": { "terms": {"field" : "gatheringEvent.provinceState"} } }}');

			$this->queries->specimen_prepTypePerCollection=$this->makeQueryObject($this->services->specimen, '{ "size": 0, "query": {}, "aggs" : { "collections" : { "terms" : { "field" : "collectionType", "size" : 100 }, "aggs": { "prepTypes": { "terms" : { "field" : "preparationType","size": 100 } } } } } }');
			$this->queries->specimen_noPrepTypePerCollection=$this->makeQueryObject($this->services->specimen, '{ "size": 0,  "query": { "bool": { "must_not": [ { "exists": { "field": "preparationType" } } ] } }, "aggs" : { "collections" : { "terms" : { "field" : "collectionType", "size" : 100 } } } }');
			$this->queries->specimen_kindOfUnitPerCollection=$this->makeQueryObject($this->services->specimen, '{ "size": 0, "query": {}, "aggs" : { "collections" : { "terms" : { "field" : "collectionType", "size" : 100 }, "aggs": { "kindsOfUnit": { "terms" : { "field" : "kindOfUnit","size": 100 } } } } } }');
			$this->queries->specimen_noKindOfUnitPerCollection=$this->makeQueryObject($this->services->specimen, '{ "size": 0,  "query": { "bool": { "must_not": [ { "exists": { "field": "kindOfUnit" } } ] } }, "aggs" : { "collections" : { "terms" : { "field" : "collectionType", "size" : 100 } } } }');
			$this->queries->specimen_perCollection=$this->makeQueryObject($this->services->specimen, '{ "size": 0, "query": {}, "aggs" : { "collections" : { "terms" : { "field" : "collectionType", "size" : 100 } } } }');

			$this->queries->storage_sumPerColl_withIndivCount=$this->makeQueryObject($this->services->storageunits, '{ "query": { "exists" : { "field" : "individualCount" } }, "aggs" : { "collections" : { "terms" : { "field" : "INST_COLL_SUBCOLL.keyword","size": 100 }, "aggs" : { "indiv_count" : { "sum" : { "field" : "individualCount" } } } } } }');
			$this->queries->storage_docCountPerColl_withoutIndivCount=$this->makeQueryObject($this->services->storageunits, '{ "query": { "bool": { "must_not": [ { "exists": { "field": "individualCount" } } ] } }, "aggs" : { "collections" : { "terms" : { "field" : "INST_COLL_SUBCOLL.keyword","size": 100 }, "aggs": { "sum_count": { "terms" : { "field" : "Mount.keyword","size": 100 } } } } } }');
			$this->queries->storage_catNumberCardinality=$this->makeQueryObject($this->services->storageunits, '{ "size": 0, "aggs" : { "catalogNumber_count" : { "cardinality" : { "field" : "catalogNumber.keyword" } } } }');
		
			$this->queries->storage_netherlandsCollectionMount=$this->makeQueryObject($this->services->storageunits, '{ "size": 0, "query": { "terms": { "country.keyword": [ %DUTCHLANDS% "Unknown" ] } }, "aggs": { "provinces": { "terms": { "field": "stateProvince.keyword", "size": 100 }, "aggs": { "collections": { "terms": { "field": "INST_COLL_SUBCOLL.keyword", "size": 100 }, "aggs": { "mounts": { "terms": { "field" : "Mount.keyword", "size": 100 }}}}}}}}');
			$this->queries->specimen_netherlandsCollectionKindOfUnit=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "bool": { "must": [ { "terms": { "gatheringEvent.country": [ %DUTCHLANDS% "Unknown" ] } } ] } }, "aggs": { "provinces": { "terms": {"field" : "gatheringEvent.provinceState", "size": 100 }, "aggs": { "collections": { "terms": { "field": "collectionType", "size": 100 }, "aggs": { "kindsOfUnit": { "terms": { "field": "kindOfUnit", "size": 100 } } } }}}}}');
			$this->queries->specimen_netherlandsCollectionPreparationType=$this->makeQueryObject($this->services->specimen, '{ "size" : 0, "query": { "bool": { "must": [ { "terms": { "gatheringEvent.country": [ %DUTCHLANDS% "Unknown" ] } } ] } }, "aggs": { "provinces": { "terms": {"field" : "gatheringEvent.provinceState", "size": 100 }, "aggs": { "collections": { "terms": { "field": "collectionType", "size": 100 }, "aggs": { "preparationTypes": { "terms": { "field": "preparationType", "size": 100 } } }}}}}}');
		
			
			foreach($this->queries as $key=>$obj)
			{
				$d = $obj->query;
				if (strpos($obj->query,'%DUTCHLANDS%'))
				{
					$d = str_replace('%DUTCHLANDS%', '"' . implode('","',array_map(function($a) { return addslashes($a);} ,$this->cfg->dutchlands)). '", ',$d);
				}
				if (strpos($obj->query,'%SUBSPECIES_ETC_RANKS%'))
				{
					$d = str_replace('%SUBSPECIES_ETC_RANKS%', '"' . implode('","',array_map(function($a) { return addslashes($a);} ,$this->cfg->subspeciesetceteras)) . '"', $d);
				}
				$this->queries->{$key}->query = $d;
			}
		}

		private function makeQueryObject( $service, $query, $type='aggregation' )
		{
			$d=new StdClass();
			$d->service=$service;
			$d->field=$this->fields->{trim($service,"/ ")};
			$d->query=$query;
			$d->type=$type;
			return $d;
		}

		private function queueQueries()
		{
			foreach($this->queries as $key=>$obj)
			{
				if ( isset($obj->secondary) && $obj->secondary == true ) continue;
				$this->queueQuery( $key, $obj );
			}
			
			
		}

		private function queueQuery( $handle, $obj )
		{
			$this->nds->setEsPath( $obj->service );
			$this->nds->setEsQueryField( $obj->field );
			$this->nds->setEsQuery( $obj->query );
			$this->nds->setQueryHandle( $handle );
			//$this->nds->setMethod( "GET" );
			$this->nds->registerQuery();
		}
	
		private function setData()
		{
			foreach($this->queries as $key=>$val)
			{
				if ( $this->nds->isHandleRegistered($key) )
				{
					if ( $val->type=='total' )
						$this->data->{$key} = $this->nds->resultGetTotal( $key );
					else
					if ( $val->type=='aggregation' )
						$this->data->{$key} = $this->nds->resultGetAggregations( $key );
				}
			}

			// this should be invoked based on some $obj->secondary value rather than hard-coded
			$this->data->specimen_collectionTypeCountPerGatherer = $this->specimen_collectionTypeCountPerGatherer();
		}
		
		private function specimen_collectionTypeCountPerGatherer()
		{
			$d=$this->nds->resultGetAggregations( "specimen_collectionTypeCountPerGatherer" );

			if (empty($d)) return;
			
			$b=[];

			foreach((array)$d['gatheringPersons']['gatheringPersons']['buckets'] as $val)
			{
				$b[]=['collector'=>$val['key'],'doc_count'=>$val['doc_count'],'collection_count'=>$val['collectionType']['collectionType_count']['value'],'collections'=>[]];
			}
			
			/*
			usort($b,function($a,$b)
			{ 
				if ($a['collection_count']==$b['collection_count']) { return $a['collector']<$b['collector']; } else { return $a['collection_count']<$b['collection_count']; }
			});
			*/
			
			$b=array_slice($b,0,100);
	
			$c= clone $this->nds;
			$c->setEsSever( $this->cfg->server );
			$c->resetQueryQueue();
				
			foreach((array)$b as $key=>$val)
			{
				$c->setEsPath( $this->services->specimen );
				$c->setEsQuery( str_replace('%COLLECTOR%',$val['collector'],$this->queries->specimen_collectionTypePerGatherer->query) );
				$this->nds->setEsQueryField( $this->queries->specimen_collectionTypePerGatherer->field );
				$c->setQueryHandle( "specimen_gathererCollectionTypeCount_" . $key );
				$c->registerQuery();			
			}
			
			$c->run();
			
			foreach((array)$b as $key=>$val)
			{
				$s = $c->resultGetAggregations( "specimen_gathererCollectionTypeCount_" . $key );
				$total=0;
				foreach((array)$s['collectionType_count']['buckets'] as $val2)
				{
					$b[$key]['collections'][]=['collection'=>$val2['key'],'doc_count'=>$val2['doc_count']];
				}
			}

			return $b;
		}

	}
