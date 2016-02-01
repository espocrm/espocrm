    <div class="list-row-buttons pull-right">
        <div class="btn-group">
        <button type="button" class="btn btn-link btn-sm dropdown-toggle" data-toggle="dropdown">
            <span class="caret"></span>
        </button>
        <ul class="dropdown-menu pull-right">
            <li><a href="javascript:" class="action" data-action="quickRemove" data-id="{{model.id}}">{{translate 'Remove'}}</a></li>
        </ul>
        </div>
    </div>
{{#unless isRead}}
    <span class="badge-circle badge-circle-warning"></span>
{{/unless}}
