{foreach from=$__jsFiles item=file}
	{if $file.isSmart}
		<script type="text/javascript" src="{'get/js/name:'|cat:$file.path|cat:'/path:'|cat:$file.pagePath|glink}"></script>
	{else}
		<script type="text/javascript" src="{$file.path}"></script>
	{/if}
{/foreach}