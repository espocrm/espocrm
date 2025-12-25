<div class="banking-360-grid" style="margin-top: 10px;">
    <div class="row">
        <div class="col-md-4 col-sm-12"></div>

        <div class="col-md-4 col-sm-12">
            <div class="panel panel-default" style="border-top: 3px solid #d9534f;">
                <div class="panel-heading" style="color: #d9534f; font-weight: bold; background:#fff;">
                    Cases ({{cases.length}})
                </div>
                <div class="panel-body" style="max-height: 200px; overflow-y: auto;">
                    {{#each cases}}
                    <div style="border-bottom: 1px solid #eee; margin-bottom: 5px;">
                        <a href="#Case/view/{{id}}"><strong>{{name}}</strong></a><br>
                        <small>{{status}} | {{number}}</small>
                    </div>
                    {{/each}}
                </div>
            </div>
            <div class="panel panel-default" style="border-top: 3px solid #00a1e0;">
                <div class="panel-heading" style="background:#fff;">Debit Cards</div>
                <div class="panel-body">
                    {{#each banking.debitCards}}
                    <p>{{number}} <span class="pull-right badge">{{status}}</span></p>
                    {{/each}}
                </div>
            </div>
        </div>

        <div class="col-md-4 col-sm-12">
             <div class="panel panel-default" style="border-top: 3px solid #00a1e0;">
                <div class="panel-heading" style="background:#fff;">Saving Account</div>
                <div class="panel-body">
                    {{#each banking.savings}}
                    <p><strong>{{number}}</strong> <span class="pull-right">{{balance}}</span></p>
                    {{/each}}
                </div>
            </div>
        </div>
    </div>
</div>