<div class="button-container">
{{#each buttonList}}
    {{button name label=label scope='Admin' style=style}}
{{/each}}
</div>

<style>
    #layout ul {
        width: 100%;
        min-height: 100px;
        padding: 0;
        list-style-type: none;
        margin: 0;
    }

    #layout ul li {
        list-style: none;
        border: 1px solid #CCC;
        margin: 5px 0;
        padding: 5px;
        height: 32px;
    }

    #layout header {
        font-weight: 600;
    }

    #layout ul > li .left {
        float: left;
    }

    #layout ul > li {
        background-color: #FFF;
    }

    #layout ul.enabled > li .right {
        float: right;
    }

    #layout ul.disabled > li .right {
        display: none;
    }

    #layout ul > li .width {
        font-size: small;
    }

    #layout ul.disabled > li .width {
        display: none;
    }

    #layout label {
        font-weight: normal;
    }

    .enabled li a.edit-field {
        display: none;
    }

    .enabled li:hover a.edit-field {
        display: block;
    }
</style>

<div id="layout" class="row">
    <div class="col-sm-5">
        <div class="well">
            <header>{{translate 'Enabled' scope='Admin'}}</header>
            <ul class="enabled connected">
                {{#each layout}}
                    <li class="cell" draggable="true" {{#each ../dataAttributeList}}data-{{toDom this}}="{{prop ../this this}}" {{/each}}>
                        <div class="left" style="width: calc(100% - 17px);">
                            <span>{{label}}</span>
                        </div>
                        {{#if ../editable}}
                        <div class="right" style="width: 17px;"><a href="javascript:" data-action="editItem" class="edit-field"><i class="fas fa-pencil-alt fa-sm"></i></a></div>
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
                    <li class="cell" draggable="true" {{#each ../dataAttributeList}}data-{{toDom this}}="{{prop ../this this}}" {{/each}}>
                        <div class="left" style="width: calc(100% - 17px);">
                            <span>{{label}}</span>
                        </div>
                        {{#if ../editable}}
                        <div class="right" style="width: 17px;"><a href="javascript:" data-action="editItem" class="edit-field"><i class="fas fa-pencil-alt fa-sm"></i></a></div>
                        {{/if}}
                    </li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>

