<?php
//http://localhost/bioportal-dashboard/?forceDataRefresh
	/*
		to do:
		change block titles and add explanaroty texts
		dutch map that actually works
		use same map-class for world & nl (and introduce tooltips etc.)
		separate sections for siebld & dubois
		specimen tree (based on CoL taxonomy, greyed out when no specimen, tooltip for speciment lists)
		complete taxonrank list for "what constitutes a (sub)species?"
		re-examine collectors (odd behaviour when only one or two collections)
		storage units!
		add legends to graphs
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
	
	function q( $d, $die=false ) 
	{
		echo'<pre>';
		print_r( $d );
		echo'</pre>';
		if ($die) die();
	}
	
	
	include_once("classes/class.ndsInterface.php");
	include_once("classes/class.ndsDataHarverster.php");
	include_once("classes/class.dataCache.php");
	include_once("classes/class.webPageStealer.php");
	include_once("classes/class.contentBlocks.php");
	include_once("classes/class.collectionUnitCalculation.php");

	include_once("config/cfg.iso3166.php");
	include_once("config/settings.php");
	
	$esServer=config::elasticsearchAddress();
	$bpHomePage=config::bioportalHomepage();
	$dbAccess=config::databasAccessParameters();

	// ?forceDataRefresh forces, well, data refresh (and re-caching)
	$forceDataRefresh = (isset($_REQUEST["forceDataRefresh"]));

	// needs actual database check
	function needFreshData( $force )
	{
		if ($force) return true;
		return false;
	}

	// map to drupal translations?
	function __( $text )
	{
		return $text;
	}

	function formatNumber( $number, $decimals=0 )
	{
		return number_format( $number, $decimals, ",", ".");
	}

	$cache=new dataCache;
	$cache->setDbParams( $dbAccess );
	$cache->setProject( "nba_data" );

	if (!needFreshData( $forceDataRefresh ))
	{
		$data = $cache->getData();
	}
		
	if (needFreshData( $forceDataRefresh ) || !isset($data->specimen_totalCount))
	{
		$n=new ndsDataHarvester;
		$n->setServer( $esServer  );
		$n->setServicePaths( [ 'taxon' => '/taxon/', 'specimen' => '/specimen/', 'multimedia'=>'/multimedia/', 'geo'=>'/geo/', 'storageunits'=>'/storageunits/' ] );
		$n->setQueryParameterField( 'query' );
		$n->setNdsInterface( new ndsInterface );
		$n->initialize();
		$n->runQueries();
		$data = $n->getData();
		$cache->emptyCache();
		$cache->storeData( $data );
	}

	
	// callcuating totals
	$calculator=new collectionUnitCalculation;
	
	$calculator->setStorageSumPerCollWithIndivCount( $data->storage_sumPerColl_withIndivCount['collections']['buckets'] );
	$calculator->setStorageSumPerCollWithoutIndivCount( $data->storage_docCountPerColl_withoutIndivCount['collections']['buckets'] );
	$calculator->setSpecimenPrepTypePerCollection( $data->specimen_prepTypePerCollection['collections']['buckets'] );
	$calculator->setSpecimenNoPrepTypePerCollection( $data->specimen_noPrepTypePerCollection['collections']['buckets'] );
	$calculator->setSpecimenKindOfUnitPerCollection( $data->specimen_kindOfUnitPerCollection['collections']['buckets'] );
	
	$staticNumbers = [
		'BrahmsLowerPlants' => [ 'category' => 'Botanie lage planten', 'storageNumber' => 13527, 'average' => 40.30 ],
		'2D' => [ 'category' => '2D materiaal', 'specimenNumber' => (625500 - 2449), 'average' => 1 ],
		'PiscesLegacy' => [ 'category' => 'Vertebraten vissen', 'specimenNumber' => 116000, 'average' => 1 ],
		'StonesLegacy' => [ 'category' => 'Mineralogie en petrologie', 'specimenNumber' => 100000, 'average' => 1 ],
		'StonesNBADiscarded' => [ 'category' => 'Mineralogie en petrologie', 'specimenNumber' => 199000, 'average' => 1 ] // should be 'thin section'
	];

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

	
	$calculator->runCalculations();
	$calculator->roundEstimates();
	$calculator->sortCategoryBuckets();
	
	$collectionUnitEstimates=$calculator->getCollectionUnitEstimates();
	$grandUnitsTotal=$calculator->getGrandUnitsTotal();
	$categoryBuckets=$calculator->getCategoryBuckets();

	//q($calculator->getErrors());

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

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[
			"title" => __("Collection objects count"), "main" => formatNumber( $grandUnitsTotal ),
			"subscript" => __("objects in total"), 
			"info" => __( "registered  in the Netherlands Biodiversity API as " . formatNumber($data->specimen_totalCount) . " specimen and  " . formatNumber($data->storage_catNumberCardinality['catalogNumber_count']['value']) . " storage units (NEED TO ADD BRAHMS NUMBER)" ) ]
	);

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[ "title" => __("Taxon count"), "main" => formatNumber($data->taxon_totalCount), "subscript" => __("records in total"), "info" => __("The number of taxon records in the Netherlands Biodiversity API, sourced from the Catalogue of Life and the Dutch Species Register.") ]
	);

	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[ "title" => __("Multimedia count"), "main" => formatNumber($data->multimedia_totalCount), "subscript" => __("records in total"), "info" => __("The number of multimedia records in the Netherlands Biodiversity API data store.") ]
	);

	echo $c->getBlockRow();
	
	

	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$buffer=[];
	$buffer[]='<div style="display:inline-block;"><table class="data-table" style="width:325px;float:left;margin-right:25px;">';
	
	$i=0;
	foreach((array)$categoryBuckets as $key=>$bucket)
	{
		if ($i++==10)
		{
			$buffer[] = '</table><table class="data-table" style="width:325px;float:left;margin-right:25px;">';
		}
		$buffer[] = '<tr class="data-row"><td class="data-cell">' . ucfirst( isset($bucket['label']) ? $bucket['label'] : $key ) . '</td><td class="number">' .formatNumber( $bucket['totalUnit_sum'] ) . '</td></tr>';
	}
	
	$buffer[]='</table></div>';

	$buffer[]='<div style="display:inline-block;vertical-align:top;margin:5px 0 0 50px;"><canvas style="width:289px;height:289px;" id="specimen_perCollectionTypeChart"></canvas></div>';
	
	$c->makeBlock(
		[ "cell" => CLASS_FULL, "info" => "normal-right-aligned" ],
		[ "title" => __("Collection categories by specimen count"), "main" => implode("\n",$buffer) ]
	);
	
	/* ------------------------------------------------------------------------------------------------------------------------ */


	echo $c->getBlockRow();	

	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	

	$buffer=[];
	$buffer[]='<div style="display:inline-block;margin-bottom:-25px;"><table id="taxon_groupByRank" class="data-table" style="width:325px;">';

	foreach((array)$data->taxon_groupByRank['taxon_groupByRank']['buckets'] as $bucket)
	{
		$buffer[] = '<tr class="data-row"><td class="data-cell">' .  $bucket['key'] . '</td><td class="number">' . formatNumber( $bucket['doc_count'] ) . '</td></tr>';
	}
	
	$buffer[]='</table></div>';
	
	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "normal-central"  ],
		[ "title" => __("Number of taxa per rank"), "main" => implode("\n",$buffer), "info" => __( "Breakdown of taxa per rank in the taxon index. The index does not contain individual records for higher taxa." )  ]
	);

	
	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[
			"title" => __("Unique scientific names with specimen"), "main" => formatNumber( $data->specimen_acceptedNamesCardinality['fullScientificName']['fullScientificName']['value']  ),
			"subscript" => __("unique scientific names"), 
			"info" => __( "Number of unique full scientific names registered for NBA specimens." ) ]
	);


	$buffer=[];
	$buffer[]='<h3>Number of unique vernacular names</h3>';
	$buffer[]='<h1>'.formatNumber( $data->taxon_vernacularNamesCardinality['vernacularName']['vernacularName']['value'] ) . '</h1>';

	$buffer[]='<h3>Number of unique accepted names</h3>';
	$buffer[]='<h1>'.formatNumber( $data->taxon_acceptedNamesCardinality['acceptedName']['value'] ) . '</h1>';
        
	$buffer[]='<h3>Number of unique synonyms</h3>';
	$buffer[]='<h1>' . formatNumber( $data->taxon_synonymCardinality['synonym']['synonym']['value'] ) . '</h1>';
	
	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "simple-central" ],
		[ "title" => __("Name count"), "main" => implode("\n", $buffer), "info" => "Numbers of unique names in the NBA taxon index" ]
	);

	/*
	$c->makeBlock(
		[ "cell" => CLASS_ONE_THIRD, "main" => "big-simple-central", "info" => "big-simple-central" ],
		[ "title" => __("titles"), "main" => "SOME GRAPH HERE", "subscript" => __("datas"), "info" => __("infos") ]
	);
	*/

	echo $c->getBlockRow();

	
	/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	
