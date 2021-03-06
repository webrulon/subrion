<div class="wrap-list">
	{foreach $item_sections as $key => $section}
		{if !empty($section.fields) && isset($section.name)}
			{if '___empty___' != $key}
				{assign var='grouptitle' value="fieldgroup_{$section.name}"}
			{else}
				{assign var='grouptitle' value='other'}
			{/if}

			<div class="wrap-group">
				<div class="wrap-group-heading">
					<h4>{lang key=$grouptitle}
						{if isset($section.description) && $section.description}
							<a href="#" class="js-tooltip" data-placement="right" title="{$section.description}"><i class="i-info"></i></a>
						{/if}
					</h4>
				</div>

				{if isset($fieldset_before[$section.name])}{$fieldset_before[$section.name]}{/if}

				{foreach $section.fields as $variable}
					{if !isset($exceptions) || !in_array($variable.name, $exceptions)}
						{include file='field-type-content-manage.tpl'}
					{/if}
				{/foreach}

				{if isset($fieldset_after[$section.name])}{$fieldset_after[$section.name]}{/if}
			</div>
		{/if}
	{/foreach}

	{if isset($isSystem) && $isSystem}
		{include file='fields-system.tpl'}
	{/if}
</div>