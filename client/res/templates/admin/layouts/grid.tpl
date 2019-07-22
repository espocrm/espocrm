<style>
    #layout ul, #layout li, #layout .row {
        list-style: none;
        margin: 0;
        padding: 0;
    }
    #layout li {
        background-color: white;
    }

    #layout ul.panels li.panel {
        padding: 5px 10px;
        border: 1px solid #CCC;
        margin-bottom: 20px;
        min-height: 80px;
    }

    #layout ul.panels li.panel ul.rows,
    #layout ul.panels li.panel ul.rows > li {
        min-height: 30px;
    }

    #layout ul.panels li.panel ul.cells {
        margin: 5px;
        padding: 5px;
        border: 1px solid #CCC;
        min-height: 20px;
    }
    #layout ul.panels li.panel ul.cells > li {
        float: left;
    }

    #layout ul.panels li.panel ul.cells > li > div.cell {
        margin: 5px;
        padding: 5px;
        border: 1px solid #CCC;
        height: 32px;
    }

    #layout ul.panels li.panel[data-mode="column"] > ul > li {
        float: left;
    }
    #layout ul.panels li.panel[data-mode="column"] ul.cells > li {
        float: none;
    }

    #layout div.available-fields {
        height: 80%;
        width: 22%;
        overflow-x: hidden;
        overflow-y: scroll;
        position: fixed;
        right: 5px;
    }
    #layout > li,
    #layout div.available-fields li {
        border: 1px solid #CCC;
        margin: 5px;
        padding: 5px;
    }
    #layout > li a,
    #layout div.available-fields li a {
        display: none;
    }
</style>

<!-- Templates -->
<div id="layout-row-tpl" style="display: none;">
    <li>
        <div class="row">
          <ul class="cells clearfix">
              <div class="w-100 clearfix"><a href="javascript:" data-action="removeRow" class="remove-row pull-right"><i class="fas fa-times"></i></a></div>
              <a href="javascript:" data-action="addCell" class="add-cell"><i class="fas fa-plus"></i></a>
          </ul>
        </div>
    </li>
</div>
<!-- Templates End -->

<!-- Content -->
<div class="button-container">
  {{#each buttonList}}
  {{button name label=label scope='Admin' style=style}}
  {{/each}}
</div>

<div id="layout" class="row">
    <div class="col-md-8">
        <div class="well">
            <header>{{translate 'Layout' scope='LayoutManager'}}</header>
            <ul class="panels">
                {{#each panelDataList}}
                <li data-number="{{number}}" data-mode="{{mode}}" class="panel clearfix">
                    {{{var viewKey ../this}}}
                </li>
                {{/each}}
            </ul>

            <div><a href="javascript:;" data-action="addPanel">{{translate 'Add Panel' scope='Admin'}}</a></div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="available-fields well">
            <header>{{translate 'Available Fields' scope='Admin'}}</header>
            <ul class="disabled cells clearfix">
                {{#each disabledFields}}
                    <li class="draggable">
                        <div class="cell" data-name="{{./this}}">{{translate this scope=../scope category='fields'}}
                            &nbsp;<a href="javascript:" data-action="removeField" class="remove-field pull-right"><i class="fas fa-times"></i></a>
                        </div>
                    </li>
                {{/each}}
            </ul>
        </div>
    </div>
</div>
<!-- Content End -->