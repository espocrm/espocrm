<ul class="list-group array-add-list-group">
{{#each dashletList}}
    <li class="list-group-item clearfix">
        {{translate this category="dashlets"}}
        <button class="btn btn-default pull-right add" data-name="{{./this}}">{{translate 'Add'}}</button>
    </li>
{{/each}}
</ul>
