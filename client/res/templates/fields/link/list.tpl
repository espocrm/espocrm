{{#if idValue}}
	<a href="#{{foreignScope}}/view/{{idValue}}" title="{{nameValue}}">
{{#ifAttrNotEmpty model 'dept'}}
{{#ifAttrNotEmpty model 'accountActive'}}
{{nameValue}}
{{else}}
<span style="text-decoration:line-through;color:#999">{{nameValue}}</span>
{{/ifAttrNotEmpty}}
{{else}}
{{nameValue}}
{{/ifAttrNotEmpty}}
</a>
{{/if}}

