<div class="side-banking-container">
    
    <div class="panel panel-default" style="border-left: 4px solid #f0ad4e; margin-bottom: 10px;">
        <div class="panel-heading" style="padding: 5px 10px; font-weight: bold;">
            <span class="fas fa-coins" style="color: #f0ad4e;"></span> Gold Loans
        </div>
        <div class="panel-body" style="padding: 10px;">
            {{#each banking.goldLoans}}
            <div style="margin-bottom: 5px;">
                <strong>{{id}}</strong>
                <span class="pull-right text-danger">{{amount}}</span>
                <div class="text-muted small">Rate: {{rate}}</div>
            </div>
            {{/each}}
        </div>
    </div>

    <div class="panel panel-default" style="border-left: 4px solid #5bc0de; margin-bottom: 10px;">
        <div class="panel-heading" style="padding: 5px 10px; font-weight: bold;">
            <span class="fas fa-certificate" style="color: #5bc0de;"></span> Fixed Deposits
        </div>
        <div class="panel-body" style="padding: 10px;">
            {{#each banking.fds}}
            <div style="margin-bottom: 8px; border-bottom: 1px dashed #eee; padding-bottom: 4px;">
                <strong>{{id}}</strong>
                <span class="pull-right">{{amount}}</span>
                <div class="text-muted small">Mat: {{maturity}}</div>
            </div>
            {{/each}}
        </div>
    </div>

    <div class="panel panel-default" style="border-left: 4px solid #5cb85c; margin-bottom: 10px;">
        <div class="panel-heading" style="padding: 5px 10px; font-weight: bold;">
            <span class="fas fa-piggy-bank" style="color: #5cb85c;"></span> Savings
        </div>
        <div class="panel-body" style="padding: 10px;">
            {{#each banking.savings}}
            <div>
                <strong>{{id}}</strong>
                <span class="pull-right text-success" style="font-weight:bold;">{{balance}}</span>
            </div>
            {{/each}}
        </div>
    </div>

</div>