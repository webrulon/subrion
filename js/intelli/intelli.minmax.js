jQuery(document).ready(function($) {
	var boxesState = JSON.parse(intelli.cookie.read('boxesState'));
	if (typeof boxesState == 'undefined' || boxesState == null) {
		boxesState = {};
	}
	
	$('.collapsible').each(function()
	{
		var blockId = $(this).attr("id");

		if (!$('.minmax-toggle', this).length > 0) {
			$('.box-caption', this).append('<a href="#" class="minmax-toggle"><i></i></a>');
		}

		if (boxesState[blockId] == 'visible') {
			$(this).removeClass('collapsed');
		}
		else if (boxesState[blockId] == 'hidden') {
			$(this).addClass('collapsed');	
		}
	});

	$('.minmax-toggle').click(function(e)
	{
		e.preventDefault();

		var o = $(this).closest('.collapsible');
		var blockId = o.attr('id');

		if (o.hasClass('collapsed'))
		{
			$('.box-content', o).slideDown('fast', function()
			{
				o.removeClass('collapsed');
			});

			boxesState[blockId] = 'visible';
		}
		else
		{
			$('.box-content', o).slideUp('fast', function()
			{
				o.addClass('collapsed');
			});

			boxesState[blockId] = 'hidden';
		}

		intelli.cookie.write('boxesState', JSON.stringify(boxesState));
	});
});
