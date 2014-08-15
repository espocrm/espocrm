<div class="container content">
	<div class="col-md-4 col-md-offset-4">	
	<div id="login" class="panel panel-default">
		<div class="panel-heading">
			<img src="{{logoSrc}}"></img>
		</div>
		<div class="panel-body">
			<div>
				<form id="login-form">
					<div class="form-group">
						<label for="field-username">{{translate 'Username'}}</label>
						<input type="text" name="username" id="field-userName" class="form-control" autocapitalize="off" autocorrect="off">
					</div>
					<div class="form-group">
						<label for="login">{{translate 'Password'}}</label>
						<input type="password" name="password" id="field-password" class="form-control">
					</div>
					<div>
						<button type="button" class="btn btn-primary" id="btn-login">{{translate 'Login'}}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
	</div>
</div>
<footer class="container">{{{footer}}}</footer>
