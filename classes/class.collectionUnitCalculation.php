<?php

/*	

botany
	vellen zitten in folders zitten in dozen
	hogere planten op objectniveau (=vel) gedigitaliseerd
	lagere planten alleen de dozen (denkt marian), maar zitten dan ook (meestal) niet in folders, want specimen zijn 3D van aard
	
bewaareenheden = BE in CRS
beheereenheden = specimen (wel tellen, 1 als niet gedefinieerd) -> OOK MEE NEMEN, maar (ook) op basis gemiddelden uit PDF

*/		


// https://drive.google.com/file/d/0BwegJAX7tT_IcTBiUl9JTnVrdlU/view?ts=59bbabae
// ignoring: 2D materiaal beheereenheden (report), Arts (NBA), Miscellaneous (NBA)
// entomologie: geen specimen omdat die dubbelen met bewaareenheden (alle bewaareenheden zijn gedigitaliseerd, slechts een deel van de specimen - en die zijn dubbel)
// mineralogie en petrologie> > _other = gewogen gemiddelde
// vertebraten vogels > collectionEstimatesSpecimen > _other copied from 'droog en alcohol'


	class collectionUnitCalculation {
		
		private $mapping2016ReportCategoryToCollection;
		private $collectionUnitEstimates=[];
		private $storage_sumPerColl_withIndivCount;
		private $storage_docCountPerColl_withoutIndivCount;
		private $specimen_prepTypePerCollection;
		private $specimen_noPrepTypePerCollection;
		private $specimen_kindOfUnitPerCollection;
		private $normalizedSpecimen;
		private $errors=[];
		
		public function __construct()
		{
			$this->initTransformationObject();
			$this->initCollectionUnitEstimates();
		}
		
		public function runCalculations()
		{
			$this->normalizeSpecimenPreparationTypes();
			$this->calculateStorageRecordsWithIndividualCount();
			$this->calculateStorageRecordsWithoutIndividualCount();
			$this->calculatSpecimenCount();
			$this->calculateCollectionUnitEstimates();
		}
		
		public function setStorageSumPerCollWithIndivCount( $data )
		{
			$this->storage_sumPerColl_withIndivCount = $data;
		}

		// storage units that have NO individualCount -> use estimates from report
		public function setStorageSumPerCollWithoutIndivCount( $data )
		{
			$this->storage_docCountPerColl_withoutIndivCount=$data;
			$this->normalizeStorageMounts();
		}

		public function setSpecimenPrepTypePerCollection( $data )
		{
			$this->specimen_prepTypePerCollection = $data;
		}

		public function setSpecimenNoPrepTypePerCollection( $data )
		{
			$this->specimen_noPrepTypePerCollection = $data;
		}

		public function setSpecimenKindOfUnitPerCollection( $data )
		{
			$this->specimen_kindOfUnitPerCollection = $data;
		}

		public function calculateStorageRecordsWithIndividualCount()
		{
			// storage units that have an actual individualCount
			foreach($this->storage_sumPerColl_withIndivCount as $collection)
			{
				foreach($this->mapping2016ReportCategoryToCollection as $category=>$collections)
				{
					if ( !isset($collections['mapping']) )
						continue;
					
					if (in_array(strtolower($collection['key']),$collections['mapping']))
					{
						$this->collectionUnitEstimates[$category]['storageRecordsWithIndividualCount_number']+=$collection['doc_count'];
						$this->collectionUnitEstimates[$category]['storageRecordsWithIndividualCount_sum']+=$collection['indiv_count']['value'];
						break;
					}
				}
			}

			//q( $this->collectionUnitEstimates,1);
		}			
		
		public function calculateStorageRecordsWithoutIndividualCount()
		{
			foreach($this->storage_docCountPerColl_withoutIndivCount as $collection=>$mounts)
			{
				foreach($this->mapping2016ReportCategoryToCollection as $category=>$collections)
				{
					if ( !isset($collections['mapping']) )
						continue;

					if (in_array(strtolower($collection),$collections['mapping']))
					{
						if (!isset($collections['collectionEstimatesStorageUnits'])) continue;

						foreach($mounts as $mount=>$value)
						{
							$product=0;
							if (!isset($collections['collectionEstimatesStorageUnits'][$mount]))
							{
								if (!isset($collections['collectionEstimatesStorageUnits']["_other"]))
								{
									$product = (int)$value * 1;
									$this->collectionUnitEstimates[$category]['storageRecordsWithoutIndividualCount_number']+=1;
								}
								else
								{
									$product = (int)$value * $collections['collectionEstimatesStorageUnits']["_other"];
									$this->collectionUnitEstimates[$category]['storageRecordsWithoutIndividualCount_number']+=$collections['collectionEstimatesStorageUnits']["_other"];
								}
							}
							else
							{
								$product = (int)$value * $collections['collectionEstimatesStorageUnits'][$mount];
								$this->collectionUnitEstimates[$category]['storageRecordsWithoutIndividualCount_number']+=$collections['collectionEstimatesStorageUnits'][$mount];
							}
						
							$this->collectionUnitEstimates[$category]['storageRecordsWithoutIndividualCount_estimated_sum']+=$product;
						}
					}
				}
			}

			//q( $this->collectionUnitEstimates ,1 );			
		}
		
		public function normalizeSpecimenPreparationTypes()
		{
			$this->normalizedSpecimen=[];
			foreach( (array)$this->specimen_prepTypePerCollection as $bucket )
			{
				$d = $this->getPrepTypeKeyByCollection($bucket['key']);
				if (!is_null($d) && $d!='preparationType')
					continue;
				
				$this->normalizedSpecimen[$bucket['key']]=[];
				if (isset($bucket['prepTypes']))
				{
					$boxes=$drawers=0;
					foreach( $bucket['prepTypes']['buckets'] as $subBucket )
					{
						$b=strtolower($subBucket['key']);
						if (!isset($this->normalizedSpecimen[$bucket['key']][$b]))
						{
							$this->normalizedSpecimen[$bucket['key']][$b]=(int)$subBucket['doc_count'];
						}
						else
						{
							$this->normalizedSpecimen[$bucket['key']][$b]+=(int)$subBucket['doc_count'];
						}
					}
						
				}
			}

			if (!is_null($this->specimen_noPrepTypePerCollection)) 
			{
				foreach( (array)$this->specimen_noPrepTypePerCollection as $bucket )
				{
					if (!isset($this->normalizedSpecimen[$bucket['key']])) $this->normalizedSpecimen[$bucket['key']]=[];
					$this->normalizedSpecimen[$bucket['key']]['_other']=(int)$bucket['doc_count'];
				}
			}

			foreach( (array)$this->specimen_kindOfUnitPerCollection as $bucket )
			{
				$d = $this->getPrepTypeKeyByCollection($bucket['key']);
				if (is_null($d) || (!is_null($d) && $d!='kindOfUnit'))
					continue;

				$this->normalizedSpecimen[$bucket['key']]=[];
				if (isset($bucket['kindsOfUnit']))
				{
					$boxes=$drawers=0;
					foreach( $bucket['kindsOfUnit']['buckets'] as $subBucket )
					{
						$b=strtolower($subBucket['key']);
						if (!isset($this->normalizedSpecimen[$bucket['key']][$b]))
						{
							$this->normalizedSpecimen[$bucket['key']][$b]=(int)$subBucket['doc_count'];
						}
						else
						{
							$this->normalizedSpecimen[$bucket['key']][$b]+=(int)$subBucket['doc_count'];
						}
					}
				}
			}			
			
		}

		public function addStaticNumbers( $p )
		{
			if ( !isset($p['category']) ) return;
			
			$c = strtolower($p['category']);
			
			if ( !isset($this->collectionUnitEstimates[$c]) )
			{
				$this->collectionUnitEstimates[$c] = $this->initSumObject();
				if ( isset($p['label']) ) $this->collectionUnitEstimates[$c][$p['label']];
			}
			
			if ( isset($p['specimenNumber']) ) $this->collectionUnitEstimates[$c]['storageRecordsWithoutIndividualCount_number'] += $p['specimenNumber'];
			if ( isset($p['specimenCount']) ) $this->collectionUnitEstimates[$c]['storageRecordsWithoutIndividualCount_estimated_sum'] += $p['specimenCount'];
			if ( isset($p['storageNumber']) ) $this->collectionUnitEstimates[$c]['storageRecordsWithIndividualCount_number'] += $p['storageNumber'];
			if ( isset($p['storageCount']) ) $this->collectionUnitEstimates[$c]['storageRecordsWithIndividualCount_sum'] += $p['storageCount'];

			//q($this->collectionUnitEstimates,1);
		}
		
		public function calculatSpecimenCount()
		{
			foreach( $this->normalizedSpecimen as $collection => $prepTypes)
			{
				foreach($this->mapping2016ReportCategoryToCollection as $category=>$collections)
				{
					if ( !isset($collections['mapping']) ) continue;

					if (in_array(strtolower($collection),$collections['mapping']))
					{
						if ( $category == 'entomologie' ) continue; // these overlap with the (more complete) storage units
			
						foreach( $prepTypes as $prepType=>$doc_count ) 
						{
							$this->collectionUnitEstimates[$category]['specimenUnit_count']+=$doc_count;

							$product = 1 * $doc_count;
							
							if (!isset($collections['specimenCategoryToPrepType']))
							{
								if (isset($collections['collectionEstimatesSpecimen']) && isset($collections['collectionEstimatesSpecimen']['_other']))
								{
									$product=$collections['collectionEstimatesSpecimen']['_other'] * $doc_count;
								}
								else
								{
									//$product = 1 * $doc_count;
								}
							}
							else
							{
								$dryOrWet=null;
								foreach($collections['specimenCategoryToPrepType'] as $dryOrWetKey => $dryOrWetKeyVal)
								{
									if (in_array($prepType, $dryOrWetKeyVal))
									{
										$dryOrWet=$dryOrWetKey;
										break;
									}
								}
								if (is_null($dryOrWet))
								{
									
									$this->addError( $category .": '" . $prepType . "' not mapped ");
									
									if (isset($collections['collectionEstimatesSpecimen']) && isset($collections['collectionEstimatesSpecimen']['_other']))
									{
										$product=$collections['collectionEstimatesSpecimen']['_other'] * $doc_count;
									}
									else
									{
										//$product = 1 * $doc_count;
									}
								}
								else
								{
									if (isset($collections['collectionEstimatesSpecimen']) && isset($collections['collectionEstimatesSpecimen'][$dryOrWet]))
									{
										$product=$collections['collectionEstimatesSpecimen'][$dryOrWet] * $doc_count;
									}
									else
									{
										if (isset($collections['collectionEstimatesSpecimen']) && isset($collections['collectionEstimatesSpecimen']['_other']))
										{
											$product=$collections['collectionEstimatesSpecimen']['_other'] * $doc_count;
										}
										else
										{
											//$product = 1 * $doc_count;
										}
									}
								}
							}

							$this->collectionUnitEstimates[$category]['specimenUnit_sum_estimate']+=$product;
						}
					}
				}
			}
		}

		public function roundEstimates()
		{
			foreach( $this->collectionUnitEstimates as $key=>$val )
			{
				$this->collectionUnitEstimates[$key]['storageRecordsWithoutIndividualCount_number']=round($val['storageRecordsWithoutIndividualCount_number'],0);
				$this->collectionUnitEstimates[$key]['specimenUnit_sum_estimate']=round($val['specimenUnit_sum_estimate'],0);
				$this->collectionUnitEstimates[$key]['storageRecordsWithoutIndividualCount_estimated_sum']=round($val['storageRecordsWithoutIndividualCount_estimated_sum'],0);
				$this->collectionUnitEstimates[$key]['storageRecordsWithIndividualCount_sum']=round($val['storageRecordsWithIndividualCount_sum'],0);
			}
		}
		
		public function getCollectionUnitEstimates()
		{
			return $this->collectionUnitEstimates;
		}

		public function sortCategoryBuckets( $field='totalUnit_sum', $dir='desc')
		{
			uasort ($this->collectionUnitEstimates, function ($a, $b) use ($field,$dir)
			{
			   return ($dir!='desc' ? 1 : -1) * ($a[$field] - $b[$field]);
			});
		}
		
		public function getGrandUnitsTotal()
		{
			$grandTotal=0;
			foreach( $this->getCollectionUnitEstimates() as $key=>$val )
			{
				$grandTotal += $val['totalUnit_sum'];
			}
			return $grandTotal;
		}
		
		public function getCategoryBuckets()
		{
			return $this->getCollectionUnitEstimates();
		}

		public function getErrors()
		{
			return $this->errors;
		}

		
		
		private function initSumObject()
		{
			return [
				'specimenUnit_count'=>0,
				'specimenUnit_sum_estimate'=>0,
				'storageRecordsWithoutIndividualCount_number'=>0,
				'storageRecordsWithoutIndividualCount_estimated_sum'=>0,
				'storageRecordsWithIndividualCount_number'=>0,
				'storageRecordsWithIndividualCount_sum'=>0,
			];
		}

		private function initCollectionUnitEstimates()
		{
			foreach($this->mapping2016ReportCategoryToCollection as $category=>$collections)
			{
				if (!isset($this->collectionUnitEstimates[$category]))
				{
					$this->collectionUnitEstimates[$category]=$this->initSumObject();
					$this->collectionUnitEstimates[$category]['label']=$this->mapping2016ReportCategoryToCollection[$category]['label'];
				}
			}
		}
	
		private function normalizeStorageMounts()
		{
			$storageDocCountPerCollWithoutIndivCount=[];
			foreach(  $this->storage_docCountPerColl_withoutIndivCount as $bucket )
			{
				//echo $bucket['key']," : ",$bucket['doc_count'], "\n";
				$storageDocCountPerCollWithoutIndivCount[$bucket['key']]=[];
				if (isset($bucket['sum_count']))
				{
					$boxes=$drawers=0;
					foreach( $bucket['sum_count']['buckets'] as $subBucket )
					{
						//echo " -- " ,$subBucket['key']," : ",$subBucket['doc_count'], "\n";
						if (stripos($subBucket['key'],"box")!==false)
						{
							$boxes+=(int)$subBucket['doc_count'];
						}
						else
						if (stripos($subBucket['key'],"drawer")!==false)
						{
							$drawers+=(int)$subBucket['doc_count'];
						}
						else
						{
							$b=strtolower($subBucket['key']);
							if (!isset($storageDocCountPerCollWithoutIndivCount[$b]["box"]))
							{
								$storageDocCountPerCollWithoutIndivCount[$bucket['key']][$b]=(int)$subBucket['doc_count'];
							}
							else
							{
								$storageDocCountPerCollWithoutIndivCount[$bucket['key']][$b]+=(int)$subBucket['doc_count'];
							}
						}
						
					}
				}
				$storageDocCountPerCollWithoutIndivCount[$bucket['key']]["box"]=$boxes;
				$storageDocCountPerCollWithoutIndivCount[$bucket['key']]["drawer"]=$drawers;
			}
			
			$this->storage_docCountPerColl_withoutIndivCount = $storageDocCountPerCollWithoutIndivCount; 
			
		}

		private function getPrepTypeKeyByCollection( $collectionType )
		{
			foreach($this->mapping2016ReportCategoryToCollection as $reportCategory=>$val)
			{
				if( isset($val['mapping']) && in_array(strtolower($collectionType),$val['mapping']) )
				{
					return isset($val['prepTypeKey']) ? $val['prepTypeKey'] : null;
				}
			}
		}

		private function calculateCollectionUnitEstimates()
		{
			foreach( $this->collectionUnitEstimates as $key=>$val )
			{
				$this->collectionUnitEstimates[$key]['totalUnit_sum']=
					(int)$val['specimenUnit_sum_estimate'] + 
					(int)$val['storageRecordsWithoutIndividualCount_estimated_sum'] + 
					(int)$val['storageRecordsWithIndividualCount_sum'];

			}
		}

		
		private function addError( $error )
		{
			$this->errors[]=$error;
		}
		
		private function initTransformationObject()
		{
			
			// averages: https://drive.google.com/a/naturalis.nl/file/d/0BwegJAX7tT_IcTBiUl9JTnVrdlU/view?usp=sharing
			
			$this->mapping2016ReportCategoryToCollection=[
				'botanie hoge planten' => [
					'label' => 'Hogere planten',
					'mapping' => [ 'botany' ],
					'prepTypeKey' => 'preparationType',
					'specimenCategoryToPrepType' => [ '_other' => [ '_other' ] ],
					'collectionEstimatesSpecimen' => [ '_other' => 1  ],
				],
				'botanie lage planten' =>  [
					'label' => 'Lagere planten',
					'mapping' => [ 'lagere planten' ],
					'collectionEstimatesStorageUnits' => [ '_other' => 40.30 ],
				],
				'entomologie' => [
					'label' => 'Entomologie',
					'mapping' => [ 'entomology','lepidoptera','hymenoptera','remaining insects','coleoptera','diptera','diptera0','orthopteroidea','odonata','hemiptera','entomologyhyj','collembola'],
					'prepTypeKey' => 'preparationType',
					'specimenCategoryToPrepType' => [
						'nat' => [ 'alcohol', 'alcohol 70%'  ], 
						'droog' => [ 'air dried', 'pinned specimen', 'microscopic slide', 'tube', 'embedded', 'envelope', 'embalmed', 'microscopic slide', 'bag', 'drawer' ]
					],
					'collectionEstimatesStorageUnits' => [ 'drawer' => 145.98, 'jar' => 10.31, 'box' => 83.42, '_other' => 218.73 ],
				],
				'vertebraten zoogdieren' => [
					'label' => 'Zoogdieren',
					'mapping' => [ 'mammalia' ],
					'prepTypeKey' => 'preparationType',
					'specimenCategoryToPrepType' => [
						'nat' => [ 'alcohol >70%', 'wet specimen', 'alcohol', 'alcohol 70%', 'alcohol 96%', 'formalin', 'glycerin' ],
						'droog' => [ 'loose bones', 'study skin', 'droog', 'mounted skin', 'mounted', 'microscopic slide', 'not applicable', 'air dried', 'flat skin', 'mummified specimen', 'box', 'semstub', 'mounted skeleton', 'skeletonized', 'full skeleton', 'skull and horns trophy', 'standard mount', 'trophy mount', 'glassine', 'skin', 'partly mounted', 'tube', 'card mounted', 'unknown', 'dry', '_other' ]
					],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00  ],
					'collectionEstimatesSpecimen' => [ 'droog' => 1.23, 'nat' => 1.20 ],
				],
				'vertebraten reptielen en amfibieën' =>  [
					'label' => 'Reptielen en amfibieën',
					'mapping' => [ 'amphibia and reptilia' ],
					'prepTypeKey' => 'preparationType',
					'specimenCategoryToPrepType' => [
						'nat'=> ['alcohol', 'formalin', 'alcohol 70%', 'glycerine', 'wet specimen', 'formalin 5%', 'alcohol-formaline' ],
						'droog' => [ 'droog', 'air dried', 'mounted skin', 'cast', 'loose bones', 'mounted skeleton', 'dry' ],
						'_other' => [ 'alcohol & dry', '_other' ]
					],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00  ],
					'collectionEstimatesSpecimen' => [ 'droog' => 1.18, 'nat' => 1.79 ],
				],
				'vertebraten vogels' => [
					'label' => 'Vogels',
					'mapping' => [ 'aves' ],
					'prepTypeKey' => 'kindOfUnit',
					'specimenCategoryToPrepType' => [
						'droog en alcohol' => [ 'skin', 'skeleton (whole)', 'Wing', 'skull', 'feather', 'Not applicable', 'skin (part)', 'WholeOrganism', 'Head', 'skeleton part', 'skeleton', 'unknown', 'Leg', 'tail', 'wings & head', 'DNA-extract', 'tissue', 'wings & half tail', 'cast', 'beak', 'footprint', 'soft body parts', 'stomach content', 'wings & bill', 'wings & feathers','dna-extract', 'leg' ,'head', 'wholeorganism', 'wing', 'not applicable' ],
						'nesten en eieren' => [ 'nest', 'egg' ]
					],
					'collectionEstimatesSpecimen' => [ 'droog en alcohol' => 1.05, 'nesten en eieren' => 3.13 ],
				],
				'vertebraten vissen' => [
					'label' => 'Vissen',
					'mapping' => [ 'pisces' ],
					'prepTypeKey' => 'preparationType',
					'specimenCategoryToPrepType' => [ 'droog' => [ 'air dried' ], 'nat' => [ 'nog te migreren' ] ],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00 ],
					'collectionEstimatesSpecimen' => [ 'droog' => 1.00, 'nat' => 1.00 ],
				],
				'evertebraten overige collecties' => [
					'label' => 'Evertebraten',
					'mapping' => [ 'crustacea','cnidaria','echinodermata','porifera','vermes','hydrozoa','chelicerata and myriapoda','tunicata','bryozoa','brachiopoda','foraminifera','protozoa', 'invertebrates'],
					'prepTypeKey' => 'preparationType',
					'collectionEstimatesStorageUnits' => [ 'drawer'=> 73.00, 'jar' => 6.96, '_other' => 1.00 ], // _other = Shelf, Box
					'collectionEstimatesSpecimen' => [ '_other' => 4.08 ]
				],
				'evertebraten mollusca' => [
					'label' => 'Mollusca',
					'mapping' => [ 'mollusca'],
					'prepTypeKey' => 'preparationType',
					'collectionEstimatesStorageUnits' => [ 'slide drawer' => 26.71, 'jar' => 8.38, '_other' => 1.00 ], // _other = Shelf, Box
					'collectionEstimatesSpecimen' => [ '_other' => 11.70 ]
				],
				'paleontologie' => [
					'label' => 'Paleontologie',
					'mapping' => [ 'paleobotany','paleontology vertebrates','paleontology invertebrates','paleontology','macro vertebrates','micro vertebrates','mesozoic invertebrates','micropaleontology','cainozoic mollusca', 'paleozoic invertebrates' ],
					'prepTypeKey' => 'preparationType',
					'specimenCategoryToPrepType' => [
						'_other' => [ 'fossilized', 'not applicable', 'peel', 'fossilized', 'fossilized specimen', 'air dried', 'unknown', 'microscopic slide', 'fossilized specimen', 'box' ]
					],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00 ],
					'collectionEstimatesSpecimen' => [ '_other' => 12.64 ],
				],
				'mineralogie en petrologie' => [
					'label' => 'Mineralogie en petrologie',
					'prepTypeKey' => 'preparationType',
					'mapping' => [ 'mineralogy','petrology','mineralogy and petrology' ],
					'specimenCategoryToPrepType' => [
						'preparaten' => [ 'thin section' ],
						'monsters' => [ 'not applicable', 'fossilized' ],
						'_other' => [ 'nog te migreren' ]
					],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00 ],
					'collectionEstimatesSpecimen' => [ 'preparaten' => 1.00, 'monsters' => 1.57, '_other' => 1.00 ],
				],
				'2d materiaal' => [
					'label' => '2D materiaal',
					'prepTypeKey' => 'preparationType',
					'mapping' => [ 'arts' ],
					'collectionEstimatesSpecimen' => [ '_other' => 1  ],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00 ],
				],		
			];
		}	
	}