?>
			
	<br clear="all" />
	
    <div id="r1">
	   

        <div class="left-float">
            <table class="normal-table no-color">
                <tr><td>
                <img src="img/partner_kaart_blanko.gif" id="netherlands-map" />
                <!-- div id="specimen_countPerProvince_NL" style="width:375px; height: 450px;"></div -->
                </td></tr>
            </table>
    
            <table class="normal-table">
                <tr><th colspan="2">specimen_countPerProvince_NL</th></tr>
                <tr><td colspan="2" class="info main">what am i looking at here?</td></tr>
                <?php
                
                    foreach((array)$data->specimen_countPerProvince_NL['country']['buckets'] as $bucket)
                    {
                        echo '<tr><td>' . $bucket['key'] . '</td><td class="number">' . $bucket['doc_count'] . '</td></tr>';
                    }
                ?>

                <tr><th colspan="2"><canvas id="specimen_countPerProvince_NL" width="300" height="300"></canvas></th></tr>
                <tr><td colspan="2" class="info secondary">more extra info</td></tr>
            </table>
			<script>
            <?php
            foreach((array)$data->specimen_countPerProvince_NL['country']['buckets'] as $key=>$bucket)
            {
                echo "specimen_countPerProvince_NLData.push({label:'" . $bucket['key'] . "',value:" . $bucket['doc_count'] . ", color: defaultColors[".$key."] });\n";
            }
            ?>
            </script> 
        </div>

        <div class="left-float">
            <table class="double-table">
                <tr><th colspan="2">specimen_typeStatusPerCollectionType<br />(top 5 > top 10)</th></tr>
                <tr><td colspan="2" class="info main">what am i looking at here?</td></tr>
                <?php
                
                    $b=array_slice($data->specimen_typeStatusPerCollectionType['collectionType']['buckets'],0,5);
                
                    foreach((array)$b as $collectionType)
                    {
                        echo '<tr class="main-item"><td colspan="2">' . $collectionType['key'] . ' (<span class="number">' . $collectionType['doc_count'] . '</span>)</tr>';
                        
                        $c=array_slice($collectionType['identifications']['typeStatus']['buckets'],0,10);
        
                        foreach((array)$c as $typeStatus)
                        {
                            echo '<tr class="sub-item"><td>' . $typeStatus['key'] . '</td><td class="number">' . $typeStatus['doc_count'] . '</td></tr>';
            
                        }
                    }
                ?>
            </table>
        </div>
    
	</div>
    
    <br clear="all" />

    <div id="r2">

        <div class="left-float">
            <?php $countryCutOff=20; ?>
    
            <table class="normal-table">
                <tr><th colspan="2">specimen_countPerCountry_NotNL (top <?php echo $countryCutOff; ?>)</th></tr>
                <?php
                    $codes=[];
                    foreach((array)$data->specimen_countPerCountry_NotNL['country']['buckets'] as $key=>$bucket)
                    {
                        if(isset($iso3166[$bucket['key']]))
                        {
                            $code=$iso3166[$bucket['key']];
                            if (isset($codes[$code]))
                                $codes[$code]+=$bucket['doc_count'];
                            else
                                $codes[$code]=$bucket['doc_count'];
                        }
    
                        if ($key==0 || $key>$countryCutOff) continue; 
                        echo '<tr><td>' . $bucket['key'] . '</td><td class="number">' . $bucket['doc_count'] . '</td></tr>';
                    }
                ?>
            </table>
        </div>
    
        <div class="left-float">
            <table class="wide-table no-color">
                <tr><td><div id="specimen_countPerCountry_NotNL" style="width:600px; height:425px;"></div></td></tr>
            </table>
            <script>
            <?php
            foreach((array)$codes as $code=>$count)
            {
                echo "countryData." . strtolower($code) .'=' . $count . "\n";
            }
            ?>
            </script>                
		</div>
	</div>

    <br clear="all" />

    <div id="r2-5">

        <div class="half-width text-block">
            some info
        </div>
            
        <div class="half-width text-block">
            some info
        </div>
        
	</div>
        
    <br clear="all" />

    <div id="r3">

        <div class="left-float">
            <table class="normal-table wide-table">
                <tr><th colspan="2">specimen_perScientificName (top 15)<br />(sub)species only</th></tr>
                <?php
                
                    foreach((array)$data->specimen_perScientificName['fullScientificName']['fullScientificName']['buckets'] as $bucket)
                    {
                        echo '<tr><td>' . $bucket['key'] . '</td><td class="number">' . $bucket['doc_count'] . '</td></tr>';
                    }
                ?>
            </table>
        </div>

    </div>
    
    <br clear="all" />

    <div id="r4">



        <div class="left-float">
            <table class="double-table">
                <tr><th colspan="2">specimen_collectionTypeCountPerGatherer<br />(top 10)</th></tr>
                <?php
                
                    foreach((array)$data->specimen_collectionTypeCountPerGatherer as $key=>$collector)
                    {
                        echo '
                            <tr class="main-item">
                                <td colspan="2" onclick="$(\'.list'.$key.'\').toggle();" class="toggle">' . 
                                    '<span class="main-item">' . $collector['collector'] . '</span> (<span class="number">' . $collector['collection_count'] . '</span> collections)
                                </td>
                            </tr>';
                        
                        foreach((array)$collector['collections'] as $collection)
                        {
                            echo '<tr class="sub-item invisible list'.$key.'"><td>' . $collection['collection'] . '</td><td class="number">' . $collection['doc_count'] . '</td></tr>';
            
                        }
                    }				
                ?>
            </table>
        </div>

        <div class="left-float">
            <table class="no-color">
                <tr><th>BioPortal</th></tr>
                <tr><td>
