<?php

	class contentBlocks {

		private $tpl;
		private $blocks;
		
		public function __construct()
		{
			$this->tpl=new stdClass;
			$this->initTemplates();
		}

		private function initTemplates()
		{
			$this->tpl->rowBlock = <<<HTML
<div class="row-block">
	%CELLS%
</div>
HTML;

			$this->tpl->cell = <<<HTML
<div class="block-cell %CELL-CLASS%">
	<div class="block-header %HEADER-CONTENT-CLASS%">
		%HEADER-CONTENT%
	</div>
	<div class="block-content %MAIN-CONTENT-CLASS%">
		%MAIN-CONTENT%
	</div>
	<div class="block-subscript %SUBSCRIPT-CONTENT-CLASS%">
		%SUBSCRIPT-CONTENT%
	</div>
	<div class="block-info %INFO-CONTENT-CLASS%">
		%INFO-CONTENT%
	</div>
</div>
HTML;
		}
		
		public function makeBlock( $classes, $content )
		{
			$this->blocks[]=$this->getBlock( $classes, $content );
		}
		
		private function substElement( $elements, $element )
		{
			return (isset($elements[$element]) ? $elements[$element] : "" );
		}

		public function getBlock( $classes, $content )
		{
			$b=$this->tpl->cell;
			
			$b=str_replace('%CELL-CLASS%',$this->substElement($classes,"cell"),$b);
			$b=str_replace('%HEADER-CONTENT-CLASS%',$this->substElement($classes,"title"),$b);
			$b=str_replace('%MAIN-CONTENT-CLASS%',$this->substElement($classes,"main"),$b);
			$b=str_replace('%SUBSCRIPT-CONTENT-CLASS%',$this->substElement($classes,"subscript"),$b);
			$b=str_replace('%INFO-CONTENT-CLASS%',$this->substElement($classes,"info"),$b);
			
			$b=str_replace('%HEADER-CONTENT%',$this->substElement($content,"title"),$b);
			$b=str_replace('%MAIN-CONTENT%',$this->substElement($content,"main"),$b);
			$b=str_replace('%SUBSCRIPT-CONTENT%',$this->substElement($content,"subscript"),$b);
			$b=str_replace('%INFO-CONTENT%',$this->substElement($content,"info"),$b);

			return $b;
		}
		
		public function getBlockRow()
		{
			$b=$this->tpl->rowBlock;
			$b=str_replace('%CELLS%',implode("\n",$this->blocks),$b);
			$this->blocks=[];
			return $b;		
		}

	}

