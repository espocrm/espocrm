<ul class="list-group array-add-list-group">
{{#each dashletList}}
    <li class="list-group-item clearfix">
        <a href="javascript:" class="add" data-name="{{./this}}">{{translate this category="dashlets"}}</a>
    </li>
{{/each}}
</ul>
