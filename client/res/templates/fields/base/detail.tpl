{{#ifAttrNotEmpty model 'active'}}
    {{#ifAttrEquals model 'active' false}}
        <span style="text-decoration:line-through">{{value}}</span>
    {{else}}
        {{value}}
    {{/ifAttrEquals}}
{{else}}
    {{value}}
{{/ifAttrNotEmpty}}

