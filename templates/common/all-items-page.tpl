<div class="ia-items">
	{foreach $all_items as $oneitem}
		<div class="media ia-item ia-item-bordered">
			{assign var='item' value=$oneitem}

			{foreach $all_item_fields as $onefield}
				{if 'plan_id' != $onefield.name}
					{include file='field-type-content-view.tpl' variable=$onefield wrappedValues=true}
				{/if}
			{/foreach}

			<div class="ia-item-panel">
				{ia_url item=$all_item_type data=$oneitem type='icon' classname='btn-info'}

				{if isset($member.id) && $member.id}
					{printFavorites item=$oneitem itemtype=$all_item_type classname='btn-info'}
					{accountActions item=$oneitem itemtype=$all_item_type classname='btn-info'}
				{/if}
			</div>
		</div>
	{/foreach}
</div>