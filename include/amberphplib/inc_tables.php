<?php
/*
	tables.php

	Provides classes/functions used for generating all the tables and forms
	used.

	Some of the handy features it provides:
	* Ability to select/unselect columns to display on tables
	* Lookups of column names against word database allows for different language translations.
	* CSV export function

	Class provided is "table"

*/

class table
{
	var $tablename;			// name of the table - used for internal purposes, not displayed
	var $language = "en_us";	// language to use for the form labels.
	
	var $columns;			// array containing the list of all the columns to display
	var $columns_order;		// array containing columns to order by

	var $total_columns;		// array of columns to create totals for
	var $total_rows;		// array of columns to create per-row totals for

	var $links;			// array of links to place in a final column

	var $structure;			// contains the structure of all the defined columns.
	var $filter = array();		// structure of the filtering
	var $option = array();		// fixed options to add to the option form

	var $data;			// table content
	var $data_num_rows;		// number of rows

	var $sql_obj;			// object used for SQL string, queries and data
	

	var $render_columns;		// human readable column names


	/*
		table()

		Constructor Function
	*/
	function table()
	{
		// init the SQL structure
		$this->sql_obj = New sql_query;
	}


	/*
		add_column($type, $name, $dbname)
	
		Defines the column structure.
		type	- A known type of column
				standard
				date		- YYYY-MM-DD format date field
				timestamp	- UNIX style timestamp field
				price		- displays a price field correctly
				hourmins	- input is a number of seconds, display as H:MM
				
		name	- name/label of the column for display purposes

		dbname	- name of the field in the DB or session data to use for the input data (optional)
	*/
	function add_column($type, $name, $dbname)
	{
		log_debug("table", "Executing add_column($type, $name, $dbname)");

		$this->structure[$name]["type"]		= $type;
		$this->structure[$name]["dbname"]	= $dbname;
	}


	/*
		add_link($name, $page, $options_array)

		Adds a new link to the links array, with "name" becomming the link after undergoing
		translation. Note that $page is equal to the page to display, you don't need to define
		"index.php?page=" or anything.
		
		$options_array is used to specifiy get values, and has the following structure:
		$options_array["get_field_name"]["value"]	= "value";
		$options_array["get_field_name"]["column"]	= "columnname";

		If the value option is specified, a GET field will be added with the specified value,
		otherwise if the column option is

		
	*/
	function add_link($name, $page, $options_array)
	{
		$this->links[$name]["page"]	= $page;
		$this->links[$name]["options"]	= $options_array;
	}


	/*
		add_filter($option_array)

		Allows the specification of filter options, which display fields such as input boxes
		or dropdowns for search or filtering purposes.

		The input to these options is then used to form SQL WHERE queries.

		The structure for the $option_array is the same as for add_input for the form_input class
		- see the form::render_field function for structure definition - with one addition:
		
			$option_array["sql"] = "QUERY";
			
			Where QUERY can be any SQL statment that goes after WHERE, with the word "value"
			being a variable that gets replaced by the input in this option field.

			eg:
			$option_array["sql"] = "date > 'value'";

			

	*/
	function add_filter($option_array)
	{
		log_debug("table", "Executing add_filter(option_array)");

		// we append "filter_" to fieldname, to prevent the chance of the filter field
		// having the same name as one of the column fields and breaking stuff.
		$option_array["fieldname"] = "filter_" . $option_array["fieldname"];
		
		$this->filter[ $option_array["fieldname"] ] = $option_array;
	}


	/*
		add_fixed_option($fieldname, $value)

		Adds a fixed hidden form input to the option form - for stuff like specifiy the ID of
		an object, etc.
	*/
	function add_fixed_option($fieldname, $value)
	{
		log_debug("table", "Executing add_fixed_option($fieldname, $value)");

		$this->option[$fieldname] = $value;
	}


	/*
		custom_column_label($column, $label)

		Instead of doing a translate, the render functions will load the label from the data
		inputted by this function
	*/
	function custom_column_label($column, $label)
	{
		log_debug("table", "Executing custom_column_label($column, $label)");
		
		$this->structure[$column]["custom"]["label"] = $label;
	}


	/*
		custom_column_link($column, $link)

		Create the column label into a hyper link to the specified link.
	*/
	function custom_column_link($column, $link)
	{
		log_debug("table", "Executing custom_column_link($column, $link)");
		
		$this->structure[$column]["custom"]["link"] = $link;
	}
	


