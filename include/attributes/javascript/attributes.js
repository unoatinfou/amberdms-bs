/*
	include/attributes/attributes.js

	Provides javascript logic for expanding and reducing the webforms.
*/

var highest_attr_id;
var error_color = "";

$(document).ready(function()
{
	/*
		When any element in the last row is changed (therefore, having data put into it), call a function to create a new row
	*/
	//num_values = $("input[name='num_values']").val();
	highest_attr_id = $("input[name='highest_attr_id']").val();
	
	$(".last_row").live("change", function()
	{
		string_array = this.id.split("_");
		add_attribute_row(string_array[1]);
	});
//	$("input[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);
//	$("textarea[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);

	/*
	 * 	Attach delete function to mouse click on delete link
	 */
	$(".delete_undo").live("click", function(){
		var cell = $(this).parent();
		delete_undo_row(cell);
		return false;
	});
	
	$(".delete_undo").live("select", function(){
		var cell = $(this).parent();
		delete_undo_row(cell);
		return false;
	});
	
	$("a[id^='move_row_']").live("click", function()
	{
		id = this.id.substring(9);
		console.log(id);
		group_id = $(this).parent().parent().parent().removeClass("form_error").attr("class").substring(26);
		console.log(group_id);
		show_move_dropdown(id, group_id);
		return false;
	});
	
	$("#show_add_group").click(function()
	{
		$(".add_group").show();
		$("#show_add_group").hide();
		return false;
	});
	
	$("#add_group").click(function()
	{
		add_group();
		$(this).siblings("input").val("");
		return false;
	});
	
	$("#close_add_group").click(function()
	{
		$(".add_group").hide();
		$("#show_add_group").show();
		return false;
	});
	
	$(".show_attributes").live("click", function()
	{
		id = this.id;
		$("." + id).show();
		$(this).removeClass("show_attributes").addClass("hide_attributes");
		$(this).children("td:last").html("<b>^</b>");
		return false;
	});
	
	$(".hide_attributes").live("click", function()
	{
		id = this.id;
		$("." + id).hide();
		$(this).children("td:last").html("<b>v</b>");
		$(this).removeClass("hide_attributes").addClass("show_attributes");
		return false;
	});
	
	error_color = $(".form_error").css("background-color");
//	$(".form_error").removeClass("form_error").css("background-color", error_color);
	error_row = $(".form_error").attr("class");
	if(error_row)
	{
		$(error_row).removeClass("form_error").css("background-color", error_color);
		show_class = error_row.substring(27);
		$("." + show_class).show();
		$("#" + show_class).removeClass("show_attributes").addClass("hide_attributes");
		$("#" + show_class).children("td:last").html("<b>^</b>");
	}

});

