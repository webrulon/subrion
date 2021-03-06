function dialog_package(type, item, package_url, package_name)
{
	intelli.config.default_package = $('#js-default-package-value').val();
	var default_package = '<div class="alert alert-info">' + _t('root_old').replace(/:name/gi, intelli.config.default_package) + '</div>' +
						  '<p><code>'+intelli.config.ia_url+'</code> <input type="text" value="" name="url[0]" class="common" /> <code>/</code></p>';
	var form_text = '<div class="url_type">' +
						'<label for="subdomain_type"><input type="radio" value="1" name="type" id="subdomain_type"> '+_t('subdomain_title')+'</label>' +
						'<div class="url_type_info"><p>' + _t('subdomain_about') + '</p><code>http://</code> <input type="text" value="'+/*package_name*/package_url+'" name="url[1]" class="common"> <code>.' + location.hostname + '/</code></div>' + 
					'</div>' +
					'<div class="url_type">' +
						'<label for="subdirectory_type"><input type="radio" value="2" name="type"' + (intelli.config.default_package ? ' checked="checked"' : '') + ' id="subdirectory_type" /> ' + _t('subdirectory_title') + '</label>' +
						'<div class="url_type_info"><p>'+_t('subdirectory_about')+'</p><code>' + intelli.config.ia_url+'</code> <input type="text" value="'+package_url+'" name="url[2]" class="common"> <code>/</code></div>' +
					'</div>';

	if (intelli.package_window)
	{
		intelli.package_window.remove();
	}
	var html = '';

	if (type == 'install')
	{
		html = '<div class="url_type"><label for="root_type"><input type="radio" value="0" name="type" id="root_type"'
			+ (intelli.config.default_package ? '' : ' checked="checked"') + ' /> '+_t('root_title')+'</label><div class="url_type_info"><p>'+_t('root_about')+'</p>'
			+ (intelli.config.default_package ? default_package : '') +
			'</div></div>' + form_text;
	}
	else if (type == 'set_default')
	{
		if (intelli.config.default_package)
		{
			html = '<div class="url_type">' + default_package + '</div>';
		}
		else
		{
			window.location = $(item).attr('url');
			return false;
		}
		
	}
	else if (type == 'reset')
	{
		html = '<div class="url_type">' + _t('reset_default_package') + '</div>' + form_text;
	}
	html = '<form action="'+$(item).attr('url')+'" method="get" id="package_form">' + html + '</form>';

	intelli.package_window = new Ext.Window(
	{
		title: _t('extra_installation'),
		closable: true,
		html: html,
		maxWidth: 600,
		bodyPadding: 10,
		autoScroll: true,
		buttons: [
		{
			text: _t(type),
			handler: function()
			{
				$('#package_form').submit();
			}
		},{
			text: _t('cancel'),
			handler: function()
			{
				intelli.package_window.hide(); 
			}
		}]
	}).show();

	$('input[name="url[2]"]').on('change', function()
	{
		$('#subdirectory_type').prop('checked', true);
	});

	$('input[type="radio"]:checked', '#package_form').parent().addClass('selected');
	$('input[type="radio"]', '#package_form').on('change', function()
	{
		if ($(this).is(':checked'))
		{
			$('input[type="radio"]', '#package_form').parent().removeClass('selected');
			$('input[type="radio"]:checked', '#package_form').parent().addClass('selected');
		}
	});
}
function installPackage(item, package_url, package_name)
{
	dialog_package('install', item, package_url, package_name);	
}
function setDefault(item, package_url, package_name)
{
	dialog_package('set_default', item, package_url, package_name);
}
function resetUrl(item, package_url, package_name)
{
	dialog_package('reset', item, package_url, package_name);
}

function readme(pluginName)
{
	Ext.Ajax.request({
		url: intelli.config.admin_url + '/packages.json',
		method: 'POST',
		params: {action: 'info', name: pluginName},
		failure: function()
		{
			Ext.MessageBox.alert(_t('error_while_doc_tabs'));
		},
		success: function(response)
		{
			response = Ext.decode(response.responseText);
			var tabs = response.tabs;
			var info = response.info;

			if (null != tabs)
			{
				var packageTabs = new Ext.TabPanel(
				{
					region: 'center',
					bodyStyle: 'padding: 5px;',
					activeTab: 0,
					defaults: {autoScroll: true},
					items: tabs
				});

				var packageInfo = new Ext.Panel(
				{
					region: 'east',
					split: true,
					minWidth: 200,
					collapsible: true,
					html: info,
					bodyStyle: 'padding: 5px;'
				});

				var win = new Ext.Window(
				{
					title: _t('extra_documentation'),
					closable: true,
					width: 800,
					height: 550,
					border: false,
					plain: true,
					layout: 'border',
					items: [packageTabs, packageInfo]
				});

				win.show();
			}
			else
			{
				Ext.Msg.show(
				{
					title: _t('error'),
					msg: _t('doc_extra_not_available'),
					buttons: Ext.Msg.OK,
					icon: Ext.Msg.ERROR
				});
			}
		}
	});
}