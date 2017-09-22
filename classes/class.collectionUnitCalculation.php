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
		private $normalizedSpecimen;
		
		public function __construct()
		{
			$this->initTransformationObject();
			$this->initCollectionUnitEstimates();
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

		// BRAHMS BE-export not in NBA; number taken from SQL-export
		public function setLowerPlantsNumberFromBRAHMS( $num )
		{
			$this->storage_docCountPerColl_withoutIndivCount['lagere planten']['other']=$num;
		}

		public function calculateStorageRecordsWithIndividualCount()
		{
			// storage units that have an actual individualCount
			foreach($this->storage_sumPerColl_withIndivCount as $collection)
			{
				foreach($this->mapping2016ReportCategoryToCollection as $category=>$collections)
				{
					if (in_array(strtolower($collection['key']),$collections['mapping']))
					{
						$this->collectionUnitEstimates[$category]['storageRecordsWithIndividualCount_number']+=$collection['doc_count'];
						$this->collectionUnitEstimates[$category]['storageRecordsWithIndividualCount_sum']+=$collection['indiv_count']['value'];
						break;
					}
				}
			}	
		}			
		
		public function calculateStorageRecordsWithoutIndividualCount()
		{
			foreach($this->storage_docCountPerColl_withoutIndivCount as $collection=>$mounts)
			{
				foreach($this->mapping2016ReportCategoryToCollection as $category=>$collections)
				{
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
		}

		public function setSpecimenPrepTypePerCollection( $data )
		{
			$this->specimen_prepTypePerCollection = $data;
		}

		public function setSpecimenNoPrepTypePerCollection( $data )
		{
			$this->specimen_noPrepTypePerCollection = $data;
		}
		
		public function normalizeSpecimenPrepTypes()
		{
			$this->normalizedSpecimen=$this->normalizePrepTypes( $this->specimen_prepTypePerCollection,  $this->specimen_noPrepTypePerCollection );
		}

		public function calculatSpecimenCount()
		{
			foreach( $this->normalizedSpecimen as $collection => $prepTypes)
			{
				foreach($this->mapping2016ReportCategoryToCollection as $category=>$collections)
				{
					if (in_array(strtolower($collection),$collections['mapping']))
					{
						if ( $category == 'entomologie' ) continue; // these overlap with the (more complete) storage units
			
						foreach( $prepTypes as $prepType=>$doc_count ) 
						{
							$this->collectionUnitEstimates[$category]['specimenUnit_count']+=$doc_count;

							$product = 1 * $doc_count;
							
							if (!isset($collections['categoryToPreservation']))
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
								foreach($collections['categoryToPreservation'] as $dryOrWetKey => $dryOrWetKeyVal)
								{
									if (in_array($prepType, $dryOrWetKeyVal))
									{
										$dryOrWet=$dryOrWetKey;
										break;
									}
								}
								if (is_null($dryOrWet))
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
			foreach( $this->collectionUnitEstimates as $key=>$val )
			{
				$this->collectionUnitEstimates[$key]['totalUnit_sum']=
					(int)$val['specimenUnit_sum_estimate'] + 
					(int)$val['storageRecordsWithoutIndividualCount_estimated_sum'] + 
					(int)$val['storageRecordsWithIndividualCount_sum'];

			}
			return $this->collectionUnitEstimates;
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
		
		public function addStaticNumbers( $key, $numbers )
		{
			if (!isset($this->collectionUnitEstimates[$key]))
			{
				$this->collectionUnitEstimates[$key] = [
					'specimenUnit_count' => isset($numbers['specimenUnit_count']) ? $numbers['specimenUnit_count'] : null,
					'specimenUnit_sum_estimate' => isset($numbers['specimenUnit_sum_estimate']) ? $numbers['specimenUnit_sum_estimate'] : null,
					'storageRecordsWithoutIndividualCount_number' => isset($numbers['storageRecordsWithoutIndividualCount_number']) ? $numbers['storageRecordsWithoutIndividualCount_number'] : null,
					'storageRecordsWithoutIndividualCount_estimated_sum' => isset($numbers['storageRecordsWithoutIndividualCount_estimated_sum']) ? $numbers['storageRecordsWithoutIndividualCount_estimated_sum'] : null,
					'storageRecordsWithIndividualCount_number' => isset($numbers['storageRecordsWithIndividualCount_number']) ? $numbers['storageRecordsWithIndividualCount_number'] : null,
					'storageRecordsWithIndividualCount_sum' => isset($numbers['storageRecordsWithIndividualCount_sum']) ? $numbers['storageRecordsWithIndividualCount_sum'] : null,
				];
			}
			else
			{
				$this->collectionUnitEstimates[$key]['specimenUnit_count'] += isset($numbers['specimenUnit_count']) ? $numbers['specimenUnit_count'] : 0;
				$this->collectionUnitEstimates[$key]['specimenUnit_sum_estimate'] += isset($numbers['specimenUnit_sum_estimate']) ? $numbers['specimenUnit_sum_estimate'] : 0;
				$this->collectionUnitEstimates[$key]['storageRecordsWithoutIndividualCount_number'] += isset($numbers['storageRecordsWithoutIndividualCount_number']) ? $numbers['storageRecordsWithoutIndividualCount_number'] : 0;
				$this->collectionUnitEstimates[$key]['storageRecordsWithoutIndividualCount_estimated_sum'] += isset($numbers['storageRecordsWithoutIndividualCount_estimated_sum']) ? $numbers['storageRecordsWithoutIndividualCount_estimated_sum'] : 0;
				$this->collectionUnitEstimates[$key]['storageRecordsWithIndividualCount_number'] += isset($numbers['storageRecordsWithIndividualCount_number']) ? $numbers['storageRecordsWithIndividualCount_number'] : 0;
				$this->collectionUnitEstimates[$key]['storageRecordsWithIndividualCount_sum'] += isset($numbers['storageRecordsWithIndividualCount_sum']) ? $numbers['storageRecordsWithIndividualCount_sum'] : 0;
			}
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
			
			$this->storage_docCountPerColl_withoutIndivCount= $storageDocCountPerCollWithoutIndivCount; 
			
		}
		
		private function normalizePrepTypes( $collectionBuckets,$collectionBucketsWithoutPrepTypes=null )
		{
			$storageDocCountPerPrepType=[];
			foreach( $collectionBuckets as $bucket )
			{
				$storageDocCountPerPrepType[$bucket['key']]=[];
				if (isset($bucket['prepTypes']))
				{
					$boxes=$drawers=0;
					foreach( $bucket['prepTypes']['buckets'] as $subBucket )
					{
						$b=strtolower($subBucket['key']);
						if (!isset($storageDocCountPerPrepType[$b]["box"]))
						{
							$storageDocCountPerPrepType[$bucket['key']][$b]=(int)$subBucket['doc_count'];
						}
						else
						{
							$storageDocCountPerPrepType[$bucket['key']][$b]+=(int)$subBucket['doc_count'];
						}
					}
						
				}
			}

			if (!is_null($collectionBucketsWithoutPrepTypes)) 
			{
				foreach( $collectionBucketsWithoutPrepTypes as $bucket )
				{
					if (!isset($storageDocCountPerPrepType[$bucket['key']])) $storageDocCountPerPrepType[$bucket['key']]=[];
					$storageDocCountPerPrepType[$bucket['key']]['_other']=(int)$bucket['doc_count'];
				}
			}
		
			return $storageDocCountPerPrepType; 
		}
	
		private function initTransformationObject()
		{
			$this->mapping2016ReportCategoryToCollection=[
				'botanie hoge planten' => [
					'mapping' => [ 'botany' ],
					'collectionEstimatesSpecimen' => [ '_other' => 1  ],
					'categoryToPreservation' => [ ],
				],
				'botanie lage planten' =>  [
					'mapping' => [ 'lagere planten' ],
					'collectionEstimatesStorageUnits' => [ '_other' => 40.30 ],
				],
				'entomologie' => [
					'mapping' => [ 'entomology','lepidoptera','hymenoptera','remaining insects','coleoptera','diptera','diptera0','orthopteroidea','odonata','hemiptera','entomologyhyj','collembola'],
					'collectionEstimatesStorageUnits' => [ 'drawer' => 145.98, 'jar' => 1031, 'box' => 83.42, '_other' => 218.73 ],
					'categoryToPreservation' => [
						'nat' => [ 'alcohol', 'alcohol 70%'  ], 
						'droog' => [ 'air dried', 'pinned specimen', 'microscopic slide', 'tube', 'embedded', 'envelope', 'embalmed', 'microscopic slide', 'bag', 'drawer' ]
					]
				],
				'vertebraten' => [
					'mapping' => [ 'vertebrates' ],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00  ],
					'categoryToPreservation' => [ 'droog' => [ 'loose bones' ] ]
				],
				'vertebraten zoogdieren' => [
					'mapping' => [ 'mammalia' ],
					'collectionEstimatesSpecimen' => [ 'droog' => 1.23, 'nat' => 1.20 ],
					'categoryToPreservation' => [
						'nat' => [ 'alcohol >70%', 'wet specimen', 'alcohol', 'alcohol 70%', 'alcohol 96%', 'formalin', 'glycerin' ],
						'droog' => [ 'loose bones', 'study skin', 'droog', 'mounted skin', 'mounted', 'microscopic slide', 'not applicable', 'air dried', 'flat skin', 'mummified specimen', 'box', 'semstub', 'mounted skeleton', 'skeletonized', 'full skeleton', 'skull and horns trophy', 'standard mount', 'trophy mount', 'glassine', 'skin', 'partly mounted', 'tube', 'card mounted' ],
						'_other' => [ 'unknown' ]
					]
				],
				'vertebraten reptielen en amfibieën' =>  [
					'mapping' => [ 'amphibia and reptilia' ],
					'collectionEstimatesSpecimen' => [ 'droog' => 1.18, 'nat' => 1.79 ],
					'categoryToPreservation' => [
						'nat'=> ['alcohol', 'formalin', 'alcohol 70%', 'glycerine', 'wet specimen', 'formalin 5%', 'alcohol-formaline' ],
						'droog' => [ 'droog', 'air dried', 'mounted skin', 'cast', 'loose bones', 'mounted skeleton' ],
						'_other' => [ 'alcohol & dry' ]
					]
				],
				'vertebraten vissen' => [
					'mapping' => [ 'pisces' ],
					'collectionEstimatesSpecimen' => [ 'droog' => 1.00, 'nat' => 1.00 ],
					'categoryToPreservation' => [ 'droog' => [ 'air dried' ] ]
				],
				'vertebraten vogels' => [
					'mapping' => [ 'aves' ],
					'collectionEstimatesSpecimen' => [ 'droog en alcohol' => 1.05, '_other' => 1.05, 'nesten' => 3.13 ],
					'categoryToPreservation' => [
						'nat' => [ 'alcohol', 'wet specimen', 'alcohol 96%' ],
						'droog' => [ 'air dried', 'study skin', 'mounted skin', 'skeletonized', 'microscopic slide', 'not applicable', 'loose bones', 'box', 'flat skin', 'mounted skeleton', 'mummified specimen', 'head', 'check nummer', 'kop', 'wing', 'cast', 'claws', 'skull', 'wings', 'tube', 'bill', 'case mount', 'head & leg', 'head & tail', 'mounted``', 'skin & win', 'tail', 'wing & tai' ]
					]
				],
				'invertebrates' => [
					'mapping' => [ 'invertebrates' ],
					'collectionEstimatesStorageUnits' => [ 'drawer' => 73.00, 'alcohol' => 6.96, '_other' => 1.00 ],
					'categoryToPreservation' => [ 'nat' => [ 'alcohol', 'alcohol 70%' ], 'droog' => [ 'microscopic slide', 'box', 'air dried' ] ]
				],
				'evertebraten overige collecties' => [
					'mapping' => [ 'crustacea','cnidaria','echinodermata','porifera','vermes','hydrozoa','chelicerata and myriapoda','tunicata','bryozoa','brachiopoda','foraminifera','protozoa'],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00 ],
					'collectionEstimatesSpecimen' => [ '_other' => 4,08 ],
					'categoryToPreservation' => [
						'nat' => [ 'alcohol', 'wet specimen', 'alcohol 70%', 'alcohol >70%', 'formol', 'formaline', 'formalin', 'prep. glycerine', 'pre. glyc.', 'prep. (glycerine)', 'prep. glyc.', 'prep.' ],
						'droog' => [ 'microscopic slide', 'not applicable', 'tube', 'air dried', 'jar', 'bag', 'box', 'semstub', 'box 558', 'jar', 'dried and pressed', 'microscopic slide', 'slide', 'hout-prep.', 'prep. hout', 'verwijsglaasje', 'fix. sublimaat', 'karton prep.', 'prep.hout' ],
						'_other' => [ 'unknown' ]
					]
				],
				'evertebraten mollusca' => [
					'mapping' => [ 'mollusca'],
					'collectionEstimatesStorageUnits' => [ 'slide drawer' => 26.71, 'alcohol' => 8.38, '_other' => 1.00 ],
					'collectionEstimatesSpecimen' => [ '_other' => 11.70 ],
					'categoryToPreservation' => [ 'nat' => [ 'alcohol 70%', 'alcohol 96%', 'formalin' ], 'droog' => [ 'air dried', 'not applicable', 'fossilized', 'microscopic slide', 'semstub' ] ]
				],
				'paleontologie' => [
					'mapping' => [ 'paleobotany','paleontology vertebrates','paleontology invertebrates','paleontology','macro vertebrates','micro vertebrates','mesozoic invertebrates','micropaleontology','cainozoic mollusca'],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00 ],
					'collectionEstimatesSpecimen' => [ '_other' => 12.64 ],
					'categoryToPreservation' => [ 'droog' => [ 'fossilized', 'not applicable', 'peel', 'fossilized', 'fossilized specimen', 'air dried', 'unknown', 'microscopic slide', 'fossilized specimen', 'box' ] ]
				],
				'mineralogie en petrologie' => [
					'mapping' => [  'mineralogy','petrology','mineralogy and petrology' ],
					'collectionEstimatesStorageUnits' => [ '_other' => 1.00 ],
					'collectionEstimatesSpecimen' => [ 'preparaten' => 1.00,'monsters' => 1.57,'nog te migreren' => 1.00, '_other' => 1.27 ],
					'categoryToPreservation' => [ 'droog' => [ 'thin section', 'not applicable', 'fossilized' ] ]
				]
			];
		}

	
	}
