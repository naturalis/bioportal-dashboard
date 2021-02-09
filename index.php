<?php

	// force refresh: /dashboard/?forceDataRefresh

	/*
		to do:
		separate sections for siebld & dubois
		specimen tree (based on CoL taxonomy, greyed out when no specimen, tooltip for speciment lists)
		complete taxonrank list for "what constitutes a (sub)species?"
		post-processing:harmonize countries
			logical collection-groupings
			harmonize collectors' names (but how?)
		extra charts:
			subdivision for botany?
			subdivision for collections as double-banded donut
			specimen over time, stacked per colletion
			something useful that can be expressed in a bar chart
			something useful that can be expressed in a radar plot (because they are cool)
	*/

	include_once("classes/class.nbaInterface.php");
	include_once("classes/class.storageUnits.php");
	include_once("classes/class.nbaQueries.php");
	include_once("classes/class.dataCache.php");
	include_once("classes/class.webPageStealer.php");
	include_once("classes/class.contentBlocks.php");
	include_once("classes/class.collectionUnitCalculation.php");
	include_once("classes/class.translator.php");

	include_once("config/class.config.php");

	$bpRootUrl=config::bioportalRootUrl();
	$dbAccess=config::databasAccessParameters();
	$urls=config::searchUrls();

	// ?forceDataRefresh forces data refresh (and re-caching)
	$forceDataRefresh = (isset($_REQUEST["forceDataRefresh"]));
	$language = (isset($_REQUEST["language"]) ? $_REQUEST["language"] : 'nl' );

	// needs actual database check
	function needFreshData( $force )
	{
		if ($force) return true;
		return false;
	}

	function formatNumber( $number, $decimals=0 )
	{
		return number_format( $number, $decimals, ",", ".");
	}

	$translator=new Translator;
	$translator->setLanguage( $language );
	
	$cache=new dataCache;
	$cache->setDbParams( $dbAccess );
	$cache->setProject( "nba_data" );

	if (!needFreshData( $forceDataRefresh ))
	{
		$data = $cache->getData();
	}
		
	if (needFreshData( $forceDataRefresh ) || !isset($data->specimen_totalCount))
	{

		set_time_limit( 3600 );
	
		$data = new stdClass;

		$n = new nbaInterface;
		$n->setNbaAddress( config::nbaAddress() );

		$q = new nbaQueries( $n );

		$sU = new storageUnits( config::storageUnitDbPath() );
		$sU->setDbParams( $dbAccess );
		$sU->connectDb();

		$q->setLowerRanks( config::lowerRanks() );
		$q->setIgnorableFullScientificNames( config::ignorableFullScientificNames() );
		$q->setIgnorableCollectors( config::ignorableCollectors() );
		$q->setSourceSystems( "specimen", config::sourceSystemsSpecimen() );
		$q->setSourceSystems( "taxon", config::sourceSystemsTaxon() );
		$q->setSourceSystems( "multimedia", config::sourceSystemsMultimedia() );
		$q->setDutchlands($sU->getDutchlands(false));

		$r = $q->nbaGetSpecimenOverview();
		$data->specimen_acceptedNamesCardinality = $r["specimen_acceptedNamesCardinality"];
		$data->specimen_typeStatusPerCollectionType = $r["specimen_typeStatusPerCollectionType"];
		$data->specimen_perScientificName = $r["specimen_perScientificName"];
		$data->specimen_collectionTypeCountPerGatherer = $q->nbaGetCollectors();

		$r = $q->nbaGetMainNumbers();
		$data->specimen_totalCount = $r["specimen_totalCount"];
		$data->taxon_totalCount = $r["taxon_totalCount"];
		$data->multimedia_totalCount = $r["multimedia_totalCount"];

		$r = $q->nbaGetTaxonOverview();
		$data->taxon_groupByRank = $r["taxon_groupByRank"];
		$data->taxon_acceptedNamesCardinality = $r["taxon_acceptedNamesCardinality"];
		$data->taxon_synonymCardinality = $r["taxon_synonymCardinality"];
		$data->taxon_vernacularNamesCardinality = $r["taxon_vernacularNamesCardinality"];

		$r = $q->nbaGetCollectionOverview();
		$data->specimen_prepTypePerCollection = $r["specimen_prepTypePerCollection"];
		$data->specimen_noPrepTypePerCollection = $r["specimen_noPrepTypePerCollection"];
		$data->specimen_kindOfUnitPerCollection = $r["specimen_kindOfUnitPerCollection"];

		
		$data->storage_sumPerColl_withIndivCount = $sU->doQuery($sU->getQuery("sumPerColl_withIndivCount"));
		$data->storage_docCountPerColl_withoutIndivCount = $sU->doQuery($sU->getQuery("docCountPerColl_withoutIndivCount"));

		foreach ($data->storage_docCountPerColl_withoutIndivCount as $key => $val)
		{
			$data->storage_docCountPerColl_withoutIndivCount[$key]["mounts"] = $sU->doQuery(sprintf($sU->getQuery("docCountPerColl_withoutIndivCount_mounts"),$val["INST_COLL_SUBCOLL"]));
		}

		$data->storage_netherlandsCollectionMount = $sU->doQuery($sU->getQuery("docCountPerNetherlandsProvinces"));

		foreach($data->storage_netherlandsCollectionMount as $key=>$val)
		{
			$data->storage_netherlandsCollectionMount[$key]["collections"] = $sU->doQuery(sprintf($sU->getQuery("docCountCollectionsPerProvince"),$val["stateProvince"]));

			foreach($data->storage_netherlandsCollectionMount[$key]["collections"] as $sKey => $sVal)
			{
				$data->storage_netherlandsCollectionMount[$key]["collections"][$sKey]["mounts"] = 
					$sU->doQuery(sprintf($sU->getQuery("docCountMountsPerCollectionPerProvince"),$val["stateProvince"],$sVal["INST_COLL_SUBCOLL"]));
			}
		}

		$data->storage_catNumberCardinality = $sU->doQuery($sU->getQuery("catNumberCardinality"))[0]["doc_count"];


		// calculating provinces
		$data->specimen_netherlandsCollectionKindOfUnit = $q->nbaGetNetherlandsCollectionKindOfUnit();
		$data->specimen_netherlandsCollectionPreparationType = $q->nbaGetNetherlandsCollectionPreparationType();
		$data->specimen_countPerCountryWorld = $q->nbaGetSpecimenCountPerCountryWorld();

		$cache->emptyCache();
		$cache->storeData( $data );
	}

	$calculator = new collectionUnitCalculation;
	$calculator->setStorageSumPerCollWithIndivCount( $data->storage_sumPerColl_withIndivCount );
	$calculator->setStorageSumPerCollWithoutIndivCount( $data->storage_docCountPerColl_withoutIndivCount );
	$calculator->setSpecimenPrepTypePerCollection( $data->specimen_prepTypePerCollection );
	$calculator->setSpecimenNoPrepTypePerCollection( $data->specimen_noPrepTypePerCollection );
	$calculator->setSpecimenKindOfUnitPerCollection( $data->specimen_kindOfUnitPerCollection );
	
	// not in any database
	$staticNumbers = [
		'BrahmsLowerPlants' => [ 'category' => 'Botanie lage planten', 'storageNumber' => 13527, 'average' => 40.30 ],
		'2D' => [ 'category' => '2D materiaal', 'specimenNumber' => (625500 - 2449), 'average' => 1 ], // 2019.03.22: voor actuele minus-waarde: https://api.biodiversitydata.nl/v2/specimen/count/?collectionType=Arts
		// 'PiscesLegacy' => [ 'category' => 'Vertebraten vissen', 'specimenNumber' => 116000, 'average' => 1 ], // 2019.01.10: zijn inmiddels geimporteerd (dixit marian)
		// 'StonesLegacy' => [ 'category' => 'Mineralogie en petrologie', 'specimenNumber' => 100000, 'average' => 1 ], // 2019.01.10: zijn inmiddels geimporteerd (dixit marian)
		'StonesNBADiscarded' => [ 'category' => 'Mineralogie en petrologie', 'specimenNumber' => 199000, 'average' => 1 ], // should be 'thin section'
		// 'paleoLegacy' => [ 'category' => 'paleontologie', 'specimenNumber' => (714000 - 593265), 'average' => 12.64 ] // 2019.01.10: zijn inmiddels geimporteerd (dixit marian)
	];
	
	/*
		please also note this line in the collectionUnitCalculation class:
		if ( $category == 'entomologie' ) continue;
		there is overlap between specimens and storage units for the entomology-collection.
		since the latter are more complete, the former are ignored in the calculations.
	*/

	foreach((array)$staticNumbers as $key=>$val)
	{
		$calculator->addStaticNumbers( [
			'category' =>  $val['category'], 
			'storageNumber' => ( isset($val['storageNumber']) ? $val['storageNumber'] : 0 ),
			'storageCount' => ( isset($val['storageNumber']) ? ($val['storageNumber'] * $val['average']) : 0 ),
			'specimenNumber' => ( isset($val['specimenNumber']) ? $val['specimenNumber'] : 0 ),
			'specimenCount' => ( isset($val['specimenNumber']) ? ($val['specimenNumber'] * $val['average']) : 0 ),
		] );	
	}
	
	$getAddedStaticNumbers = $calculator->getAddedStaticNumbers();

	
	// calculating totals
	$calculator->runCalculations();
	$calculator->roundEstimates();
	$calculator->sortCategoryBuckets();
	$collectionUnitEstimates=$calculator->getCollectionUnitEstimates();
	$grandUnitsTotal=$calculator->getGrandUnitsTotal();
	$categoryBuckets=$calculator->getCategoryBuckets();

	// calculating netherlands
	$calculator->setStorageMountPerCollectionPerDutchProvince( $data->storage_netherlandsCollectionMount );
	$calculator->setSpecimenKindOfUnitPerCollectionPerDutchProvince( $data->specimen_netherlandsCollectionKindOfUnit );
	$calculator->setSpecimenPreparationTypePerCollectionPerDutchProvince( $data->specimen_netherlandsCollectionPreparationType );
	$calculator->calculateDutchProvinceNumbers();
	$calculator->aggregateDutchProvinceNumbers();
	$provinces=$calculator->getDutchProvinceNumbers();

	// calculating world
	$calculator->setSpecimenCountPerCountryWorld( $data->specimen_countPerCountryWorld );
	$calculator->calculateWorldNumbers();
	$calculator->sortWorldNumbers( 'doc_count', 'desc' );
	$world=$calculator->getWorldNumbers();

	ob_start();

