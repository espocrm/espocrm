<h3>Информация о Вашей учетной записи</h3>

<p>Имя пользователя: {{userName}}</p>
<p>Пароль: {{password}}</p>

{{#each siteUrlList}}
<p><a href="{{./this}}">{{./this}}</a></p>
{{/each}}