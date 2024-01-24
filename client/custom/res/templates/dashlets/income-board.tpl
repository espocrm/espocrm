<div class="income-container">
    <div class="income-result">
        <div class="" style="padding-right: 5px">
            {{#if isProfit}}
                <span class="text-success" style="font-size: 1.5em">₴</span>
                <span class="text-3em text-success text-bold">{{ income }}</span>
            {{else}}
                <span class="text-danger" style="font-size: 1.5em">₴</span>
                <span class="text-3em text-danger text-bold">{{ income }}</span>
            {{/if}}
        </div>
        <div class="income-result-details" >
            <div class="text-success" style="margin-bottom: -5px;">{{ profit }}</div>
            <div class="text-danger">{{ spending }}</div>
        </div>
    </div>
</div>