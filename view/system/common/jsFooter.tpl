<!-- Start JavaScript -->

{*{foreach from=$__customJsFiles item=file}
	<script type="text/javascript" src="{$file}"></script>
{/foreach}
<script type="text/javascript" src="{'get/min/files:'|cat:$__jsFiles|glink}"></script>*}


{foreach from=$__jsFiles item=file}
	{if $file.isSmart}
		<script type="text/javascript" src="{'get/js/name:'|cat:$file.path|cat:'/path:'|cat:$file.pagePath|glink}"></script>
	{else}
		<script type="text/javascript" src="{$file.path}"></script>
	{/if}
{/foreach}

{getInlineJS}
<!-- End Of JavaScript -->