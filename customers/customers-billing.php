<?php
/*
	customers.php
	
	access: "customers_view" group members

	Displays a list of all the customers on the system.
*/

include("include/services/inc_services.php");

class page_output
{
	var $obj_table_list;


	function check_permissions()
	{
		if (user_permissions_get('customers_view'))
		{
			return 1;
		}
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	/*
		Define table and load data
	*/
	function execute()
	{
		// define customer list table
		$this->obj_table_list			= New table;
		$this->obj_table_list->language		= $_SESSION["user"]["lang"];
		$this->obj_table_list->tablename	= "customer_list_billing";

		// define all the columns and structure
		$this->obj_table_list->add_column("standard", "code_customer", "");
		$this->obj_table_list->add_column("standard", "name_customer", "");
		$this->obj_table_list->add_column("standard", "billing_direct_debit", "");
		$this->obj_table_list->add_column("money", "balance_owed", "NONE");

		// defaults
		$this->obj_table_list->columns			= array("code_customer", "name_customer", 'billing_direct_debit', 'balance_owed');
		$this->obj_table_list->columns_order		= array("name_customer");
		$this->obj_table_list->columns_order_options	= array("code_customer", "name_customer", "name_contact", "contact_phone", "contact_mobile", "contact_email", "contact_fax", "date_start", "date_end", "tax_number", "address1_city", "address1_state", "address1_country");

		// define SQL structure
		$this->obj_table_list->sql_obj->prepare_sql_settable("customers");
		$this->obj_table_list->sql_obj->prepare_sql_addfield("id", "");


		/*
                // define SQL structure
                $this->obj_table->sql_obj->prepare_sql_settable("account_ar");
                $this->obj_table->sql_obj->prepare_sql_addfield("id", "account_ar.id");
                $this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN customers ON customers.id = account_ar.customerid");
                $this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN staff ON staff.id = account_ar.employeeid");
		*/
		
		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "date_start";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_start >= 'value'";
		$this->obj_table_list->add_filter($structure);

		$structure = NULL;
		$structure["fieldname"] = "date_end";
		$structure["type"]	= "date";
		$structure["sql"]	= "date_end <= 'value' AND date_end != '0000-00-00'";
		$this->obj_table_list->add_filter($structure);
		
		/*
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(code_customer LIKE '%value%' OR name_customer LIKE '%value%')";
		$this->obj_table_list->add_filter($structure);
		*/

		$structure = NULL;
		$structure["fieldname"] 	= "billing_method";
		$structure["type"]		= "checkbox";
		$structure["options"]["label"]	= "Billing method Direct Debit";
		$structure["defaultvalue"]	= "1";
		$structure["sql"]		= "billing_method = 'direct debit'";
		$this->obj_table_list->add_filter($structure);

		// load settings from options form
		$this->obj_table_list->load_options_form();

		// fetch all the customer information
		$this->obj_table_list->generate_sql();
		$this->obj_table_list->load_data_sql();

	
		// handle balance owed
		if (in_array('balance_owed', $this->obj_table_list->columns)) {

			$obj_balance_owed_sql 		= New sql_query;
			$obj_balance_owed_sql->string	= 
			       "SELECT customerid, sum(bal) AS balance_owed FROM (
				SELECT ar.customerid, sum(ar.amount_total - ar.amount_paid) as bal 
				FROM account_ar AS ar 
				WHERE 1 GROUP BY ar.customerid
				UNION
				SELECT arc.customerid, sum(arc.amount_total) as bal
				FROM account_ar_credit AS arc
				WHERE 1 GROUP BY arc.customerid
				) as tbl GROUP by customerid";

			$obj_balance_owed_sql->execute();
	
			if ($obj_balance_owed_sql->num_rows())
			{
				$obj_balance_owed_sql->fetch_array();

				foreach ($obj_balance_owed_sql->data as $data_balance_owed)
				{
					$map_balance_owed[ $data_balance_owed['customerid'] ] = $data_balance_owed['balance_owed'];
				}

			}

			// replace with 0.00 or the calculated balance value
			for ($i=0; $i < $this->obj_table_list->data_num_rows; $i++)
			{
				$this->obj_table_list->data[$i]["balance_owed"] = "0.00";

				if(isset($map_balance_owed[$this->obj_table_list->data[$i]['id']])) {
					$this->obj_table_list->data[$i]["balance_owed"] = $map_balance_owed[$this->obj_table_list->data[$i]['id']];
				}

				// we dont want 0 balance (or credit) records here
				if($this->obj_table_list->data[$i]["balance_owed"] <= 0) {
					unset($this->obj_table_list->data[$i]);
				}

			}

			// re index after the potential unsets
			$this->obj_table_list->data = @array_values($this->obj_table_list->data);
			$this->obj_table_list->data_num_rows = count($this->obj_table_list->data);




			unset($map_balance_owed);
			unset($obj_balance_owed_sql);
			
		}

	} // end of load_data()