?>
<link rel="stylesheet" type="text/css" href="css/style.css">
<script src="js/jquery-3.2.1.min.js" type="text/javascript"></script>
<script src="js/Chart.bundle.min.js"></script>
<script src="js/jqvmap/jquery.vmap.js"></script>
<script src="js/jqvmap/maps/jquery.vmap.world.js"></script>
<script>

var specimen_perCollectionTypeData=[];
var specimen_countPerProvince_NLData=[];
var countryData={};
var colors=[];

</script>
<?php

	define("CLASS_FULL","full");
	define("CLASS_HALF","half");
	define("CLASS_ONE_THIRD","one-third");
	define("CLASS_TWO_THIRD","two-third");
	
	$c = new contentBlocks;

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$t[]=$translator->translate("intro1");
	$t[]=$translator->translate("intro2");
	$t[]=$translator->translate("intro3");
	$t[]=$translator->translate("intro4");

	$buffer=[];
	$buffer[]="<div style='float:left;width:70%;margin:0 20px 10px 5px;'><p>" .implode("</p><p>\n",$t). "</p></div>";

	$buffer[]="
		<div style='float:right;margin:15px 10px 15px 0;'>
			<img style='border:1px solid #eee;width:170px;margin-bottom:10px;' src='img/1b.jpg'><br />
			<img style='border:1px solid #eee;width:170px;' src='img/3.jpg'>
		</div>";

	$c->makeBlock(
		[ "cell" => CLASS_TWO_THIRD, "main" => "simple", "info" => "big-simple-central" ],
		[
			"title" => $translator->translate("Naturalis dashboard"), 
			"main" => implode("\n",$buffer)
		]
	);

	$w = new webPageStealer;
	$w->setUrl( config::nbaAddress() . 'import-files' );
	$w->stealPage();

	$loadInfos = json_decode($w->getPage(),true);

	// foreach ($loadInfos as $key => $value)
	// {
	// 	$str = str_replace(["nsr-","crs-specimens-","col-","brahms-",".tar.gz"], "", $value);
	// 	if ($key!="col_source_file") {
	// 		$date = date_parse($str);
	// 		$loadInfos[$key] = $date["day"] . " " . $translator->translateMonth($date["month"],$language) . " " . $date["year"];
	// 	}
	// 	else {
	// 		$loadInfos[$key] = $str;
	// 	}
	// }

	function reformatDate($date)
	{
		$t=explode("-",$date);
		return implode("-",[$t[2],$t[1],$t[0]]);
	}

	// reformatDate($loadInfos["crs_multimediaobject"]["harvest_date"]);
	// reformatDate($loadInfos["xc_multimediaobject"]["harvest_date"]);
	// reformatDate($loadInfos["obs_specimen"]["harvest_date"]);
	// reformatDate($loadInfos["xc_specimen"]["harvest_date"]);
	// reformatDate($loadInfos["brahms_multimediaobject"]["harvest_date"]);
	// reformatDate($loadInfos["nsr_multimediaobject"]["harvest_date"]);
	// reformatDate($loadInfos["geo_geoarea"]["harvest_date"]);

	$table[] = '<table id="importDates">';

	foreach ([
		"Naturalis Botany catalogues" => reformatDate($loadInfos["brahms_specimen"]["harvest_date"]),
		"Naturalis Zoology and Geology catalogues" => reformatDate($loadInfos["crs_specimen"]["harvest_date"]),
		"Naturalis storage units" => "22-05-2017",
		// "Catalogue of Life" => reformatDate($loadInfos["col_taxon"]["harvest_date"]),
		"Nederlands Soortenregister" => reformatDate($loadInfos["nsr_taxon"]["harvest_date"]),
		"Dutch Caribbean Species Register" => reformatDate($loadInfos["dcsr_taxon"]["harvest_date"]),
	] as $key => $val) {
		$table[] = '<tr><th>' . $key . '</td><td>' . $val . '</td></tr>';
	}

	$table[] = '</table>';
	$table[] = '<!-- import_date: ' . $loadInfos["import_date"] .' -->';

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "simple", "info" => "big-simple-central" ],
		[
			"title" => $translator->translate("Last import dates"),
			"main" => implode("\n",$table)
		]
	);

	echo $c->getBlockRow();

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[
			"title" => $translator->translate("Specimen count"), "main" => formatNumber( $grandUnitsTotal ),
			"subscript" => $translator->translate("specimens"), 
			"info" => sprintf( $translator->translate( "registered in the Netherlands Biodiversity API as %s specimen records and %s storage units."),
				formatNumber($data->specimen_totalCount),
				(formatNumber($data->storage_catNumberCardinality+$getAddedStaticNumbers['storageNumber']) ) )
		]
	);

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[ "title" => $translator->translate("Taxon count"), "main" => formatNumber($data->taxon_totalCount), "subscript" => $translator->translate("taxa"), "info" => $translator->translate("registered in the Netherlands Biodiversity API, sourced from the Catalogue of Life and the Dutch Species Register.") ]
	);

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[ "title" => $translator->translate("Multimedia count"), "main" => formatNumber($data->multimedia_totalCount), "subscript" => $translator->translate("multimedia records"), "info" => $translator->translate("registered in the Netherlands Biodiversity API, consisting of  specimen images from the collection and taxon photo's from Dutch Species Register.") ]
	);

	echo $c->getBlockRow();

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$buffer=[];
	$buffer[]='<div style="display:inline-block;"><table class="data-table" style="width:325px;float:left;margin-right:25px;">';
	
	$i=0;
	foreach((array)$categoryBuckets as $key=>$bucket)
	{
		if ($i++==9)
		{
			$buffer[] = '</table><table class="data-table" style="width:325px;float:left;margin-right:25px;">';
		}
		$buffer[] = '<tr class="data-row"><td class="data-cell">' . ucfirst( $translator->translate( isset($bucket['label']) ? $bucket['label'] : $key ) ) .
					'</td><td class="number">' . formatNumber( $bucket['totalUnit_sum'] ) . '</td></tr>';
	}
	
	$buffer[]='</table></div>';
	$buffer[]='<div style="display:inline-block;vertical-align:top;margin:5px 0 0 50px;"><canvas style="width:289px;height:289px;" id="specimen_perCollectionTypeChart"></canvas></div>';
	
	$c->makeBlock(
		[ "cell" => CLASS_FULL, "info" => "normal-right-aligned" ],
		[ "title" => $translator->translate("Collection categories by specimen count"), "main" => implode("\n",$buffer) ]
	);
	
	echo $c->getBlockRow();	
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$buffer=[];
	$buffer[]='<div style="display:inline-block;margin-bottom:-25px;"><table id="taxon_groupByRank" class="data-table" style="width:325px;">';

	foreach((array)$data->taxon_groupByRank as $rank => $doc_count)
	{
		$buffer[] = '<tr class="data-row"><td class="data-cell">' .  $rank . '</td><td class="number">' . formatNumber( $doc_count ) . '</td></tr>';
	}
	
	$buffer[]='</table></div>';
	
	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "normal-central"  ],
		[ "title" => $translator->translate("Number of taxa per rank"), "main" => implode("\n",$buffer), "info" => $translator->translate( "Breakdown of taxa per rank in the taxon index. The index does not contain individual records for taxa above species level." )  ]
	);
	
	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[
			"title" => $translator->translate("Unique scientific names with specimens"), "main" => formatNumber( $data->specimen_acceptedNamesCardinality ),
			"subscript" => $translator->translate("full scientific names"), 
			"info" => $translator->translate( "Number of unique full scientific names registered as identification for NBA specimen records." ) ]
	);

	$buffer=[];
	$buffer[]='<h3>'.$translator->translate("Number of unique accepted names").'</h3>';
	$buffer[]='<h1>'.formatNumber( $data->taxon_acceptedNamesCardinality ) . '</h1>';
        
	$buffer[]='<h3>'.$translator->translate("Number of unique synonyms").'</h3>';
	$buffer[]='<h1>' . formatNumber( $data->taxon_synonymCardinality ) . '</h1>';
	
	$buffer[]='<h3>'.$translator->translate("Number of unique vernacular names").'</h3>';
	$buffer[]='<h1>'.formatNumber( $data->taxon_vernacularNamesCardinality ) . '</h1>';

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "simple-central", "info" => "simple-central" ],
		[ "title" => $translator->translate("Name count"), "main" => implode("\n", $buffer), "info" => $translator->translate( "Number of unique names registered in the NBA taxon index." ) ]
	);

	echo $c->getBlockRow();

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$buffer=[];
	$buffer[]='<div id="dutchMap1" style="width: 350px; height: 450px;"></div>';
	
	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "simple-central" ],
		[ "title" => $translator->translate("Specimens per Dutch province"), "main" => implode("\n", $buffer) ]
	);

	$buffer=[];
	$buffer[]='<div style="display:inline-block;margin-bottom:-25px;">';
	$buffer[]='<table class="data-table" style="width:325px;float:left;margin-right:25px;">';
	$i=0;
	$x=0;
	foreach((array)$provinces as $province=>$numbers)
	{
		if ($numbers['valid']!==true) continue;
		if ($i++==6)
		{
			$buffer[] = '</table><table class="data-table" style="width:325px;float:left;margin-right:25px;">';
		}
		$buffer[] = '<tr class="data-row">
				<td class="data-cell">' .  $province . '</td>
				<td class="number">' . formatNumber( $numbers['doc_count'] ) . '</td>
				<td class="number" style="width:20px">(' . $numbers['percentage'] . '%)</td>
			</tr>';
			$x+=$numbers['doc_count'];
	}
	
	$buffer[]='</table></div>';

	$c->makeBlock(
		[ "cell" => CLASS_TWO_THIRD, "main" => "simple-central", "info" => "normal-central" ],
		[ "title" => $translator->translate("Specimen records per Dutch province"), "main" => implode("\n", $buffer), "info" => $translator->translate("Number of registered specimen records per Dutch province.") ]
	);

	echo $c->getBlockRow();
	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$countryCutOff=10;
	
	$buffer=[];
	$buffer[]='<div style="display:inline-block;margin-bottom:-55px;">';
	$buffer[]='<table class="data-table" style="width:340px;float:left;margin-left:15px;margin-right:25px;">';

	
	$buffer[] = '<tr><th colspan=2" class="table-header">' .  sprintf( $translator->translate("Country top %s"), $countryCutOff ).'</th></tr>';
	
	$i=0;
	foreach((array)$world as $country)
	{
		if ($country['label']=='nederland') continue;
		if ($i++>=$countryCutOff) break; 
		$buffer[] = '<tr><td>' . ucwords($country['label']) . '</td><td class="number">' . formatNumber( $country['doc_count'] ) . '</td></tr>';
	}

	$buffer[]='</table></div>';	
	$buffer[]='<div id="worldMap1" style="width: 650px; height: 400px; float:right;margin:0 20px 0 60px;"></div>';	


	$c->makeBlock(
		[ "cell" => CLASS_FULL, "main" => "simple-central", "info" => "normal-central" ],
		[
			"title" => $translator->translate("Registered specimen records per country (without The Netherlands)"),
			"main" => implode("\n", $buffer), 
			"info" => ""
		]
	);

	echo $c->getBlockRow();	

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$maxShow_collections=6;
	$maxShow_typeStatuses=5;
	$buffer=[];
	$buffer[]='<div style="display:inline-block;margin-bottom:-55px;">';
	$buffer[]='<table class="data-table" style="width:340px;float:left;margin-left:15px;margin-right:25px;">';
	$i=0;

	$b=array_slice($data->specimen_typeStatusPerCollectionType,0,6);
       
	$i=0;
	foreach((array)$b as $collectionType)
	{
		$buffer[]='<tr class="main-item"><td colspan="2">'. $translator->translate( $collectionType['collectionType'] ) . ' (<span class="number">' . formatNumber( $collectionType['count'] ) . ' ' . $translator->translate('total') . '</span>)</tr>';

		$bb=array_slice($collectionType['values'],0,5);

		foreach((array)$bb as $typeStatus)
		{
			$buffer[]='<tr class="sub-item"><td><a href="'.$bpRootUrl . sprintf($urls->bioportalSearchCollectionAndType,$collectionType['collectionType'],$typeStatus['identifications.typeStatus']).'">' . $typeStatus['identifications.typeStatus'] . '</a></td><td class="number">' . formatNumber( $typeStatus['count'] ) . '</td></tr>';
		}
		
		$buffer[]='<tr class="no-item"><td colspan="2">&nbsp;</td></tr>';

		if ($i++%2==1) 
		{
			$buffer[] = '</table><table class="data-table" style="width:340px;float:left;margin-right:25px;">';
		}
	}

	$buffer[]='</table></div>';	

	$c->makeBlock(
		[ "cell" => CLASS_FULL, "main" => "simple-central", "info" => "normal-central" ],
		[
			"title" => $translator->translate("Type status records per collection"),
			"main" => implode("\n", $buffer), 
			"info" => sprintf( $translator->translate("The %s top-most sub-collections in terms of the total number of specimens with a type status,<br />plus the %s most frequently occurring type statuses in that sub-collection."), $maxShow_collections, $maxShow_typeStatuses)
		]
	);

	echo $c->getBlockRow();

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$maxShow_collectedSpecies=20;
	$buffer=[];
	$buffer[]='<table class="data-table" style="width:90%;float:left;margin-left:15px;margin-right:25px;">';
	$i=1;
	foreach((array)$data->specimen_perScientificName as $name=>$doc_count)
	{
		if ($i++>$maxShow_collectedSpecies) break;
		
		$buffer[]='<tr><td><a href="'.$bpRootUrl . sprintf($urls->bioportalSearchAdvancedSpecimen,$name).'">' . $name . '</a></td><td class="number">' . formatNumber( $doc_count ) . '</td></tr>';
	}
	$buffer[]='</table>';	
	$c->makeBlock(
		[ "cell" => CLASS_HALF, "main" => "big-simple-central", "info" => "normal-central" ],
		[
			"title" => $translator->translate("Most collected (sub)species"),
			"main" => implode("\n", $buffer), 
			"info" => sprintf( $translator->translate("Top %s of the most collected species or subspecies, measured by number of registered specimen records."),$maxShow_collectedSpecies)
		]
	);


	$maxShow_collectors=15;
	$buffer=[];
	$buffer[]='<table class="data-table" style="width:90%;float:left;margin-left:15px;margin-right:25px;">';
	$i=1;
	foreach($data->specimen_collectionTypeCountPerGatherer as $key=>$val)
	{
		if ($i++>=$maxShow_collectors) break;
		
		if (substr_count($val['gatheringEvent.gatheringPersons.fullName'],",")==1)
		{
			$collectorname=explode(", ", $val['gatheringEvent.gatheringPersons.fullName'], 2);
			$collectorname=trim($collectorname[1]) . ' ' . trim($collectorname[0]);
		}
		else
		{
			$collectorname=$val['gatheringEvent.gatheringPersons.fullName'];
		}
		
		$buffer[]='
			<tr class="main-item">
				<td colspan="2" onclick="$(\'.list'.$key.'\').toggle();" class="toggle">' . 
					'<span>' .$collectorname . '</span><br />
					<span class="list' . $key . ' grey">&#9660;</span>
					<span class="list' . $key . ' grey invisible">&#9650;</span>
					<span class="post-fix">' . sprintf( $translator->translate('%s registered specimen records in %s collection%s'), formatNumber( $val['count'] ), count($val['values']) , (count($val['values'])>1 ? 's' : '') ) . '</span>
				</td>
			</tr>';

		foreach((array)$val['values'] as $collection)
		{
			$buffer[]='<tr class="sub-item invisible list'.$key.'"><td><a href="'.$bpRootUrl . sprintf($urls->bioportalSearchCollectorAndCollection,$collection['collectionType'],
				$val['gatheringEvent.gatheringPersons.fullName']).'">' . ucfirst( $translator->translate($collection['collectionType']) ). '</a></td><td class="number">' . formatNumber( $collection['count'] ) . '</td></tr>';
		}
	}	

	$buffer[]='</table>';	

	
	$c->makeBlock(
		[ "cell" => CLASS_HALF, "main" => "big-simple-central", "info" => "normal-central" ],
		[
			"title" => sprintf($translator->translate("Top %s collectors"),$maxShow_collectors),
			"main" => implode("\n", $buffer), 
			"info" => sprintf($translator->translate("Top %s collectors having the most specimen records registered to their name, plus the collections they've contributed to."),$maxShow_collectors)
		]
	);

	echo $c->getBlockRow();

	echo '<br clear="all" />';
 
	$buffer=ob_get_clean();

	$w = new webPageStealer;
	$w->setUrl( $bpRootUrl . '?language=' . $language );
	$w->stealPage();
	$w->replaceElementByXPath( "//div[@class='large-12 main columns']", ["element"=>"div", "attributes"=>["id"=>"dashboard_data"], "content" => $buffer, "html" => true ] );
	$w->replaceElementById( "naturalis-header" );
	$w->replaceElementsByTag( "title", ["element"=>"title", "content"=>"BioPortal Dashboard"] );

	echo $w->getNewPage();

