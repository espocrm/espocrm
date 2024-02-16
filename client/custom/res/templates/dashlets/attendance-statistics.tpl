<div class="row">
    <div class="col-sm-4">
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title" style="margin-left: 5px">
                    <span title="Попередній день" class="cp text-muted fas fa-angle-left date-nav highlight" data-action="prevDay"></span>
                    <span>
                        <input class="cp" type="date" id="date" name="date" value={{date}} 
                            min="2023-01-01" max="2050-12-31" style="border: none; border-bottom: 1px solid #E4E7F2; background-color: transparent" />
                    </span>
                    <span title="Наступний день" class="cp text-muted fas fa-angle-right date-nav highlight" data-action="nextDay"></span>
                </h4>
            </div>
            <div class="panel-body">
                <ul class="list-group">
                    {{#each trainers}}
                        <li data-id={{this.id}} data-name="{{this.name}}" class="trainer list-group-item cp">
                            {{this.name}}
                            <br>
                            {{{this.groupsHTML}}}
                        </li>
                    {{/each}}
                </ul>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        {{#if trainerId}}
        <div class="panel panel-default">
            <div class="panel-heading">
                <h4 class="panel-title" style="margin-left: 15px">
                    <span class="fas fa-user-circle" style="padding-top: 3px"></span>
                    <span>&nbsp{{trainerName}}</span>
                    &nbsp &nbsp
                    <span class="fas fa-calendar-day" style="padding-top: 2px"></span>
                    <span>&nbsp{{ dateSelected }}</span>
                </h4>
            </div>
            <div class="panel-body">
                <ul class="list-group">
                    <li class="list-group-item" style="overflow: auto;">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th class="cell-nowrap">
                                        <span class="fas fa-users"></span> Група
                                    </th>
                                    <th class="cell-nowrap">
                                        <span class="fas fa-user-check"></span> Присутність
                                    </th>
                                    <th class="cell-nowrap">
                                        <span class="fas fa-id-card"></span> Нові абонементи
                                    </th>
                                <tr>
                            </thead>
                            <tbody>
                                {{#each groups}}
                                <tr>
                                    <td class="cell-nowrap">{{ this.name }}</td>
                                    <td>{{ this.attendanceCount }}</td>
                                    <td>
                                        <span class="text-soft">звичайні:</span> {{ this.abonements.regular }}<br>
                                        <span class="text-soft">разові:  </span> {{ this.abonements.onetime }}<br>
                                        <span class="text-soft">пробні:  </span> {{ this.abonements.trial }}
                                    </td>
                                </tr>
                                {{/each}}
                                <tr>
                                    <td><span class="pull-right text-soft">Загалом:</span></td>
                                    <td>{{attandanceTotalCount}}</td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </li>
                </ul>
            </div>
        </div>
        {{else}}
            <div class="center-align margin-top-2x text-soft">Не обрано</div>
        {{/if}}
    </div>
</div>