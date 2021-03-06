<?php
/*
	services/plan-edit-process.php

	access: services_write

	Adjusts the service plan configuration.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");


// custom includes
require("../include/services/inc_services_process.php");


if (user_permissions_get('services_write'))
{
	/////////////////////////

	service_form_plan_process();

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
