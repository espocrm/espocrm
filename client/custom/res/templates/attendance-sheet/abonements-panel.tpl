<div class="panel panel-default panel-overflow" style="position: relative">
    
    <div id="loaderBackground" style="visibility: hidden" class="abonements-panel-loader"></div>
    <div id="loaderSpinner" style="visibility: hidden" class="lds-default"><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div></div>

    <div class="panel-heading panel-header">
        <h4 class="panel-title" style='font-size: 1.2em'>
            <span class="fas fa-id-card" style="padding: 4px 5px 0 0;"></span>
            Абонементи
        </h4>
        <div>
            <span>
                <label>Всього: {{abonementsTotal}}</label>
                <label style='padding-left: 5px'>Присутні: {{marksTotal}}</label>
            </span>
        </div>
    </div>
    {{#ifEqual trainingId null}}
        <div class="text-soft center-align margin-bottom-2x">Заняття не обрано</div>
    {{else}}
        <div class="panel-body">
            {{#if abonementsTotal}}
                <table class="table table-hover">
                    <tr class="text-soft">
                        <th class="col-sm-1">Номер</th>
                        <th>Ім'я</th>
                        <th>Присутній</th>
                        <th>Залишилось</th>
                        <th>Статус</th>
                        <th>Дії</th>
                    </tr>
                    {{#each abonements}}
                        <tr>
                            <td class="cp text-soft">
                                {{this.number}}
                            </td>
                            <td>
                                <span data-id={{this.id}} class="abon-name cp highlight">{{this.name}}</span>
                            </td>
                            <td>
                                {{#if this.mark.id}}
                                    <input data-mark-id={{this.mark.id}} checked type="checkbox" class="form-checkbox mark"/>
                                {{else}}
                                    <input data-abonement-id={{this.id}} type="checkbox" class="form-checkbox mark"/>
                                {{/if}}
                            </td>
                            <td>{{this.classesLeft}}</td>
                            <td>
                                {{#if this.isActive}}
                                    <span title="Активний" class="cp text-muted fas fa-play-circle"></span>
                                {{/if}}
                                {{#if this.isPending}}
                                    <span title="Очікує" class="cp text-muted far fa-clock"></span>
                                {{/if}}
                                {{#if this.isEmpty}}
                                    <span title="Вичерпано" class="cp text-muted far fa-stop-circle"></span>
                                {{/if}}
                                {{#if this.isFreezed}}
                                    <span title="Заморожено" class="cp text-muted fas fa-snowflake"></span>
                                {{/if}}
                                {{#if this.note}}
                                    <span title="Переглянути замітку" data-id={{this.id}} class="cp text-muted fas fa-exclamation-circle"></span>
                                {{/if}}
                            </td>
                            <td class="nowrap">
                                <span title="Переглянути відмітки" data-id={{this.id}} class="cp highlight text-muted far fa-calendar"></span>
                                <span title="Оновити" class="btn-add cp highlight text-muted fas fa-sync-alt" data-id={{this.id}} data-action="recalculate"></span>
                            </td>
                        </tr>
                    {{/each}}
                    {{#each otherAbonements}}
                        <tr>
                            <td class="cp text-soft">
                                {{this.number}}
                            </td>
                            <td>
                                <span data-id={{this.id}} class="other-group cp highlight">{{this.name}}</span>
                                <span class="label label-default">Інша група</span>
                            </td>
                            <td>
                                {{#if this.mark.id}}
                                    <input data-mark-id={{this.mark.id}} checked type="checkbox" class="form-checkbox floating-mark"/>
                                {{else}}
                                    <input data-abonement-id={{this.id}} type="checkbox" class="form-checkbox"/>
                                {{/if}}
                            </td>
                            <td>{{this.classesLeft}}</td>
                            <td>
                                {{#if this.isActive}}
                                    <span title="Активний" class="cp text-muted fas fa-play-circle"></span>
                                {{/if}}
                                {{#if this.isPending}}
                                    <span title="Очікує" class="cp text-muted far fa-clock"></span>
                                {{/if}}
                                {{#if this.isEmpty}}
                                    <span title="Вичерпано" class="cp text-muted far fa-stop-circle"></span>
                                {{/if}}
                                {{#if this.isFreezed}}
                                    <span title="Заморожено" class="cp text-muted fas fa-snowflake"></span>
                                {{/if}}
                                {{#if this.note}}
                                    <span title="Переглянути замітку" data-id={{this.id}} class="cp text-muted fas fa-exclamation-circle"></span>
                                {{/if}}
                            </td>
                            <td class="nowrap">
                                <span title="Переглянути відмітки" data-id={{this.id}} class="cp highlight text-muted far fa-calendar"></span>
                                <span title="Оновити" class="btn-add cp highlight text-muted fas fa-sync-alt" data-id={{this.id}} data-action="recalculateOther"></span>
                            </td>
                        </tr>
                    {{/each}}
                </table>
            {{else}}
                <div class="text-soft center-align">Немає данних</div>
            {{/if}}
            <div class="abon-panel-buttons">
                <div class="margin-bottom" style="margin-right: 4px">
                    <button class="btn btn-sm btn-default btn-add" data-action="addOneTime">
                        <span class="fas fa-plus"></span>
                        Разовий
                    </button>
                    <button class="btn btn-sm btn-default btn-add" data-action="addTrial">
                        <span class="fas fa-plus"></span>
                        Пробний
                    </button>
                    <button class="btn btn-sm btn-default btn-add" data-action="addAbonement">
                        <span class="fas fa-plus"></span>
                        Абонемент
                    </button>
                </div>
                <div class="margin-bottom">
                    <button class="btn btn-sm btn-default btn-floating-mark">
                        <span class="fas fa-user-check"></span>
                        Інша група
                    </button>
                </div>
            </div>
        </div>
    {{/ifEqual}}
</div>