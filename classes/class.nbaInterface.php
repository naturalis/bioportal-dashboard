<?php

	class nbaInterface {
		
		private $nbaAddress;
		private $queryQueue=[];

		const MAX_PARALLEL_CURL = 100;
		
		public function setNbaAddress( $nbaAddress )
		{
			$this->nbaAddress=rtrim($nbaAddress,"/") . "/";
		}

		public function registerQuery( $p )
		{
			$label = isset($p["label"]) ? $p["label"] : false;
			$path = isset($p["path"]) ? $p["path"] : false;
			$query = isset($p["query"]) ? $p["query"] : false;
			$field = isset($p["field"]) ? $p["field"] : '_querySpec';
			$method = isset($p["method"]) ? $p["method"] : 'GET';

			if (!$label) throw new Exception("need a query label", 1);
			if (!$path) throw new Exception("need a query path", 1);

			$t = [
				'label' => $label,
				'path' => $path,
				'field' => $field,
				'request' => $this->nbaAddress . ltrim($path, "/"),
				'method' => $method
			];

			if ($query)	$t['query'] = $query;

			$this->queryQueue[] = $t;
		}

		public function getQueryQueue()
		{
			return $this->queryQueue;
		}

		public function getQueryResults()
		{
			$r=[];

			foreach((array)$this->getQueryQueue() as $key=>$val)
			{
				$t=[ 'label' => $val['label'] ];
				if (isset($val['error'])) $t['error'] = $val['error'];
				if (isset($val['response_decoded'])) $t['result'] = $val['response_decoded'];
				$r[ $val['label'] ]=$t;
			}			
			return $r;
		}

		public function resetQueryQueue()
		{
			$this->queryQueue=[];
		}

		public function processQueries()
		{
			try
			{
				$this->doChecks();
				$this->runRequests();
				$this->parseResults();
			} 
			catch (Exception $e)
			{
				echo 'caught exception: ',  $e->getMessage(), "\n";
				echo 'exiting';
			}		
		}

		private function runRequests()
		{
			$queue=[];

			foreach((array)$this->getQueryQueue() as $key=>$val)
			{
				$queue[$key]=$val;
				if (count($queue) >= self::MAX_PARALLEL_CURL)
				{
					$this->runPartialQueue($queue);
					$queue=[];
				}
			}

			$this->runPartialQueue($queue);
		}

		private function runPartialQueue( $queue )
		{

			foreach((array)$queue as $key=>$val)
			{
				$this->queryQueue[$key]["cUrlHandle"]=curl_init();
				
				curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_RETURNTRANSFER, true);
				curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_TIMEOUT, 10);

				if ($val["method"]=="GET")
				{
					curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_POST, false);
					curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_URL, $val["request"] . "?". $val["field"] ."=" . rawurlencode ($val["query"]));
				}
				else
				{
					curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_POST, true);
					curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_URL,$val["request"]);
					//curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_HTTPHEADER, [ "content-type: application/x-www-form-urlencoded; charset=UTF-8" ]);
					curl_setopt($this->queryQueue[$key]["cUrlHandle"], CURLOPT_POSTFIELDS,[ $val["field"] => $val["query"] ] );
				}
			}

			$mh = curl_multi_init();
			foreach((array)$this->getQueryQueue() as $key=>$val)
			{
				if (isset($val["cUrlHandle"]))
				{
					curl_multi_add_handle($mh,$val["cUrlHandle"]);
				}
			}
			
			$running = null;

			do {
				$status = curl_multi_exec($mh, $running);
				if($status > 0)
				{
					echo "CURL error: " . curl_multi_strerror($status);
				}
			} while ($running);

			foreach((array)$this->getQueryQueue() as $key=>$val)
			{
				if (isset($val["cUrlHandle"]))
				{
					$this->queryQueue[$key]["response"]=curl_multi_getcontent($val["cUrlHandle"]);
					curl_multi_remove_handle($mh, $val["cUrlHandle"]);					
				}
			}

			curl_multi_close($mh);
		}

		private function parseResults()
		{
			foreach((array)$this->getQueryQueue() as $key=>$val)
			{
				if (empty($val['response']))
				{
					$this->queryQueue[$key]['error']= 'empty response';
				}
				else
				{
					$r=json_decode($val['response'],true);

					if (json_last_error() != JSON_ERROR_NONE)
					{
						$this->queryQueue[$key]['error']= 'invalid json: ' . $this->translateJSONerror( json_last_error() );
					}
					else
					{
						$this->queryQueue[$key]['response_decoded']=$r;
			
						if (isset($r['exception']))
						{
							$this->queryQueue[$key]['error']=sprintf('nba error: %s (%s)',$r['exception']['message'],$r['exception']['type']);
							unset($this->queryQueue[$key]['response_decoded']);
						}						
					}
				}
			}
		}

		private function translateJSONerror( $error )
		{
			$errors=[
				JSON_ERROR_NONE => 'No error has occurred',
				JSON_ERROR_DEPTH => 'The maximum stack depth has been exceeded',
				JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
				JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
				JSON_ERROR_SYNTAX => 'Syntax error',
				JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
				JSON_ERROR_RECURSION => 'One or more recursive references in the value to be encoded',
				JSON_ERROR_INF_OR_NAN => 'One or more NAN or INF values in the value to be encoded',
				JSON_ERROR_UNSUPPORTED_TYPE => 'A value of a type that cannot be encoded was given',
			];
			
			if ( defined("JSON_ERROR_INVALID_PROPERTY_NAME" ) )
				$errors[JSON_ERROR_INVALID_PROPERTY_NAME] = 'A property name that cannot be encoded was given';

			if ( defined("JSON_ERROR_UTF16" ) )
				$errors[JSON_ERROR_UTF16] = 'Malformed UTF-16 characters, possibly incorrectly encoded';
			
			$genericErrorMessage = "Unknown JSON error (error code: %s)";
			
			return isset($errors[$error]) ? $errors[$error] : sprintf($genericErrorMessage,$error);
		}

		private function doChecks()
		{
			if (empty($this->nbaAddress))  throw new Exception('no server set');
			if (empty($this->getQueryQueue())) throw new Exception('no queries set');
		}

    }