<?php

	/*
		$w = new webPageStealer;

		$w->setUrl( "http://145.136.242.15/datasets" );
		$w->stealPage();
		$code=$w->getCurlInfo( "http_code" );
		$element=$w->getPageElementById( "top-bar-wrapper" );
		$elements=$w->getPageElementsByTag( "div" );

		$w->replaceElementByXPath( "//div[@class='large-12 main columns']", ['element'=>'div', 'content'=>'wtf?','attributes'=>['id'=>'new_div']] );
		echo $w->getNewPage();
		
	*/

	class webPageStealer {
		
		private $url;
		private $headers;
		private $postVars;
		private $cUrlHandle;
		private $rawPage;
		private $newPage;
		private $DOMDocument;
		private $cUrlError;
		private $cUrlErrorMsg;
		private $cUrlInfo;
			
		public function stealPage()
		{
			try
			{
				$this->initialize();
				$this->initializeCurl();
				$this->goSteal();
				$this->parsePage();
			} 
			catch (Exception $e)
			{
				echo "caught exception:  " . $e->getMessage();
			}
		}
		
		public function setUrl( $url )
		{
			$this->url=$url;
		}
		
		public function getUrl()
		{
			return $this->url;
		}

		public function setHeaders( $headers )
		{
			// array( "content-type: application/x-www-form-urlencoded; charset=UTF-8", ... )
			if (is_array($headers)) $this->headers=$headers;
		}
		
		public function getHeaders()
		{
			return $this->headers;
		}
		
		public function setPostVars( $postVars )
		{
			// array( fieldname => value, fieldname => value, ... )
			if (is_array($postVars)) $this->postVars=$postVars;
		}
		
		public function getPostVars()
		{
			return $this->postVars;
		}

		public function getCurlError()
		{
			return [ "error" => $this->cUrlError, "message" => $this->cUrlErrorMsg ];
		}

		public function getCurlInfo( $var )
		{
			/*
				// http://php.net/manual/en/function.curl-getinfo.php
				url
				content_type
				http_code
				header_size
				request_size
				filetime
				ssl_verify_result
				redirect_count
				total_time
				namelookup_time
				connect_time
				pretransfer_time
				size_upload
				size_download
				speed_download
				speed_upload
				download_content_length
				upload_content_length
				starttransfer_time
				redirect_time
				certinfo
				primary_ip
				primary_port
				local_ip
				local_port
				redirect_url
			*/
			return isset( $this->cUrlInfo[$var] ) ? $this->cUrlInfo[$var] : null;
		}

		public function setPage( $page )
		{
			$this->rawPage=$page;
		}
		
		public function setNewPage( $page )
		{
			$this->newPage=$page;
		}
		
		public function getPage()
		{
			return $this->rawPage;
		}

		public function getNewPage()
		{
			return $this->newPage;
		}

		public function getPageElementById( $id )
		{
			return $this->DOMDocument->saveHTML( $this->DOMDocument->getElementById( $id ) );
		}

		public function replaceElementById( $id, $replacement=null )
		{
			$oldNode = $this->DOMDocument->getElementById( $id );
			$this->replaceElement( $oldNode, $replacement);
		}
		
		//$w->replaceElementByXPath( "//div[@class='large-12 main columns']", ['element'=>'div', 'content'=>'hi!','attributes'=>['id'=>'new_div']] );
		// for large swaths of preformatted html, use (attributes will be ignored):
		//$w->replaceElementByXPath( "//div[@class='large-12 main columns']", ["element"=>"div", "attributes"=>["id"=>"dashboard_data"], "content" => $buffer, "html" => true ] );
		public function replaceElementByXPath( $path, $replacement=null )
		{
			$xp = new DOMXPath($this->DOMDocument);
			$oldNode = $xp->query($path)->item(0);	
			$this->replaceElement( $oldNode, $replacement );
		}
		
		//$w->replaceElementsByTag( "title", ['element'=>'title', 'content'=>'hi!']);
		public function replaceElementsByTag( $tag, $replacement=null )
		{
			$oldNodes = $this->DOMDocument->getElementsByTagName ( $tag );
			foreach ($oldNodes as $oldNode) 
			{
				$this->replaceElement( $oldNode, $replacement );
			}
		}
		public function getPageElementsByTag( $tag )
		{
			$b=[];
			foreach( $this->DOMDocument->getElementsByTagName( $tag ) as $element )
			{
				$b[]=$this->DOMDocument->saveHTML( $element );
			};
			return $b;
		}

		private function initialize()
		{
			if (empty($this->getUrl())) throw new Exception( "no URL set" );
			if (!filter_var($this->getUrl(), FILTER_VALIDATE_URL)) throw new Exception( "illegal URL" );
			libxml_use_internal_errors(true);
		}
		
		private function initializeCurl()
		{
			$this->cUrlHandle=curl_init();
			
			curl_setopt($this->cUrlHandle, CURLOPT_URL,$this->getUrl());
			curl_setopt($this->cUrlHandle, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($this->cUrlHandle, CURLOPT_TIMEOUT, 10);

			if (!empty($this->getPostVars()))
			{
				curl_setopt($this->cUrlHandle, CURLOPT_POST, true);
				curl_setopt($this->cUrlHandle, CURLOPT_POSTFIELDS, $this->getPostVars());
			}
			if (!empty($this->getHeaders()))
			{
				curl_setopt($this->cUrlHandle, CURLOPT_HTTPHEADER, $this->getHeaders());
			}
		}

		private function goSteal()
		{
			$this->setPage( curl_exec( $this->cUrlHandle ) );
			$this->setNewPage( $this->getPage() );
			$this->cUrlError=curl_errno( $this->cUrlHandle );
			$this->cUrlErrorMsg=curl_error( $this->cUrlHandle );
			$this->cUrlInfo=curl_getinfo( $this->cUrlHandle );
			
			if (!empty($this->cUrlError)) throw new Exception( $this->cUrlErrorMsg );
		}

		private function parsePage()
		{
			$this->DOMDocument = new DOMDocument();
			$this->DOMDocument->loadHTML( $this->rawPage );
		}

		private function replaceElement( $element, $replacement )
		{
			if ( isset($replacement["html"]) && $replacement["html"]==true )
			{
				$tmpDoc = new DOMDocument();
				$tmpDoc->loadHTML( $replacement["content"] );
				$newNode = $this->DOMDocument->createElement( "span" );		
				$node = $this->DOMDocument->importNode( $tmpDoc->getElementsByTagName('*')->item(0), true );
				$newNode->appendChild($node);
			}
			else
			{
				$newNode = $this->DOMDocument->createElement(
					isset($replacement["element"]) ? $replacement["element"] : "span", 
					isset($replacement["content"]) ? $replacement["content"] : "" 
				);

				if ( isset($replacement["attributes"]) )
				{
					foreach((array)$replacement["attributes"] as $attribute=>$value)
					{
						$newNode->setAttribute($attribute,$value);
					}
				}
				
			}

			if ($element->parentNode)
			{
				$element->parentNode->replaceChild($newNode, $element);	
				$this->setNewPage( $this->DOMDocument->saveHTML($this->DOMDocument->documentElement) );
			}
		}
	}
