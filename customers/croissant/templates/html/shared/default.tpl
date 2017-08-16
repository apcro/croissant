<!DOCTYPE html>
<html lang="en" class="js-disabled">
<head>
	<meta charset="UTF-8" />
	<meta name="title" content="{if $page_title}{$page_title} | {/if}" />
	<meta name="author" content="" />
	<meta name="description" content="{$page_meta}" />
	<meta name="Copyright" content="" />

	<title>{if $page_title}{$page_title} | {/if}</title>
	<link rel="shortcut icon" href="http://{$HTTP_HOST}/favicon.ico" />
	<!--[if IE 8]><link href="/css/ie8.css" rel="stylesheet" media="screen" /><![endif]-->
	<!--[if IE 7]><link href="/css/ie7.css" rel="stylesheet" media="screen" /><![endif]-->

	{* build css output *}
	{if $css_minified}
	<link rel="stylesheet" href="/css/{$css_minified}" />
	{else}
	<link rel="stylesheet" href="/css/croissant.css?rev={$smarty.const.SVN_REVISION}" />
	{if $css_files}
		{foreach from=$css_files item=css}
			<link rel="stylesheet" href="{$css}?rev={$smarty.const.SVN_REVISION}" />
		{/foreach}
	{/if}
	{/if}
	<script>
		docElement = this.document.documentElement;
		docElement.className = docElement.className.replace(/\bjs-disabled\b/,'') + ' js-enabled';
	</script>
	<!--[if lte IE 8]>
 		<script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script>
 	<![endif]-->

	{* Attach header JS files *}
	{* is this a place page? *}
	{if $js_minified_header}
		<script src="/js/{$js_minified_header}" type="text/javascript"></script>
	{else}
	{if $js_files_header}
	{foreach from=$js_files_header item=js}
		<script src="{$js}?rev={$smarty.const.SVN_REVISION}" type="text/javascript"></script>
	{/foreach}
	{*<noscript>Browser does not support javascript!</noscript>*}
	{/if}
	{/if}
</head>

<body class="{$body_class}">
	<noscript>
		<div id="noscript">For the best experience, please enable JavaScript</div>
	</noscript>

	{include file="$controller_template"}

	{* Attach footer JS files *}
	{if $js_minified_footer}
		<script src="/js/{$js_minified_footer}" type="text/javascript"></script>
	{else}
	{foreach from=$js_files_footer item=js}
		<script src="{$js}?rev={$smarty.const.SVN_REVISION}" type="text/javascript"></script>
	{/foreach}
	{/if}
	{if $debugout}
	{include file="shared/debug.tpl"}
	{/if}
	</body>
</html>