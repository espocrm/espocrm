<div class="button-container">
    <div class="btn-group">
    {{#each buttonList}}
        {{button name label=label scope='Admin' style=style className='btn-xs-wide'}}
    {{/each}}
    </div>
</div>

<style>
    ul.enabled {
        li {
            &[data-hidden="true"] {
                .left {
                    color: var(--text-muted-color);
                }
            }
        }
    }


</style>

<div id="layout" class="row">
    <div class="col-sm-5">
        <div class="well enabled-well" tabindex="-1">
            <header>{{translate 'Enabled' scope='Admin'}}</header>
            <ul class="enabled connected">
                {{#each layout}}
                    <li
                        class="cell"
                        draggable="true"
                        {{#each ../dataAttributeList}}data-{{toDom this}}="{{prop ../this this}}" {{/each}}
                        title="{{labelText}}"
                    >
                        <div class="left" style="width: calc(100% - var(--17px));">
                            <span>{{labelText}}</span>
                        </div>
                        {{#if ../editable}}
                        {{#unless notEditable}}
                        <div class="right" style="width: 17px;"><a
                            role="button"
                            tabindex="0"
                            data-action="editItem"
                            class="edit-field"
                        ><i class="fas fa-pencil-alt fa-sm"></i></a></div>
                        {{/unless}}
                        {{/if}}
                    </li>
                {{/each}}
            </ul>
        </div>
    </div>
    <div class="col-sm-5">
        <div class="well">
            <header>{{translate 'Disabled' scope='Admin'}}</header>
            <ul class="disabled connected">
                {{#each disabledFields}}
                    <li
                        class="cell"
                        draggable="true"
                        {{#each ../dataAttributeList}}data-{{toDom this}}="{{prop ../this this}}" {{/each}}
                        title="{{labelText}}"
                    >
                        <div class="left" style="width: calc(100% - var(--17px));">
                            <span>{{labelText}}</span>
                        </div>
                        {{#if ../editable}}
                        {{#unless notEditable}}
                        <div class="right" style="width: 17px;"><a
                            role="button"
                            tabindex="0"
                            data-action="editItem"
                            class="edit-field"
                        ><i class="fas fa-pencil-alt fa-sm"></i></a></div>
                        {{/unless}}
                        {{/if}}
                    </li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>