function add_group()
{
	if ($("input[name='add_group']").val().length > 0)
	{
		name = $("input[name=\"add_group\"]").val();
		$.get("customers/ajax/add_group.php", {name: name}, function(id)
		{
			console.log($(".form_table tr:eq(2)"));
			console.log($(".form_table tr:eq(4)"));
			new_header = $(".form_table tr:eq(0)").clone();
			new_sub_header = $(".form_table tr:eq(1)").clone();
			new_blank_one = $(".form_table tr:eq(2)").clone();
			new_row_one = $(".form_table tr:eq(3)").clone();
			new_row_two = $(".form_table tr:eq(3)").clone();
			new_blank_two = $(".form_table tr:eq(2)").clone();

			$(new_header).attr("id", "group_row_" + id).removeClass("show_attributes").addClass("hide_attributes");
			$(new_header).children("td:first").html("<b>" + name + "</b>");
			$(new_header).children("td:last").html("<b>^</b>");
			$(new_sub_header).removeAttr("class").addClass("header").addClass("group_row_" + id);
			$(new_blank_one).removeAttr("class").addClass("group_row_" + id);
			
			$(new_row_one).removeAttr("class").addClass("table_highlight").addClass("group_row_" + id);
			
			//highest_attr_id++;
			attr_one_num = (parseInt(highest_attr_id)+1);
			$(new_row_one).children().children("input[name$='_key']").attr("id", "attribute_" + attr_one_num + "_key").attr("name", "attribute_" + attr_one_num + "_key").val("").unbind("change");
			$(new_row_one).children().children("input[name$='_value']").attr("id", "attribute_" + attr_one_num + "_value").attr("name", "attribute_" + attr_one_num + "_value").val("");
			//$("select[id^='select_group_attr_" + attr_id + "']").parent().html("<strong><a href=\"\" id=\"move_row_" + attr_id + "\">move...</a></strong>");
			$(new_row_one).children().children().children("a[id^='move_row']").attr("id", "move_row_" + attr_one_num);
			$(new_row_one).children().children("select[id^='select_group_attr_']").remove();
			$(new_row_one).children().children("a[id^='move_row_']").show();
			$(new_row_one).children().children("input[name$='_group']").attr("name", "attribute_" + attr_one_num + "_group").val(id);
			$(new_row_one).children().children("input[name$='_delete_undo']").attr("name", "attribute_" + attr_one_num + "_delete_undo").val("false");
			
			$(function() {
				$.get("customers/ajax/get_attribute_key_list.php", function(text)
				{
					list = "[" + text + "]";
					eval("autocomplete_attribute_" + attr_one_num + "_key =" + list);
					$("#attribute_" + attr_one_num + "_key").autocomplete({
						source: eval("autocomplete_attribute_" + attr_one_num + "_key")
					});
				});
			});
			
			$(new_row_two).removeAttr("class").addClass("table_highlight").addClass("group_row_" + id);
			
			//highest_attr_id++;
			attr_two_num = (parseInt(highest_attr_id)+2);
			$(new_row_two).children().children("input[name$='_key']").attr("id", "attribute_" + attr_two_num + "_key").attr("name", "attribute_" + attr_two_num + "_key").val("").unbind("change").addClass("last_row");
			$(new_row_two).children().children("input[name$='_value']").attr("id", "attribute_" + attr_two_num + "_value").attr("name", "attribute_" + attr_two_num + "_value").val("").addClass("last_row");
			$(new_row_two).children().children().children("a[id^='move_row']").attr("id", "move_row_" + attr_two_num);
			$(new_row_two).children().children("select[id^='select_group_attr_']").remove();
			$(new_row_two).children().children("a[id^='move_row_']").show();
			$(new_row_two).children().children("input[name$='_group']").attr("name", "attribute_" + attr_two_num + "_group").val(id);
			$(new_row_two).children().children("input[name$='_delete_undo']").attr("name", "attribute_" + attr_two_num + "_delete_undo").val("false");
			
			$(function() {
				$.get("customers/ajax/get_attribute_key_list.php", function(text)
				{
					list = "[" + text + "]";
					eval("autocomplete_attribute_" + attr_two_num + "_key =" + list);
					$("#attribute_" + attr_two_num + "_key").autocomplete({
						source: eval("autocomplete_attribute_" + attr_two_num + "_key")
					});
				});
			}); 
			
			$(new_blank_two).removeAttr("class");
			
			console.log($(new_row_one));
			console.log($(new_row_two));
			//$(".form_table tr:eq(0)").before(new_blank_two).before(new_row_two).before(new_row_one).before(new_blank_one).before(new_sub_header).before(new_header);
			$(".form_table tr:eq(0)").before(new_header).before(new_sub_header).before(new_blank_one).before(new_row_one).before(new_row_two).before(new_blank_two);
			
			$(new_sub_header).show();
			$(new_blank_one).show();
			$(new_row_one).show();
			$(new_row_two).show();
			
			highest_attr_id = parseInt(highest_attr_id)+2;
			$("input[name='highest_attr_id']").val(highest_attr_id);
			
			//add group id to list
			new_val = $("input[name='new_groups']").val() + id + ",";
			$("input[name='new_groups']").val(new_val);
			
			//create variable to hold list of attributes
			attribute_list = attr_one_num + "," + attr_two_num;
			$(".form_table").after("<input type=\"hidden\" name=\"group_" + id + "_attribute_list\" value=\"" + attribute_list + "\" />");
		});
	}
}
function show_move_dropdown(id, group_id)
{
	$.get("customers/ajax/show_move_dropdown.php", {id: id, group_id: group_id}, function(html)
	{
		$("#move_row_" + id).hide();
		$("#move_row_" + id).after(html);
		$("select[id^='select_group_attr_']").change(function()
		{
			group_id = $(this).val();
			move_attribute_row(id, group_id);
		});
	});
}

