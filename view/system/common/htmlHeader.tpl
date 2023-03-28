<!-- CSS -->
{*<link rel="stylesheet" href="{'get/min/files:'|cat:$__cssFiles|glink}" type="text/css" >
{foreach from=$__customCssFiles item=file}
	<link rel="stylesheet" href="{$file}" type="text/css" >
{/foreach}*}
{foreach from=$__cssFiles item=file}
	{if $file.isSmart}
		<link rel="stylesheet" href="{'get/css/name:'|cat:$file.path|cat:'/path:'|cat:$file.pagePath|glink}" type="text/css" >
	{else}
		<link rel="stylesheet" href="{$file.path}" type="text/css" >
	{/if}
{/foreach}
<!-- End Of CSS -->

<!-- Start JavaScript -->

{*<script type="text/javascript" src="{'get/min/files:'|cat:$__jsFiles|glink}"></script>
{foreach from=$__customJsFiles item=file}
	<script type="text/javascript" src="{$file}"></script>
{/foreach}*}

<script type="text/javascript">
	var SITE_PATH = '{$__sitePath}';
	var TEMPLATE_PATH = '{$__CurrentTemplatePath}';
	var IMG_PATH = TEMPLATE_PATH + 'img/';
</script>
{*foreach from=$__jsFiles item=file}
	{if $file.isSmart}
		<script type="text/javascript" src="{'get/js/name:'|cat:$file.path|cat:'/path:'|cat:$file.pagePath|glink}"></script>
	{else}
		<script type="text/javascript" src="{$file.path}"></script>
	{/if}
{/foreach*}
<!-- End Of JavaScript -->

<title>{$__pageTitle}</title>

{if !empty($__pageDescription)}
<meta name="description" content="{$__pageDescription}" >
{/if}
{if !empty($__pageKeywords)}
<meta name="keywords" content="{$__pageKeywords}" >
{/if}

<!-- Custom Head Tags -->
{foreach from=$__CustomHeadTags item=tag}
	{$tag}	
{/foreach}
<!-- Custom Head Tags -->