	/*
		Output: HTML format
	*/
	function render_html()
	{
		// heading
		print "<h3>CUSTOMER LIST</h3><br><br>";
		print "<p>List of customers with direct debit enabled and a current balance</p>";

		// load options form
		// $this->obj_table_list->render_options_form();


		// display results
		if (!count($this->obj_table_list->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		else if (!$this->obj_table_list->data_num_rows)
		{
			format_msgbox("info", "<p>You currently have no customers in your database.</p>");
		}
		else
		{
			// phases link
//			$structure = NULL;
//			$structure["reseller_id"]["column"]	= "id";
//			$this->obj_table_list->add_link("customer_reseller", "customers/view.php", $structure);
			

			// calculate all the totals and prepare processed values
			$this->obj_table_list->render_table_prepare();

			// display header row
			print "<table class=\"table_content\" cellspacing=\"0\" width=\"100%\">";	
					
			print "<tr>";
				foreach ($this->obj_table_list->columns as $column)
				{
					print "<td class=\"header\"><b>". $this->obj_table_list->render_columns[$column] ."</b></td>";
				}
				
				//placeholder for links
				print "<td class=\"header\">&nbsp;</td>";				
				
			print "</tr>";
			
			// display data
			for ($i=0; $i < $this->obj_table_list->data_num_rows; $i++)
			{
				$customer_id = $this->obj_table_list->data[$i]["id"];
				$contact_id = sql_get_singlevalue("SELECT id AS value FROM customer_contacts WHERE customer_id = '" .$customer_id. "' AND role = 'accounts' LIMIT 1");
				print "<tr>";
				foreach ($this->obj_table_list->columns as $columns)
				{
					print "<td valign=\"top\">";						
						//contact name
						if ($columns == "name_contact")
						{
							$value = sql_get_singlevalue("SELECT contact AS value FROM customer_contacts WHERE id = '" .$contact_id. "' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact phone
						else if ($columns == "contact_phone")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type = 'phone' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact mobile
						else if ($columns == "contact_mobile")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type= 'mobile' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact email
						else if ($columns == "contact_email")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type= 'email' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//contact fax
						else if ($columns == "contact_fax")
						{
							$value = sql_get_singlevalue("SELECT detail AS value FROM customer_contact_records WHERE contact_id = '" .$contact_id. "' AND type= 'fax' LIMIT 1");
							if ($value)
							{
								print $value;
							}
						}
						
						//all other columns
						else
						{
							if ($this->obj_table_list->data_render[$i][$columns])
							{
//								print $columns;
								print $this->obj_table_list->data_render[$i][$columns];
							}
							else
							{
								print "&nbsp;";
							}
						}
					print "</td>";
				}
				
					//links
					print "<td align=\"right\" nowrap >";
						print "<a class=\"button_small\" href=\"index.php?page=customers/view.php&id=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("details"). "</a> ";
						print "<a class=\"button_small\" href=\"index.php?page=customers/attributes.php&id_customer=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("tbl_lnk_attributes"). "</a> ";
						print "<a class=\"button_small\" href=\"index.php?page=customers/orders.php&id_customer=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("orders"). "</a> ";
						print "<a class=\"button_small\" href=\"index.php?page=customers/invoices.php&id=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("invoices"). "</a> ";
						print "<a class=\"button_small\" href=\"index.php?page=customers/services.php&id=" .$this->obj_table_list->data[$i]["id"]. "\">" .lang_trans("services"). "</a> ";						
					print "</td>";
				print "</tr>";
			}
			print "</table>";
			print "<br />";

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" style=\"font-weight: normal;\"  href=\"index-export.php?mode=csv&page=customers/customers-billing.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" style=\"font-weight: normal;\" href=\"index-export.php?mode=pdf&page=customers/customers-billing.php\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		$this->obj_table_list->render_table_csv();
	}
	
	
	function render_pdf()
	{
		$this->obj_table_list->render_table_pdf();
	}
	

} // end class page_output


?>
