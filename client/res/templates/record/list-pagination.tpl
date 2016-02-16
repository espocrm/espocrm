
<ul class="pagination pagination-sm">
    <li {{#unless previous}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="first"> <i class="glyphicon glyphicon-fast-backward"></i> </a> 
    </li>
    <li {{#unless previous}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="previous"> <i class="glyphicon glyphicon-backward"></i> </a> 
    </li>
    <li>
        <a href="javascript:" data-page="current"> {{from}} - {{to}} {{translate 'of'}} {{total}} </a>
    </li>
    <li {{#unless next}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="next"> <i class="glyphicon glyphicon-forward"></i> </a> 
    </li>
    <li {{#unless next}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="last"> <i class="glyphicon glyphicon-fast-forward"></i> </a>
    </li>
</ul>