	/*
		generate_sql()

		This function automatically builds the SQL query structure using the options
		and columns that the user has chosen.

		It then used the sql_query class to produce an SQL query string, which can be used
		by the load_data_sql() function.
	*/
	function generate_sql()
	{
		log_debug("table", "Executing generate_sql");


		// run through all the columns, and add their fields to the SQL structure
		foreach ($this->columns as $column)
		{
			$this->sql_obj->prepare_sql_addfield($column, $this->structure[$column]["dbname"]);
		}

		// generate WHERE filters if any exist
		if ($this->filter)
		{
			foreach (array_keys($this->filter) as $fieldname)
			{
				// note: we only add the filter if a value has been saved to default value, otherwise
				// we assume the SQL could break.
				if ($this->filter[$fieldname]["defaultvalue"])
				{
					$query = str_replace("value", $this->filter[$fieldname]["defaultvalue"], $this->filter[$fieldname]["sql"]);
					$this->sql_obj->prepare_sql_addwhere($query);
				}
			}
		}

		// generate order by rules
		if ($this->columns_order)
		{
			foreach ($this->columns_order as $column_order)
			{
				if ($this->structure[$column_order]["dbname"])
				{
					$this->sql_obj->prepare_sql_addorderby($this->structure[$column_order]["dbname"]);
				}
				else
				{
					$this->sql_obj->prepare_sql_addorderby($column_order);
				}
			}
		}

		// produce SQL statement
		$this->sql_obj->generate_sql();
		
		return 1;
	}



	

	/*
		load_data_sql()
		
		This function executes the SQL statement and fetches all the data from
		the DB into an associative array.

		IMPORTANT NOTE: you *must* either:
		
		 a) run the generate_sql function before running this function, in order
		    to generate the SQL statement for execution.

		 b) Set the $this->sql_obj->string variable to a SQL string you want to
		    execute. Only do this if you understand what you're doing, since you'll
		    break all the filtering stuff.

		This data can then be used directly to generate the table, or can be
		modified by other code to produce the desired result before creating
		the final output.

		Returns the number of rows found.
	*/
	function load_data_sql()
	{
		log_debug("table", "Executing load_data_sql()");

		if (!$this->sql_obj->execute())
			return 0;

		$this->data_num_rows = $this->sql_obj->num_rows();

		if (!$this->data_num_rows)
		{
			return 0;
		}
		else
		{
			$this->sql_obj->fetch_array();
			
			foreach ($this->sql_obj->data as $data)
			{
				$tmparray = array();
			
				// run through all the fields defined in the SQL structure - we can't use the
				// defined columns, since there are often other fields queried (such as ID) which
				// are not included as columns but required for things such as hyperlinks.
				foreach ($this->sql_obj->sql_structure["fields"] as $sqlfield)
				{
					$tmparray[$sqlfield] = $data[$sqlfield];
				}

				// save data to final results
				$this->data[] = $tmparray;
			}

			return $this->data_num_rows;
		}
	}


	/*
		load_options_form()

		Imports data from POST or SESSION which matches this form to be used for the options.
	*/
	function load_options_form()
	{
		/*
			Form options can be passed in two ways:
			1. POST - this occurs when the options have been passed at the last reload
			2. SESSION - if the user goes away and returns.

		*/

		if ($_GET["reset"] == "yes")
		{
			// reset the option form
			$_SESSION["form"][$this->tablename] = NULL;
		}
		else
		{
			
			if ($_GET["table_display_options"])
			{
				log_debug("table", "Loading options form from $_GET");
				
				$this->columns		= array();
				$this->columns_order	= array();

				// load checkboxes
				foreach (array_keys($this->structure) as $column)
				{
					$column_setting = security_script_input("/^[a-z]*$/", $_GET[$column]);
					
					if ($column_setting == "on")
					{
						$this->columns[] = $column;
					}
				}

				// load orderby options
				$num_cols = count(array_keys($this->structure));
				for ($i=0; $i < $num_cols; $i++)
				{
					if ($_GET["order_$i"])
					{
						$this->columns_order[] = security_script_input("/^\S*$/", $_GET["order_$i"]);
					}
				}

				// load filterby option
				foreach (array_keys($this->filter) as $fieldname)
				{
					// switch to handle the different input types
					// TODO: find a good way to merge this code and the code in the security_form_input_predefined
					// into a single function to reduce reuse and complexity.
					switch ($this->filter[$fieldname]["type"])
					{
						case "date":
							$this->filter[$fieldname]["defaultvalue"] = security_script_input("/^[0-9]*-[0-9]*-[0-9]*$/", $_GET[$fieldname ."_yyyy"] ."-". $_GET[$fieldname ."_mm"] ."-". $_GET[$fieldname ."_dd"]);

							if ($this->filter[$fieldname]["defaultvalue"] == "--")
								$this->filter[$fieldname]["defaultvalue"] = "";
						break;

						default:
							$this->filter[$fieldname]["defaultvalue"] = security_script_input("/^\S*$/", $_GET[$fieldname]);
						break;
					}

					// just blank input if it's in error
					if ($this->filter[$fieldname]["defaultvalue"] == "error")
						$this->filter[$fieldname]["defaultvalue"] = "";
				}

			}
			elseif ($_SESSION["form"][$this->tablename]["columns"])
			{
				log_debug("table", "Loading options form from session data");
				
				// load checkboxes
				$this->columns		= $_SESSION["form"][$this->tablename]["columns"];

				// load orderby options
				$this->columns_order	= $_SESSION["form"][$this->tablename]["columns_order"];

				// load filterby options
				foreach (array_keys($this->filter) as $fieldname)
				{
					$this->filter[$fieldname]["defaultvalue"] = $_SESSION["form"][$this->tablename]["filters"][$fieldname];
				}
			}

			// save options to session data
			$_SESSION["form"][$this->tablename]["columns"]		= $this->columns;
			$_SESSION["form"][$this->tablename]["columns_order"]	= $this->columns_order;
			
			foreach (array_keys($this->filter) as $fieldname)
			{
				$_SESSION["form"][$this->tablename]["filters"][$fieldname] = $this->filter[$fieldname]["defaultvalue"];
			}
		}

		return 1;
	}


