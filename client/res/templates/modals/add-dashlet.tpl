<ul class="list-group array-add-list-group no-side-margin">
{{#each dashletList}}
    <li class="list-group-item clearfix">
        <a href="javascript:" class="add text-bold" data-name="{{./this}}">{{translate this category="dashlets"}}</a>
    </li>
{{/each}}
</ul>