Het BioPortal toont de kracht van de NBA. Het is een “klant” van de NBA, een voorbeeldapplicatie die de gegevens uit de NBA via een webportaal toegankelijk maakt. Via dit portaal kan iedereen, geïnteresseerde of wetenschapper, in de gegevensbronnen van Naturalis grasduinen, met simpele zoekopdrachten, via complexe queries of op basis van specifieke geografische locaties.
                </td></tr>
                <tr><td class="info"><a href="/">BioPortal</a></td></tr>
            </table>
        </div>
        
    </div>

	
	
    <br clear="all" />


<?php
 
	$buffer=ob_get_clean();

	$w = new webPageStealer;
	$w->setUrl( $bpHomePage );
	$w->stealPage();
	$w->replaceElementByXPath( "//div[@class='large-12 main columns']", ["element"=>"div", "attributes"=>["id"=>"dashboard_data"] ] );
	echo $w->getNewPage();

?>	

<div style="display:none" id="data_buffer">
<form method=post><input type=submit value=forceRefresh><input type=hidden name=forceDataRefresh></post>
<?php echo $buffer; ?>
</div>

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
	$('#dashboard_data').html($('#data_buffer').html());

	var specimen_perCollectionTypeData = {data:[],colors:[],labels:[]};
	
<?php

	$i=0;
	foreach((array)$categoryBuckets as $key=>$bucket)
	{
		echo "specimen_perCollectionTypeData.data.push('" . $bucket['totalUnit_sum']. "');\n";
		echo "specimen_perCollectionTypeData.colors.push(defaultColors[".$i++."]);\n";
		echo "specimen_perCollectionTypeData.labels.push('" . ucfirst( isset($bucket['label']) ? $bucket['label'] : $key ) . "');\n";
	}