?>	


<script src="js/highcharts/highmaps.js"></script>
<script src="js/highcharts/exporting.js"></script>
<script src="js/highcharts/nl-all.js"></script>
<script src="js/highcharts/world-robinson.js"></script>

<script>

String.prototype.reverse = function() {	
	var o = '';
	for (var i = this.length - 1; i >= 0; i--)
	o += this[i];
	return o;
}	

var defaultColors=['#51574a','#8e8c6d','#e2975d','#c94a53','#993767','#9163b6','#7c9fb0','#447c69','#e4bf80','#f19670','#be5168','#65387d','#e279a3','#5698c4','#74c493','#e9d78e','#e16552','#a34974','#4e2472','#e0598b','#9abf88'];

$(document).ready(function(e)
{
	var specimen_perCollectionTypeData = {data:[],colors:[],labels:[]};
	
<?php

	$i=0;
	foreach((array)$categoryBuckets as $key=>$bucket)
	{
		echo "specimen_perCollectionTypeData.data.push('" . $bucket['totalUnit_sum']. "');\n";
		echo "specimen_perCollectionTypeData.colors.push(defaultColors[".$i++."]);\n";
		echo "specimen_perCollectionTypeData.labels.push('" . ucfirst( $translator->translate( isset($bucket['label']) ? $bucket['label'] : $key ) ) . "');\n";
	}

?>
	//http://www.chartjs.org/docs/latest/charts/doughnut.html
	new Chart(
		document.getElementById("specimen_perCollectionTypeChart"),
		{
			"type":"doughnut",
			"data": {
				"labels": specimen_perCollectionTypeData.labels,
				"datasets": [ {"label":"Collection types","data":specimen_perCollectionTypeData.data,"backgroundColor":specimen_perCollectionTypeData.colors} ]
			},
			"options" : {
				"legend" : { "display" : false, "position" : "left", "fullWidth" : true },
				"events": ["mousemove", "mouseout"],
				"onHover" : function(chart,area,fu) { 
					if (area[0]) 
					{
						var label = specimen_perCollectionTypeData.labels[area[0]._index];
						$('#specimen_perCollectionTypeTable tr.data-row td.data-cell').each(function()
						{
							$(this).parent("tr").removeClass("hover-row");
							if ($(this).html()==label)
							{
								$(this).parent("tr").addClass("hover-row");
							}
						});
					}
				},
				"animation" : { "animateRotate" : false }
			}
		});
	
	$('body').bind("mousemove",function()
	{
		$('#specimen_perCollectionTypeTable tr.data-row td.data-cell').each(function()
		{
			$(this).parent("tr").removeClass("hover-row");
		});		
	});

	$('#specimen_perCollectionTypeChart').bind('mousemove', function(e)
	{
		e.stopPropagation();
	});	
	

	// See API docs for 'joinBy' for more info on linking data and map.
	// https://code.highcharts.com/
	var dataDutchMap1 = [
	<?php foreach($provinces as $val) { echo  "['".$val['code']."', ".$val['doc_count']."],\n"; } ?>
	];

	Highcharts.mapChart('dutchMap1', {
		chart: { map: 'countries/nl/nl-all' }, mapNavigation: { enabled: false }, colorAxis: { min: 0 }, title: { text: '' },
		series: [{ data: dataDutchMap1, name: 'Specimens', states: { hover: { color: '#BADA55' } }, dataLabels: { enabled: true, format: '{point.name}' } }]
	});	

	var dataWorldMap1 = [
	<?php foreach($world as $country) { 
			if ($country['label']=='nederland') continue;
			echo  "['".strtolower($country['iso_code'])."', ".$country['doc_count']."],\n"; } ?>
	];

	Highcharts.mapChart('worldMap1', {
		chart: { map: 'custom/world-robinson' }, mapNavigation: { enabled: false }, colorAxis: { min: 0 }, title: { text: '' },	
		mapNavigation: { enabled: true, buttonOptions: { verticalAlign: 'bottom' } },
		series: [{ data: dataWorldMap1, name: 'Specimens', states: { hover: { color: '#BADA55' } }, dataLabels: { enabled: true, format: '{point.name}' } }]
	});	
	
	$('#language-menu > li').each(function()
	{
		var e=$(this).find('a');
		e.attr('href','/dashboard?language=' + e.html().toLowerCase());
	});
	
});

</script>
