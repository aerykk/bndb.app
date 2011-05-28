<?php

	class database_query
	{

	}

	class database_result
	{

	}

	class database
	{
		public function __construct($db)
		{
			$this->data = array();

			foreach($db as $type => $value)
				foreach($value as $name => $info)
					$this->add($type, $name, $info);
		}
		
		public function add_database($db)
		{
			foreach($value as $name => $info)
				$this->add($type, $name, $info);
		}
		
		public function add_databases($db)
		{
			foreach($db as $type => $value)
				foreach($value as $name => $info)
					$this->add($type, $name, $info);
		}
		
		public function close()
		{
			foreach($this->data as &$type)
				foreach($type as &$db)
					if(is_resource($db['link']))
						mysql_close($db['link']);
		}
		
		public function add($type, $name, $data)
		{
			if(!array_key_exists($type, $this->data))
				$this->data[$type] = array();
			
			$this->data[$type][$name] = $data;
			$this->data[$type][$name]['link'] = null;
			
			$this->set_active($type, $name);
		}
		
		public function remove($type)
		{
			if(isset($this->data[$type]))
				unset($this->data[$type]);
		}
		
		public function set_active($type, $name)
		{
			$this->data[$type]['active'] = &$this->data[$type][$name];

			// update the active db's table prefix
			$this->prefix = $this->data[$type][$name]['prefix'];
		}
		
		public function build_query($query, $type = "master")
		{
			// grab the current "active" DB for this type
			$db = &$this->data[$type]['active'];
			
			if(is_array($query))
			{
				$q = "";
				
				foreach($query as $k => $v)
				{
					$k = strtoupper($k);
					
					switch($k)
					{
						case "SELECT":
							$q .= "SELECT " . implode(', ', $v) . " ";
						break;
						
						case "FROM":
							$q .= "FROM ";
							
							$t = array();
							
							foreach($v as $table)
								$t[] = $db['prefix'] . $table;
							
							$q .= implode(', ', $t) . " ";
						break;
						
						case "WHERE":
							$q .= "WHERE ";
							
							$t = array();
							
							foreach($v as $field => $value)
							{
								if(is_string($value))
									$t[] = $field . " = '" . $value . "'";
								else if(is_numeric($value))
									$t[] = $field . " = " . $value;
								else if(is_array($value))
									$t[] = $field . " = '" . $this->build_query($value) . "'";
								else if($value === null)
									$t[] = $field . " IS NULL";
							}
							
							$q .= implode(', ', $t) . " ";
						break;
						
						case "LIMIT":
							$q .= "LIMIT " . $v;
						break;
						
						case "DELETE":
							
						break;
						
						case "UPDATE":
						
						break;
						
						case "ORDER BY":
							$q .= "ORDER BY ";
							
							$t = array();
							
							foreach($v as $field => $value)
								$t[] = $field . " " . $value;
							
							$q .= implode(', ', $t) . " ";
						break;
					}
				}

				return $q;
			}
			else if(is_string($query))
			{
				return $query;
			}
		}
		
		public function fetch_rows($query, $type = "master", $num = MYSQL_ASSOC)
		{
			$db = $this->connect($type);
			
			$query = $this->build_query($query, $type);
			
	  		$rows = array();
	  		
	  		if($result = mysql_query($query, $db['link']) or die(mysql_error()))
				while($row = mysql_fetch_array($result, $num))
					$rows[] = $row;
	  		
	  		mysql_free_result($result);
	  		
	  		return $rows;
		}
		
		public function fetch_row($query, $type = "master", $num = MYSQL_ASSOC)
		{
			$db = $this->connect($type);
			
			$query = $this->build_query($query, $type);
			
			$result = mysql_query($query, $db['link']) or die(mysql_error());
			
	  		$row = mysql_fetch_array($result, $num);
	  		
	  		mysql_free_result($result);
	    	
	  		return $row;
		}
		
		public function fetch_array($result, $type = "master", $num = MYSQL_ASSOC)
		{
			$db = $this->connect($type);
			
	  		$row = mysql_fetch_array($result, $num);
	    	
	  		return $row;
		}
		
		public function query($query, $type = "master", $errors = true)
		{
			$db = $this->connect($type);
			
			$query = $this->build_query($query, $type);
			
			if($errors)
			{
				$result = @mysql_query($query, $db['link']) or die(mysql_error());
				
				if(!$result)
				{
					throw new Exception("Failed to query database.", 100);
				}
			}
			else 
				$result = @mysql_query($query, $db['link']);
			
			return $result;
		}
		
		public function is_affected($type = "master")
		{
			$db = $this->connect($type);
			
			return mysql_affected_rows($db['link']);
		}
		
		public function connect($type = "master")
		{
			// grab the current "active" DB for this type
			$db = &$this->data[$type]['active'];
			
			// check if the DB is a resource and we're able to connect
			if($this->is_active($db))
			{
				return $db;
			}
			else
			{
				// if the DB is still a resource, close it to before overwriting it with a new connection
				if(is_resource($db['link']))
					mysql_close($db['link']);
				
				// attempt connecting to the DB currently set to active
				$db['link'] = mysql_pconnect($db['host'], $db['user'], $db['pass']);
				
				if($db['link'])
				{
					mysql_select_db($db['db'], $db['link']) or die("Cannot select: " . mysql_error());
					
					return $db;
				}
				else
				{
					// loop through every DB in the data for a connection
					foreach($this->data[$type] as $name => &$db)
					{
						$db['link'] = mysql_pconnect($db['host'], $db['user'], $db['pass']);
						
						if($db['link'])
						{
							mysql_select_db($db['db'], $db['link']) or die("Cannot select: " . mysql_error());
							
							// set the active DB for this type to the newly connected DB
							$this->set_active($type, $name);
							
							return $db;
						}
						else
						{
							// if the DB is still a resource, close it to avoid too many connection errors
							if(is_resource($db['link']))
								mysql_close($db['link']);
						}
						
						sleep(5);
					}
				}
			}
			
			// check if the DB is a resource and we're able to connect
			if($this->is_active($db))
				return $db;
			else 
				// serious error 1) Incorrect database information. 2) Server down. 3) Too many connections, try flushing MySQL.
				throw new Exception("Could not connect to any database. Type: " . $type, 69);
		}
		
		public function is_active(&$db)
		{
			if(!is_resource($db['link']) || !mysql_ping($db['link']))
				return false;
			else
				return true;
		}

		private $data;
		public $prefix;
		public $settings;
	}

?>