?>
	//http://www.chartjs.org/docs/latest/charts/doughnut.html
	new Chart(
		document.getElementById("specimen_perCollectionTypeChart"),
		{
			"type":"doughnut",
			"data": {
				"labels": specimen_perCollectionTypeData.labels,
				"datasets": [
					{"label":"Collection types","data":specimen_perCollectionTypeData.data,"backgroundColor":specimen_perCollectionTypeData.colors}
				]
			},
			"options" : {
				"legend" : {
					"display" : false,
					"position" : "left",
					"fullWidth" : true
				},
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
				"animation" : {
					"animateRotate" : false
				}
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
	
/*
	var c1 = document.getElementById('specimen_perCollectionType').getContext('2d');
	var specimen_perCollectionTypeChart = new Chart(c1).Pie(specimen_perCollectionTypeData, { animation: false });

	var c2 = document.getElementById('specimen_countPerProvince_NL').getContext('2d');
	var specimen_countPerProvince_NLChart = new Chart(c2).Pie(specimen_countPerProvince_NLData	,{ animation: false });
*/
//	https://jqvmap.com/
//	https://github.com/manifestinteractive/jqvmap

	jQuery('#specimen_countPerCountry_NotNL').vectorMap({
		map: 'world_en',
		backgroundColor: null,
		color: '#ffffff',
		hoverOpacity: 0.7,
		selectedColor: '#666666',
		enableZoom: true,
		showTooltip: true,
		values: countryData,
		scaleColors: ['#C8EEFF', '#006491'],
		normalizeFunction: 'polynomial',
	});
	
	
});
</script>
	
