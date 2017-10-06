<?php

	/*
		include_once("class.data-cache.php");

		$cache=new dataCache;
		$cache->setDbParams( "localhost", "root", "pass", "cache" );
		$cache->setProject( "nba_dashboard" );
		$cache->storeData( $data );
		$data = $cache->getData();		
	*/

	class dataCache {

		private $db;
		private $project;
		private $field;
		private $data=[];
		private $createQuery="create table if not exists data_cache (project varchar(32) not null,field varchar(255) not null,data longblob,created timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY (`project`,`field`)) CHARACTER SET utf8 ENGINE = MYISAM";
		private $saveQuery="insert into data_cache (`project`,`field`,`data`) values ('%s','%s','%s') on duplicate key update `data` = '%s'";
		private $selectQuery="select * from data_cache where `project` = '%s' and `field` = '%s'";
		private $selectAllQuery="select * from data_cache where `project` = '%s'";
		private $deleteAllQuery="delete from data_cache where `project` = '%s'";

		public function __construct() 
		{
			$this->db = new stdClass;
		}
		
		public function setDbParams( $hostOrObject, $user=null, $password=null, $database=null )
		{
			if ( is_object($hostOrObject) )
			{
				$this->_setDbParams( $hostOrObject->host, $hostOrObject->user, $hostOrObject->password, $hostOrObject->database );
			}
			else
			if ( is_array($hostOrObject) )
			{
				$this->_setDbParams( $hostOrObject['host'], $hostOrObject['user'], $hostOrObject['password'], $hostOrObject['database'] );
			}
			else
			{
				$this->_setDbParams( $hostOrObject, $user, $password, $database );
			}
		}
		
		public function setProject( $project )
		{
			$this->project=$project;
		}

		public function getProject()
		{
			return $this->project;
		}

		public function storeData( $data ) 
		{
			$this->init();
			$this->connectDb();
			$this->createTable();

			foreach( $data as $key => $data )
			{
				$this->saveRecord( $key, $data );
			}
		}

		public function getData( $field=null )
		{
			$this->init();
			$this->connectDb();
			$this->createTable();
			$this->setField( $field );
			$this->selectRecords();
			$this->unserializeData();
			return $this->getRecords();
		}

		public function setField( $field )
		{
			$this->field=$field;
		}

		public function getField()
		{
			return $this->field;
		}
		
		public function emptyCache()
		{
			$this->init();
			$this->connectDb();
			$this->deleteAllRecords();
		}

		private function init()
		{
			!is_null($this->getProject()) || die("no project");
			(isset($this->db->host) && isset($this->db->user) && isset($this->db->password) && isset($this->db->database) ) || die("incomplete database settings");
		}
		
		private function _setDbParams( $host, $user, $password, $database )
		{
			$this->db->host=$host;
			$this->db->user=$user;
			$this->db->password=$password;
			$this->db->database=$database;
		}
		
		private function connectDb()
		{
			if (!isset($this->db->connection))
			{
				$this->db->connection = mysqli_connect($this->db->host,$this->db->user,$this->db->password,$this->db->database);
			}
		}

		private function saveRecord( $key, $data )
		{
			$project=mysqli_real_escape_string($this->db->connection,$this->getProject());
			$key=mysqli_real_escape_string($this->db->connection,$key);
			$data=mysqli_real_escape_string($this->db->connection,serialize($data));
			$query=sprintf($this->saveQuery,$project,$key,$data,$data);
			if (!mysqli_query($this->db->connection,$query))
			{
				printf("error: %s", mysqli_sqlstate($this->db->connection));
			}
		}

		private function createTable()
		{
			$query=sprintf($this->createQuery);
			if (!mysqli_query($this->db->connection,$query))
			{
				printf("error: %s", mysqli_sqlstate($this->db->connection));
			}
		}

		private function selectRecords()
		{
			$project=mysqli_real_escape_string($this->db->connection,$this->getProject());
			if ( !is_null($this->getField()) )
			{
				$key=mysqli_real_escape_string($this->db->connection,$this->getField());
				$query=sprintf($this->selectQuery,$project,$key);
			}
			else
			{
				$query=sprintf($this->selectAllQuery,$project);
			}

			$result=mysqli_query($this->db->connection,$query);
			if($result)
			{
				while ($row = $result->fetch_object())
				{
					$this->data[]=$row;
				}
				$result->close();
			}
			else
			{
				printf("error: %s", mysqli_sqlstate($this->db->connection));
			}
		}
		
		private function unserializeData()
		{
			foreach($this->data as $key=>$val)
			{
				if (isset($val->data))
				{
					$this->data[$key]->data=unserialize($val->data);
				}
			}
		}

		private function getRecords()
		{
			$d = new stdClass();
			foreach($this->data as $key=>$val)
			{
				$d->{$val->field}=$val->data;
			}
			return $d;
		}

		private function deleteAllRecords()
		{
			$project=mysqli_real_escape_string($this->db->connection,$this->getProject());
			$query=sprintf($this->deleteAllQuery,$project);
			mysqli_query($this->db->connection,$query);
		}

	}