	/*
		render_column_names()

		This function creates the labels for the columns. There are two different ways for this to occur:
		1. Using the translate functions, look up the label in the language DB
		2. Use the custom provided label.
	*/
	function render_column_names()
	{
		foreach ($this->columns as $column)
		{
			if ($this->structure[$column]["custom"]["label"])
			{
				$this->render_columns[$column] = $this->structure[$column]["custom"]["label"];
			}
			else
			{
				// do translation
				$this->render_columns[$column] = language_translate_string($this->language, $column);
			}
		}

		return 1;
	}


	/*
		render_field($column, $row)

		This function correctly formats/processes values based on their type, and then returns them.
	*/
	function render_field($column, $row)
	{
		log_debug("table", "Executing render_field($column, $row)");

		/*
			See the add_column function for comments about
			the different possible types.
		*/
		switch ($this->structure[$column]["type"])
		{
			case "date":
				if ($this->data[$row][$column] == "0000-00-00" || $this->data[$row][$column] == 0)
				{
					// no date in this field, add filler
					$result = "---";
				}
				else
				{
					$result = $this->data[$row][$column];
				}
			break;

			case "timestamp":
				if ($this->data[$row][$column])
				{
					$result = date("Y-m-d H:i:s", $this->data[$row][$column]);
				}
				else
				{
					$result = "---";
				}
			break;

			case "price":
				// TODO: in future, have currency field here

				// for now, just add a $ symbol to the field.
				$result = "$". $this->data[$row][$column];
			break;

			case "hourmins":
				// value is a number of seconds, we need to convert into an H:MM format.
				$result = time_format_hourmins($this->data[$row][$column]);
			break;

			default:
				$result = $this->data[$row][$column];
			break;
			
		} // end of switch


		return $result;
	}



