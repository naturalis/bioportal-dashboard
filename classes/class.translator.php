<?php

	class translator {

		private $language;
		private $dictionary=[];
		
		public function __construct() 
		{
			$this->setDictionary();
		}
		
		public function setLanguage( $language )
		{
			$this->language=$language;
		}

		public function translate( $text )
		{
			if (isset($this->dictionary[$text]) && isset($this->dictionary[$text][$this->language]))
			{
				return $this->dictionary[$text][$this->language];
			}

			return $text;
		}

		private function setDictionary()
		{
			$this->dictionary=[
				'intro1' =>
					[
						'nl' => 'Dit dashboard geeft inzicht in de complete collectie van Naturalis. De hele collectie is in een of andere vorm gedigitaliseerd, en er loopt een continu proces om hier de metadata van alle specimens op objectniveau toe te voegen.',
						'en' => 'This dashboard is a window into the entire Naturalis collection. The complete collection has been digitzed in some form, and the process to add the meta-data of all specimens on object level is ongoing.'
					],
				'intro2' =>
					[
						'nl' => 'Het deel van de collectie dat al op objectniveau is gedigitaliseerd is toegevoegd aan de <a href="/api">Netherlands Biodiversity API</a> en is toegankelijk via het <a href="/over">BioPortal</a>. Deze geven ook toegang tot taxoninformatie uit de Catalogue of Life en het Nederlands Soortenregister.',
						'en' => 'The part of the collection that already had been digitzed on object level has been added to the <a href="/api">Netherlands Biodiversity API</a> and is accessible through the <a href="/over">BioPortal</a>. These also give access to taxon information from the Catalogue of Life and the Dutch Species Register.'

					],
				'intro3' => 
					[ 
						'nl' => 'Teneinde op dit dashboard een zo compleet mogelijk beeld te kunnen presenteren is een aantal aanvullende databronnen gebruikt voor sommige collectie-totalen',
						'en' => 'In order to present a more complete view on this dashboard, additional data sources have been used to present some totals for the entire collection.'
					],
				'intro4' =>
					[
						'nl' => 'Let op: op deze pagina wordt onderscheid gemaakt tussen het woord \'specimen\', dat refereert aan individuele specimenobjecten, en de term \'specimen record\', waarmee wordt gerefereerd aan een in de NBA geregistreerd specimendocument.',
						'en' => 'Please note the distinction between the use on this page of the word \'specimen\', which refers to individual specimen objects, and the term \'specimen record\', which refers to a specimen record registered in the NBA.'
					 ],
			
				'2D materiaal' => [ 'en' => '2D materials' ],
				'Breakdown of taxa per rank in the taxon index. The index does not contain individual records for taxa above species level.' => [ 'nl' => 'Verdeling van de taxa in de taxonindex in de verschillende rangen. De index bevat geen individuele records voor taxa boven soortsniveau.' ],
				'Collection categories by specimen count' => [ 'nl' => 'Collectiecatgeorieën en totaal aantal specimens' ],
				'Country top %s' => [ 'nl' => 'Landen top %s' ],
				'Entomologie' => ['en' => 'Entomology' ],
				'Evertebraten' => [ 'en' => 'Evertebrates' ],
				'full scientific names' => [ 'nl' => 'geaccepteerde wetenschappelijke namen' ],
				'Hogere planten' => [ 'en' => 'Higher plants' ],
				'Lagere planten' => [ 'en' => 'Lower plants' ],
				'Last import dates' => [ 'nl' => 'Laatste importdatum' ],
				'Mineralogie en petrologie' => [ 'en' => 'Mineralogy and petrology' ],
				'Most collected (sub)species' => [ 'nl' => 'Meest verzamelde (onder)soorten' ],
				'Multimedia count' => [ 'nl' => 'Totaal aantal multimedia records' ],
				'Name count' => [ 'nl' => 'Namen' ],
				'Number of registered specimen records per Dutch province.' => [ 'nl' => 'Aantal geregistreerde specimen records per provincie.' ],
				'Number of taxa per rank' => [ 'nl' => 'Taxonaantallen per rang' ],
				'Number of unique accepted names' => [ 'nl' => 'Unieke geaccepteerde wetenschappelijke namen' ],
				'Number of unique full scientific names registered as identification for NBA specimen records.' => [ 'nl' => 'Aantal unieke geaccepteerde wetenschappelijke namen geregistreerd als identificatie voor een specimen record in de NBA.'  ],
				'Number of unique names registered in the NBA taxon index.' => [ 'nl' => 'Unieke namen geregistreerd in de taxonindex van de NBA' ],
				'Number of unique synonyms' => [ 'nl' => 'Unieke synoniemen' ],
				'Number of unique vernacular names' => [ 'nl' => 'Unieke niet-wetenschappelijke namen' ],
				'Paleontologie' => [ 'en' => 'Paleontology '],
				'registered in the Netherlands Biodiversity API as %s specimen records and %s storage units.' => [ 'nl' => 'geregistreerd in de Netherlands Biodiversity API als %s specimen records en %s bewaareenheden.' ],
				'registered in the Netherlands Biodiversity API, consisting of  specimen images from the collection and taxon photo\'s from Dutch Species Register.' => [ 'nl' => 'geregistreerd in de Netherlands Biodiversity API, samengesteld uit specimenafbeeldingen uit de collectie en soortfoto\'s uit het Nederlands Soortenregister.' ],
				'registered in the Netherlands Biodiversity API, sourced from the Catalogue of Life and the Dutch Species Register.' => [ 'nl' => 'geregistreerd in de Netherlands Biodiversity API, afkomstig uit de Catalogue of Life en het Nederlands Soortenregister.' ],
				'Registered specimen records per country (without The Netherlands)' => [ 'nl' => 'Geregistreerde specimen records per land (zonder Nederland)' ],
				'Reptielen en amfibieën' => [ 'en' => 'Reptiles and amphibians' ],
				'Specimen count' => [ 'nl' => 'Totaal aantal specimen' ],
				'Specimens per Dutch province' => [ 'nl' => 'Specimen per provincie' ],
				'Specimen records per Dutch province' => [ 'nl' => 'Specimen records per provincie' ],
				'Taxon count' => [ 'nl' => 'Totaal aantal taxa' ],
				'The %s top-most sub-collections in terms of the total number of specimens with a type status,<br />plus the %s most frequently occurring type statuses in that sub-collection.' => [ 'nl' => 'De %s grootste deelcollecties gemeten naar het aantal specimen records met een typestatus,<br />plus de %s meest voorkomende typestatussen in iedere deelcollectie.' ],
				'Top %s collectors' => [ 'nl' => 'Top %s verzamelaars' ],
				'Top %s collectors having the most specimen records registered to their name, plus the collections they\'ve contributed to.' => [ 'nl' => 'Top %s verzamelaars met de meeste specimen geregistreerde specimen records, plus de collecties waaraan ze hebben bijgedragen.' ],
				'%s registered specimen records in %s collection%s' => [ 'nl' => '%s geregistreerde specimen records in %s collectie%s' ],
				'Top %s of the most collected species or subspecies, measured by number of registered specimen records.' => [ 'nl' => 'Top %s meest verzamelde soorten of ondersoorten, op basis van de hoeveelheid geregistreerde specimen records.' ],
				'total' => [ 'nl' => 'totaal' ],
				'Type status records per collection' => [ 'nl' => 'Typestatus-records per collectie' ],
				'Unique scientific names with specimens' => [ 'nl' => 'Unieke geaccepteerde wetenschappelijke namen met specimens' ],
				'Vissen' => [ 'en' => 'Fish' ],
				'Vogels' => [ 'en' => 'Birds' ],
				'Zoogdieren' => [ 'en' => 'Mammals' ],
				'Botany' => [ 'nl' => 'Botanie' ],
				'Chelicerata and Myriapoda' => [ 'nl' => 'Chelicerata en Myriapoda' ],
				'Paleontology Invertebrates' => [ 'nl' => 'Paleontologie invertebraten' ],
			];
		}
		
	}
