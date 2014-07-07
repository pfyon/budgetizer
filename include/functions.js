$(function()
{
	var index = $('input.description_contains').size();

	$('input#date_start').datepicker();
	$('input#date_end').datepicker();
	$('input#tags_reset').click(function(event)
	{
		event.preventDefault();
		$('input#date_start').datepicker('setDate', null);
		$('input#date_end').datepicker('setDate', null);
		$('input#filter_tags').val('');
	});

	$('input#add_description_field').click(function()
	{
		$(this).before('<input type="text" name="description_contains[' + index + '] value="" class="description_contains"/>');
		index++;
	});

	$("input.tag_autocomplete").autocomplete(
	{
		source: availableTags,
		minLength: 1
	});

	$("input.transaction_addtag").on("keypress autocompleteselect", function(event, ui)
	{
		if(event.type == "keypress")
		{
			//It's a keypress, we need to check that it was the "enter" key aka number 13
			if(event.which == 13)
			{
				$(this).addTag();
			}
		} else if(event.type == "autocompleteselect")
		{
			//We have an autocomplete event so we can just send the value right away
			event.preventDefault();
			$(this).val(ui.item.value);
			$(this).addTag();
		}
	});

	$(document).on("click", "div.tag", function(event, ui)
	{
		$(this).removeTag();
	});

	$("input#tags_reset").click(function()
	{
		$("input#date_start, input#date_end").val();
	});

	$("input#submit_bulk_tag").click(function(event)
	{
		event.preventDefault();
		var bulk_tags = $("input#input_bulk_tag").val();
		if(confirm("Are you sure you want to tag ALL of these transactions with the following tags?\n\n" + bulk_tags))
		{
			$("input.transaction_addtag").val(bulk_tags);
			$.each($("input.transaction_addtag"), function(index, element)
			{
				$(element).addTag();
			});
		}
	});
});

$.fn.addTag = function()
{
	var parentRow = this.closest('tr');
	var tagname = this.val().trim().toLowerCase();
	var rowid = parentRow.attr('id');

	//Clear out the input, makes it feel more responsive to the user
	this.val("");

	if(tagname != '')
	{
		$.post("ajax/tags.php",
			{
				action: "add",
				transaction_id: rowid,
				tagname: tagname
			},
			function(data)
			{
				var taghtml = '';
				$.each(data, function(index, value)
				{
					taghtml += '<div class="tag">' + value + '</div>';
				});
	
				$('td.transaction_taglist', parentRow).html(taghtml);
	
				if($.inArray(tagname, data) > -1)
				{
					//Our tagname is in the returned list of tags, let's see if it's in our array of choices for the user
					if($.inArray(tagname, availableTags) < 0)
					{
						//It's not in the choices, we should add it
						availableTags.push(tagname);
						availableTags.sort();
					}
				}
			},
			'json'
		);
	}
	return this;
}

$.fn.removeTag = function()
{
	var rowid = this.closest('tr').attr('id');
	var tag = this.html().trim();
	this.remove();

	$.post("ajax/tags.php",
		{
			action: "remove",
			transaction_id: rowid,
			tagname: tag
		},
		function(data)
		{
			//If you wanted to implement removing the tag from your auto suggest box, here is where you'd do it
		},
		'json'
	);
}
