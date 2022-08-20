
<div class="dynamic-logic-options">
    <div class="dynamic-logic-options-list-container list-group">
        {{#each itemDataList}}
        <div class="list-group-item">
            <div class="clearfix option-list-item-header">
                <div class="pull-right">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="removeOptionList"
                        data-index="{{index}}"
                        class="remove-option-list"
                        title="{{translate 'Remove'}}"
                    >
                        <span class="fas fa-minus fa-sm"></span>
                    </a>
                </div>
            </div>
            <div>
                <div class="options-container" data-key="{{optionsViewKey}}">
                    {{{var optionsViewKey ../this}}}
                </div>
            </div>
            <div>
                <div class="pull-right">
                    <a
                        role="button"
                        tabindex="0"
                        data-action="editConditions"
                        data-index="{{index}}"
                    >{{translate 'Edit'}}</a>
                </div>
                <div class="string-container" data-key="{{conditionGroupViewKey}}">
                    {{{var conditionGroupViewKey ../this}}}
                </div>
            </div>
        </div>
        {{/each}}
    </div>
    <div>
        <a
            role="button"
            tabindex="0"
            data-action="addOptionList"
            title="{{translate 'Add'}}"
            class="add-option-list"
        ><span class="fas fa-plus fa-sm"></span></a>
    </div>
</div>