function move_attribute_row(attr_id, group_id)
{
//	$("#attribute_" + attr_id + "_key").parent().parent().clone(true).insertBefore($(".last_row").parent().parent(".group_row_" + group_id));
	console.log(group_id);
	//$.get("customers/ajax/change_attribute_group.php", {attr_id: attr_id, group_id: group_id});
	if ($("#attribute_" + attr_id + "_key").hasClass("last_row"))
	{
		add_attribute_row(attr_id);
	}
	old_location = $("#attribute_" + attr_id + "_key").parent().parent();
	old_group_id = $(old_location).attr("class").substring(27);
	new_location = $(old_location).clone().insertBefore($(".last_row").parent().parent(".group_row_" + group_id)).removeAttr("class").addClass("table_highlight").addClass("group_row_" + group_id);
	$(old_location).remove();
	
	$("select[id='select_group_attr_" + attr_id + "']").remove();
	$("#move_row_" + attr_id).show();
	$("input[name$='attribute_" + attr_id + "_group']").val(group_id);
	
	$(".group_row_" + group_id).show();	
	$("#group_row_" + group_id).children("td:last").html("<b>^</b>");
	$("#group_row_" + group_id).removeClass("show_attributes").addClass("hide_attributes");
	
	$(function() {
		$.get("customers/ajax/get_attribute_key_list.php", function(text)
		{
			list = "[" + text + "]";
			eval("autocomplete_attribute_" + attr_id + "_key =" + list);
			$("#attribute_" + attr_id + "_key").autocomplete({
				source: eval("autocomplete_attribute_" + attr_id + "_key")
			});
		});
	}); 
	
	//add attribute to group list
	new_val = $("input[name='group_" + group_id + "_attribute_list']").val() + group_id + ",";
	$("input[name='group_" + group_id + "_attribute_list']").val(new_val);
	
	//remove from old group attribute list
	if($("input[name='group_" + old_group_id + "_attribute_list']"))
	{
		attr_array = $("input[name='group_" + old_group_id + "_attribute_list']").val().split(",");
		tmp_array = new Array();
		for (i=0; i<attr_array.length; i++)
		{
			if(attr_array[i] != attr_id)
			{
				tmp_array.push(attr_array[i]);
			}
		}
		$("input[name='group_" + old_group_id + "_attribute_list']").val(tmp_array.toString())
	}	
}
/*
	add_attribute_row
*/
function add_attribute_row(id)
{
	//previous_row		= $("input[name='attribute_" + (num_values-1) + "_key']").parent().parent();
	highest_attr_id++;
	
	previous_row	= $("#attribute_" + id + "_key").parent().parent();
	new_row			= $(previous_row).clone().insertAfter(previous_row);
	
	$(previous_row).children().children("#attribute_" + id + "_key").removeClass("last_row");
	$(previous_row).children().children("#attribute_" + id + "_value").removeClass("last_row");

	$(new_row).children().children("#attribute_" + id + "_key").attr("name", "attribute_" + highest_attr_id + "_key").attr("id", "attribute_" + highest_attr_id + "_key").val("");
	$(new_row).children().children("#attribute_" + id + "_value").attr("name", "attribute_" + highest_attr_id + "_value").attr("id", "attribute_" + highest_attr_id + "_value").val("");
	$(new_row).children().children("input[name='attribute_" + id + "_delete_undo']").attr("name", "attribute_" + highest_attr_id + "_delete_undo").val("false");
	$(new_row).children().children().children("#move_row_" + id).attr("id", "move_row_" + highest_attr_id);
	$(new_row).children().children("select[id^='select_group_attr_']").remove();
	$(new_row).children().children().children("a[id^='move_row_']").show();
	console.log("move show");
	console.log($(new_row).children().children("a[id^='move_row_']"));
	console.log($(new_row).children().children().children("a"));
	$(new_row).children().children("input[name$='_group']").attr("name", "attribute_" + highest_attr_id + "_group");
	
	field_color = $(".table_highlight").css("background-color");
	if($(new_row).css("background-color") == error_color)
	{
		$(new_row).css("background-color", field_color);
	}
	
	//$(new_row).children().children("input[name='attribute_" + num_values + "_key']").attr("id", "attribute_" + num_values + "_key");
	
	//remove function calls from previous row
	//$("select[name^='attribute_" + (num_values-1) + "']").unbind("change");
	$("input[name^='attribute_" + id + "']").unbind("change");
	$("input[name^='attribute_" + highest_attr_id + "']").change(function()
	{ 
		add_attribute_row(highest_attr_id);
	});
	//$("textarea[name^='attribute_" + (num_values-1) + "']").unbind("change");
	
	//add one to num_tran
	//num_values++;
	$("input[name='highest_attr_id']").val(highest_attr_id);
	
	//add attribute to group list
	group_id = $(new_row).attr("class").substring(27);
	new_val = $("input[name='group_" + group_id + "_attribute_list']").val() + highest_attr_id + ",";
	$("input[name='group_" + group_id + "_attribute_list']").val(new_val);
	
//	//add function calls to new row
//	$("select[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);
//	$("input[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);
//	$("textarea[name^='attribute_" + (num_values-1) + "']").change(add_attribute_row);

	// set values for javascript autocomplete fields.
//	$("#attribute_" + (num_values-1) + "_key").autocomplete({
//		source: autocomplete_attribute_0_key
//	});
//	$(function() {
//		eval("autocomplete_attribute_" + (highest_attr_id - 1) + "_key = ['default']");
//		$("#attribute_" + highest_attr_id + "_key").autocomplete({
//			source: eval("autocomplete_attribute_" + (highest_attr_id - 1) + "_key")
//		});
//	}); 
	$(function() {
		$.get("customers/ajax/get_attribute_key_list.php", function(text)
		{
			list = "[" + text + "]";
			eval("autocomplete_attribute_" + highest_attr_id + "_key =" + list);
			$("#attribute_" + highest_attr_id + "_key").autocomplete({
				source: eval("autocomplete_attribute_" + highest_attr_id + "_key")
			});
		});
	}); 


}



/*
 * 	delete_row
 * 
 * 	grey out row and set hidden delete variable to true
 */
function delete_undo_row(cell)
{
	var status = $(cell).children("input").val();
	if (status == "false")
	{
		$(cell).siblings().fadeTo("slow", 0.1);
		$(cell).children(".delete_undo").children().html("undo");
		$(cell).children("input").val("true");
	}
	else if (status == "true")
	{
		$(cell).siblings().fadeTo("slow", 1);
		$(cell).children(".delete_undo").children().html("delete");
		$(cell).children("input").val("false");
	}
}




