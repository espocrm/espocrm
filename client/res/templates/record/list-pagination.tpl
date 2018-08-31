
<ul class="pagination pagination-sm">
    <li {{#unless previous}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="first"> <i class="fas fa-fast-backward"></i> </a>
    </li>
    <li {{#unless previous}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="previous"> <i class="fas fa-backward"></i> </a>
    </li>
    <li>
        <a href="javascript:" data-page="current"> {{from}} - {{to}} {{translate 'of'}} {{total}} </a>
    </li>
    <li {{#unless next}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="next"> <i class="fas fa-forward"></i> </a>
    </li>
    <li {{#unless next}}class="disabled"{{/unless}}>
        <a href="javascript:" data-page="last"> <i class="fas fa-fast-forward"></i> </a>
    </li>
</ul>


