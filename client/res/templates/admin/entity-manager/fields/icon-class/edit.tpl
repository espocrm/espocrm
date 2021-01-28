<div>
    <button
        class="btn btn-default pull-right btn-icon"
        data-action="selectIcon"
        title="{{translate 'Select'}}"
    ><span class="fas fa-angle-up"></span></button>
    <span style="vertical-align: middle;">
        {{#if value}}
        <span class="{{value}}"></span>
        {{else}}
        {{translate 'None'}}
        {{/if}}
    </span>
</div>