	/*
		render_options_form()
		
		Displays a list of all the avaliable columns for the user to select from.
	*/
	function render_options_form()
	{	
		log_debug("table", "Executing render_options_form()");

		
		// create tmp array to prevent excessive use of array_keys
		$columns_available = array_keys($this->structure);
		
		// get labels for all the columns
		$labels = language_translate($this->language, $columns_available);


		// start the form
		print "<form method=\"get\" class=\"form_standard\">";
		
		$form = New form_input;
		$form->formname = $this->tablename;
		$form->language = $this->language;

		// include page name
		$structure = NULL;
		$structure["fieldname"] 	= "page";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $_GET["page"];
		$form->add_input($structure);
		$form->render_field("page");

		// include any other fixed options
		foreach (array_keys($this->option) as $fieldname)
		{
			$structure = NULL;
			$structure["fieldname"]		= $fieldname;
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $this->option[$fieldname];
			$form->add_input($structure);
			$form->render_field($fieldname);
		}


		// flag this form as the table_display_options form
		$structure = NULL;
		$structure["fieldname"] 	= "table_display_options";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= $this->tablename;
		$form->add_input($structure);
		$form->render_field("table_display_options");


		/*
			Check box options
		*/

		// configure all the checkboxes
		$num_cols	= count($columns_available);
		$num_cols_half	= sprintf("%d", $num_cols / 2);
		
		for ($i=0; $i < $num_cols; $i++)
		{
			$column = $columns_available[$i];
			
			// define the checkbox
			$structure = NULL;
			$structure["fieldname"]		= $column;
			$structure["type"]		= "checkbox";
			
			if (in_array($column, $this->columns))
				$structure["defaultvalue"] = "on";
				
			$form->add_input($structure);

			// split the column options boxes into two different columns
			if ($i < $num_cols_half)
			{
				$column_a1[] = $column;
			}
			else
			{
				$column_a2[] = $column;
			}
			
		}
		

		// structure table
		print "<table width=\"100%\"><tr>";
	
	
		print "<td width=\"50%\" valign=\"top\"  style=\"padding: 4px; background-color: #e7e7e7;\">";
			print "<b>Fields to display:</b><br><br>";

			print "<table width=\"100%\">";
				print "<td width=\"50%\" valign=\"top\">";
		
				// display the checkbox(s)
				foreach ($column_a1 as $column)
				{
					$form->render_field($column);
				}

				print "</td>";

				print "<td width=\"50%\" valign=\"top\">";
			
				// display the checkbox(s)
				foreach ($column_a2 as $column)
				{
					$form->render_field($column);
				}

				print "</td>";
			print "</table>";
		print "</td>";

		
		/*
			Filter Options
		*/
		
		
		print "<td width=\"50%\" valign=\"top\" style=\"padding: 4px; background-color: #e7e7e7;\">";
			print "<b>Filter/Search Options:</b><br><br>";

			print "<table width=\"100%\">";

			if ($this->filter)
			{
				foreach (array_keys($this->filter) as $fieldname)
				{
					$form->add_input($this->filter[$fieldname]);
					$form->render_row($fieldname);
				}
			}
			
			print "</table>";		
		print "</td>";
		

		// new row
		print "</tr>";
		print "<tr>";


		/* Order By Options */
		print "<td width=\"100%\" colspan=\"4\" valign=\"top\" style=\"padding: 4px; background-color: #e7e7e7;\">";

			print "<br><b>Order By:</b><br>";

			// limit the number of order boxes to 4
			$num_cols = count($columns_available);

			if ($num_cols > 4)
				$num_cols = 4;

			
			for ($i=0; $i < $num_cols; $i++)
			{
				// define dropdown
				$structure = NULL;
				$structure["fieldname"]		= "order_$i";
				$structure["type"]		= "dropdown";
				
				if ($this->columns_order[$i])
					$structure["defaultvalue"] = $this->columns_order[$i];

				$structure["values"] = $columns_available;

				$form->add_input($structure);

				// display drop down
				$form->render_field($structure["fieldname"]);

				if ($i < ($num_cols - 1))
				{
					print " then ";
				}
			}
			
		print "</td>";


		/*
			Submit Row
		*/
		print "<tr>";
		print "<td colspan=\"4\" valign=\"top\" style=\"padding: 4px; background-color: #e7e7e7;\">";
	
			print "<table>";
			print "<tr><td>";
			
			// submit button	
			$structure = NULL;
			$structure["fieldname"]		= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Apply Options";
			$form->add_input($structure);

			$form->render_field("submit");

			print "</form>";
			print "</td>";


			print "<td>";


			/*
				Include a reset button - this reset button is an independent form
				which passes any required fixed options and also a reset option back to the page.

				The load_options_form function then detects this reset value and erases the session
				data for the options belonging to this table, resetting the options form to the original
				defaults.
			*/

			// start the form
			print "<form method=\"get\" class=\"form_standard\">";
			
			$form = New form_input;
			$form->formname = "reset";
			$form->language = $this->language;

			// include page name
			$structure = NULL;
			$structure["fieldname"] 	= "page";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= $_GET["page"];
			$form->add_input($structure);
			$form->render_field("page");

			// include any other fixed options
			foreach (array_keys($this->option) as $fieldname)
			{
				$structure = NULL;
				$structure["fieldname"]		= $fieldname;
				$structure["type"]		= "hidden";
				$structure["defaultvalue"]	= $this->option[$fieldname];
				$form->add_input($structure);
				$form->render_field($fieldname);
			}


			// flag as the reset form
			$structure = NULL;
			$structure["fieldname"] 	= "reset";
			$structure["type"]		= "hidden";
			$structure["defaultvalue"]	= "yes";
			$form->add_input($structure);
			$form->render_field("reset");
		
			$structure = NULL;
			$structure["fieldname"]		= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Reset Options";
			$form->add_input($structure);

			$form->render_field("submit");

			
			print "</form></td>";
			print "</tr></table>";

				
		print "</td>";
		print "</tr>";




		// end of structure table
		print "</table><br><br>";
	}



