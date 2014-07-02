{{#if phoneNumberData}}
	{{#each phoneNumberData}}
		<div>
			<a href="tel:{{phoneNumber}}" data-phone-number="{{phoneNumber}}" data-action="dial">{{phoneNumber}}</a>
			<span class="text-muted text-small"></span>
		</div>
	{{/each}}
{{else}}
	<a href="tel:{{value}}" data-phone-number="{{value}}" data-action="dial">{{value}}</a>
{{/if}}
