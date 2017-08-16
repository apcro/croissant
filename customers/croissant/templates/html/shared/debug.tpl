	<div style="clear:both"></div>
	<div id="debugoutput">
		<div class="debug_bar">
			{$debug_totaltime} / {$memused}Mb
			<div class="debug_popout">
				{* debug *}
				{$debugout}
				<b>SQL queries: </b>
				<ol>
					{if $debugqueries}
						{foreach from=$debugqueries item=query name=debugout}
							<li class="debug_bg{$smarty.foreach.debugout.iteration%2}">{$query}</li>
						{/foreach}
					{else}
						<li class="debug_bg">No queries called</li>
					{/if}
				</ol>
				<b>Full debuglog</b>
				<ol>
					{if $fulldebug}
						{foreach from=$fulldebug item=query name=debugout}
						<li class="debug_bg{$smarty.foreach.debugout.iteration%2}">
						Spent <b>{$query.time}ms</b> calling these queries:
						<ul>
							{foreach from=$query.sql item=subquery name=query2}
								<li class="debug_bg{$smarty.foreach.debugout.iteration%2}" style="margin-left: 20px">{$subquery}</li>
							{/foreach}
						</ul>
						Used {$query.memory} bytes of RAM
						</li>
						{/foreach}
					{else}
						<li class="debug_bg">No queries called</li>
					{/if}
				</ol>
			</div>
		</div>
	</div>