	/*
		render_table()

		This function renders the entire table.
	*/
	function render_table()
	{
		log_debug("table", "Executing render_table()");

		// translate the column labels
		$this->render_column_names();

		// display header row
		print "<table class=\"table_content\" width=\"100%\">";
		print "<tr>";

		foreach ($this->columns as $column)
		{
			// add a custom link if one has been specified, otherwise
			// just display the standard name
			if ($this->structure[$column]["custom"]["link"])
			{
				print "<td class=\"header\"><b><a class=\"header_link\" href=\"". $this->structure[$column]["custom"]["link"] ."\">". $this->render_columns[$column] ."</a></b></td>";
			}
			else
			{
				print "<td class=\"header\"><b>". $this->render_columns[$column] ."</b></td>";
			}
		}
		
		// title for optional total column (displayed when row totals are active)
		if ($this->total_rows)
			print "<td class=\"header\"><b>Total:</b></td>";
	
		// filler for optional link column
		if ($this->links)
			print "<td class=\"header\"></td>";


		print "</tr>";

		// display data
		for ($i=0; $i < $this->data_num_rows; $i++)
		{
			print "<tr>";

			// content for columns
			foreach ($this->columns as $columns)
			{
				print "<td>". $this->render_field($columns, $i) ."</td>";
			}


			// optional: row totals column
			if ($this->total_rows)
			{
				$this->data[$i]["total"] = 0;

				foreach ($this->total_rows as $total_col)
				{
					// add to the total
					$this->data[$i]["total"] += $this->data[$i][$total_col];

					// make the type of the column the same as one of the columns to be totaled
					$this->structure["total"]["type"] = $this->structure[$total_col]["type"];
				}
				
				print "<td><b>". $this->render_field("total", $i) ."</b></td>";
			}

			
			// optional: links column
			if ($this->links)
			{
				print "<td align=\"right\">";

				$links		= array_keys($this->links);
				$links_count	= count($links);
				$count		= 0;

				foreach ($links as $link)
				{
					$count++;
					
					$linkname = language_translate_string($this->language, $link);

					// link to page
					// There are two ways:
					// 1. (default) Link to index.php
					// 2. Set the ["options]["full_link"] value to yes to force a full link

					if ($this->links[$link]["options"]["full_link"] == "yes")
					{
						print "<a href=\"". $this->links[$link]["page"] ."?libfiller=n";
					}
					else
					{
						print "<a href=\"index.php?page=". $this->links[$link]["page"] ."";
					}

					// add each option
					foreach (array_keys($this->links[$link]["options"]) as $getfield)
					{
						/*
							There are two methods for setting the value of the variable:
							1. The value has been passed.
							2. The name of a column to take the value from has been passed
						*/
						if ($this->links[$link]["options"][$getfield]["value"])
						{
							print "&$getfield=". $this->links[$link]["options"][$getfield]["value"];
						}
						else
						{
							print "&$getfield=". $this->data[$i][ $this->links[$link]["options"][$getfield]["column"] ];
						}
					}

					// finish link
					print "\">$linkname</a>";

					// if required, add seporator
					if ($count < $links_count)
					{
						print " || ";
					}
				}

				print "</td>";
			}
	
			print "</tr>";
		}


		// display totals for columns
		if ($this->total_columns)
		{
			print "<tr>";

			foreach ($this->columns as $column)
			{
				print "<td class=\"footer\">";
		
				if (in_array($column, $this->total_columns))
				{
					$this->data["total"][$column] = 0;
					
					for ($i=0; $i < $this->data_num_rows; $i++)
					{
						$this->data["total"][$column] += $this->data[$i][$column];
					}

					print "<b>". $this->render_field($column, "total") ."</b>";
				}
		
				print "</td>";
			}

			// optional: totals for rows
			if ($this->total_rows)
			{
				$this->data["total"]["total"] = 0;

				// total all the total columns
				foreach ($this->total_columns as $column)
				{
					$this->data["total"]["total"] += $this->data["total"][$column];
				}

				print "<td class=\"footer\"><b>". $this->render_field("total", "total") ."</b></td>";
			}


			// optional: filler for link column
			if ($this->links)
				print "<td class=\"footer\"></td>";
			
			print "</tr>";
		}
	
		print "</table>";
		
		
	}

} // end of table class



?>