<?php
/*
	vendors.php
	
	access: "vendors_view" group members

	Displays a list of all the vendors on the system.
*/

if (user_permissions_get('vendors_view'))
{
	function page_render()
	{
		// establish a new table object
		$vendor_list = New table;

		$vendor_list->language	= $_SESSION["user"]["lang"];
		$vendor_list->tablename	= "vendor_list";
		$vendor_list->sql_table	= "vendors";


		// define all the columns and structure
		$vendor_list->add_column("standard", "id_vendor", "id");
		$vendor_list->add_column("standard", "name_vendor", "");
		$vendor_list->add_column("standard", "name_contact", "");
		$vendor_list->add_column("standard", "contact_phone", "");
		$vendor_list->add_column("standard", "contact_email", "");
		$vendor_list->add_column("standard", "contact_fax", "");
		$vendor_list->add_column("date", "date_start", "");
		$vendor_list->add_column("date", "date_end", "");
		$vendor_list->add_column("standard", "tax_number", "");
		$vendor_list->add_column("standard", "address1_city", "");
		$vendor_list->add_column("standard", "address1_state", "");
		$vendor_list->add_column("standard", "address1_country", "");

		// defaults
		$vendor_list->columns		= array("name_vendor", "name_contact", "contact_phone", "contact_email");
		$vendor_list->columns_order	= array("name_vendor");


		// heading
		print "<h3>VENDORS/SUPPLIERS LIST</h3><br><br>";


		// options form
		$vendor_list->load_options_form();
		$vendor_list->render_options_form();


		// fetch all the vendor information
		$vendor_list->generate_sql();
		$vendor_list->load_data_sql();

		if (!count($vendor_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$vendor_list->data_num_rows)
		{
			print "<p><b>You currently have no vendors in your database.</b></p>";
		}
		else
		{
			// view link
			$structure = NULL;
			$structure["id"]["column"] = "id";
			$vendor_list->add_link("view", "vendors/view.php", $structure);

			// display the table
			$vendor_list->render_table();

			// TODO: display CSV download link
		}

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
