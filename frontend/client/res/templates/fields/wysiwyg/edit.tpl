
<link href="client/css/font-awesome.min.css" rel="stylesheet">
<link href="client/css/summernote.css" rel="stylesheet">

<textarea class="main-element form-control summernote" name="{{name}}" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} {{#if params.rows}} rows="{{params.rows}}"{{/if}}>{{value}}</textarea>
