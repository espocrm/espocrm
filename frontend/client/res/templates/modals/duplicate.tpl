<h4>{{translate 'duplicate' category="messages"}}</h4>

<div style="margin-top: 20px;">
    <table class="table">
    {{#each duplicates}}
        <tr>
            <td>
                <a href="#{{../scope}}/view/{{@key}}">{{./this}}</a>
            </td>
        </tr>
    {{/each}}
    </table>
</div>
