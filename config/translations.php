<?php

	$translations['en']=
		[
			'Entomologie'=>'Entomology',
			'Paleontologie'=>'Paleontology',
			'Hogere planten'=>'Higher plants',
			'Evertebraten'=>'Evertebrates',
			'2D materiaal'=>'2D materials',
			'Mineralogie en petrologie'=>'Mineralogy and petrology',
			'Lagere planten'=>'Lower plants',
			'Vogels'=>'Birds',
			'Vissen'=>'Fish',
			'Reptielen en amfibieën'=>'Reptiles and amphibians',
			'Zoogdieren'=>'Mammals',
		];

	function __( $text, $language='en' )
	{
		global $translations;
		
		if (isset($translations[$language]) && isset($translations[$language][$text]))
		{
			return $translations[$language][$text];
		}

		return $text;
	}
