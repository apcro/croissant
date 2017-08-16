{if $page_link.pagerlinks neq null}
	{* We need to culculate the width of our pager ul dynamically in order to center it.. *}
	{assign var=width value=0}
	{foreach name=pager from=$page_link.pagerlinks item=v key=k}
		{if $v.num eq 'First' or $v.num eq 'Previous' or $v.num eq 'Next' or $v.num eq 'Last'}
			{if $v.link neq ''}
				{if $v.num eq 'Next'}
					{assign var=width value=$width+48}
				{elseif $v.num eq 'Previous'}
					{assign var=width value=$width+92}
				{/if}
			{/if}
		{else}
			{assign var=width value=$width+30}
		{/if}
	{/foreach}
	{assign var=width value=$width+14}
	{if $page >= 8}
		{assign var=width value=$width+40}
	{/if}
	{if $page >= 98}
		{assign var=width value=$width+40}
	{/if}
	{if $page >= 998}
		{assign var=width value=$width+40}
	{/if}
    {assign var=width value=$width+400}
	<div id="pager" style="width: '100%'" class="pagination_Block">
		<ul>
			<li>Page {$page} of {$totalPage}</li>
			{foreach name=pager from=$page_link.pagerlinks item=v key=k}
				{if $v.num eq 'First'}
					{if $v.link neq ''}
					<li><a href="{$v.link}" title="First page">&laquo;</a></li><li>...</li>
					{/if}
				{elseif $v.num eq 'Last'}

					{if $v.link neq ''}
					<li>...</li>
					<li><a href="{$v.link}" title="Last page">&raquo;</a></li>
					{/if}

				{elseif $v.num eq 'Previous' OR $v.num eq 'Next'}
				{else}
					<li {if $page eq $v.num}class="active"{/if}>
						{if $page neq $v.num}<a href="{$v.link}">{$v.num}</a>{else}{$v.num}{/if}
					</li>
				{/if}
			{/foreach}
		</ul>
	</div>
{/if}