<div id="content_pri">
	{include file="shared/blocks/pulldown.tpl"}
	<h2>404, not found</h2>
	<div class="message">
		<h3>Sorry, the page you are looking for cannot be found.</h3>
		{if $places}
		<p>Perhaps you were looking for one of these fantastic places?</p>
		{/if}
	</div>
	{if $places}
	<div class="block">
		<ul class="block_listing column_3 small clear">
		{foreach from=$places item=place name=place}
			<li>
			{include file="shared/blocks/place_image_block.tpl"}
			</li>
		{/foreach}
		</ul>
	</div>
	{/if}
</div>
<div id="content_sec">
	{include file="shared/blocks/signup.tpl"}
	{include file="shared/blocks/specials.tpl"}
	{include file="shared/blocks/share.tpl"}
	{include file="shared/blocks/social.tpl"}
</div>