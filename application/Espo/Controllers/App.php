<?php

namespace Espo\Controllers;

class App extends \Espo\Core\Controllers\Record
{
	public function actionUser()
	{
		return '{"user":{"modified_by_name":"Administrator","created_by_name":"","id":"1","user_name":"admin","user_hash":"","system_generated_password":"0","pwd_last_changed":"","authenticate_id":"","sugar_login":"1","first_name":"","last_name":"Administrator","full_name":"Administrator","name":"Administrator","is_admin":"1","external_auth_only":"0","receive_notifications":"1","description":"","date_entered":"2013-06-13 12:18:44","date_modified":"2013-06-13 12:19:48","modified_user_id":"1","created_by":"","title":"Administrator","department":"","phone_home":"","phone_mobile":"","phone_work":"","phone_other":"","phone_fax":"","status":"Active","address_street":"","address_city":"","address_state":"","address_country":"","address_postalcode":"","UserType":"","deleted":"0","portal_only":"0","show_on_employees":"1","employee_status":"Active","messenger_id":"","messenger_type":"","reports_to_id":"","reports_to_name":"","email1":"test@letrium.com","email_link_type":"","is_group":"0","c_accept_status_fields":" ","m_accept_status_fields":" ","accept_status_id":"","accept_status_name":""},"preferences":{}}';
	}
	

}
