{{#each emailAddressData}}
	<div>
	{{#unless invalid}}{{#unless optOut}}
	<a href="javascript:" data-email-address="{{emailAddress}}" data-action="mailTo">
	{{/unless}}{{/unless}}	
	
	<span {{#if invalid}}style="text-decoration: line-through;"{{/if}}>	
	{{emailAddress}}	
	</span>
	
	{{#unless invalid}}{{#unless optOut}}
	</a>
	{{/unless}}{{/unless}}
	</div>
{{/each}}
