<div class="row dynamic-logic-edit-item-row">
    <div class="col-sm-2">{{{translate field category='fields' scope=scope}}}</div>
    <div class="col-sm-3">
        <select data-name="type" class="form-control">{{{options typeList type scope='DynamicLogic' field='operators'}}}</select>
    </div>
    <div class="col-sm-4 value-container">{{{value}}}</div>
    <div class="col-sm-2">
        <a class="pull-right" href="javascript:" data-action="remove"><span class="fas fa-times"></span></a>
        <span>{{translate operator category='logicalOperators' scope='Admin'}}</span>
    </div>
</div>