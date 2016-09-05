
{{translate operator category='logicalOperators' scope='Admin'}}
<div>
{{#each viewDataList}}
    <div data-view-key="{{key}}" style="margin-left: 30px;">{{{var key ../this}}}</div>
{{/each}}
</div>