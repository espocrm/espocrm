<?php

namespace Espo\Utils\Api;

use \Slim\Slim,
	\Espo\Utils as Utils,
	\Espo\Utils\Api as Api;


class Rest
{

	/**
    * Index page of API
	*
	* @return void
	*/
	function main()
	{
	    $template = <<<EOT
	            <h1>Main Page of REST API!!!</h1>
EOT;
	    Api\Helper::output($template);
	}

	public function getAppUser()
	{
		$data= '{"user":{"modified_by_name":"Administrator","created_by_name":"","id":"1","user_name":"admin","user_hash":"","system_generated_password":"0","pwd_last_changed":"","authenticate_id":"","sugar_login":"1","first_name":"","last_name":"Administrator","full_name":"Administrator","name":"Administrator","is_admin":"1","external_auth_only":"0","receive_notifications":"1","description":"","date_entered":"2013-06-13 12:18:44","date_modified":"2013-06-13 12:19:48","modified_user_id":"1","created_by":"","title":"Administrator","department":"","phone_home":"","phone_mobile":"","phone_work":"","phone_other":"","phone_fax":"","status":"Active","address_street":"","address_city":"","address_state":"","address_country":"","address_postalcode":"","UserType":"","deleted":"0","portal_only":"0","show_on_employees":"1","employee_status":"Active","messenger_id":"","messenger_type":"","reports_to_id":"","reports_to_name":"","email1":"test@letrium.com","email_link_type":"","is_group":"0","c_accept_status_fields":" ","m_accept_status_fields":" ","accept_status_id":"","accept_status_name":""},"preferences":{}}';
        return Api\Helper::output($data, 'Cannot login');
	}

	/**
    * Get whole metadata
	*
	* @return void
	*/
	public function getMetadata()
	{
    	global $base;
		$devMode= !$base->config->get('useCache');

		$metadata= new Utils\Metadata();
		$data= $metadata->getMetadata(true, $devMode);

       	return Api\Helper::output($data, 'Cannot reach metadata data');
	}

	/**
    * Put the metadata
	* ex. metadata/menu/Account
	*
	* @return void
	*/
	public function putMetadata($type, $scope)
	{
		$app= Slim::getInstance();
		$data = $app->request()->getBody();

		$metadata = new Utils\Metadata();
		$result = $metadata->setMetadata($data, $type, $scope);

		if ($result===false) {
        	return self::output($result, 'Cannot save metadata data');
        }

        $data= $metadata->getMetadata(true, true);
        return Api\Helper::output($data, 'Cannot get the metadata data');
	}

	/**
    * Get whole settigs
	*
	* @return void
	*/
	public function getSettings()
	{
		global $base;
		$config= new Utils\Configurator();

		$isAdmin= false;
		if(isset($base->currentUser) && is_object($base->currentUser)) {
        	$isAdmin= $base->currentUser->isAdmin();
		}

		$data= $config->getJSON($isAdmin);

        return Api\Helper::output($data, 'Cannot get settings');
	}

	/**
    * Add or change settigs
	*
	* @return void
	*/
	public function patchSettings()
	{
		global $base;
		$config= new Utils\Configurator();

		$isAdmin= false;
		if(isset($base->currentUser) && is_object($base->currentUser)) {
        	$isAdmin= $base->currentUser->isAdmin();
		}

		$app= Slim::getInstance();
		$data = $app->request()->getBody();

		$result= $config->setJSON($data, $isAdmin);

        if ($result===false) {
        	return self::output($result, 'Cannot save settings');
        }

        $data= $config->getJSON($isAdmin);
        return Api\Helper::output($data, 'Cannot get settings');
	}



	/**
    * Get requested layout
	*
	* @return void
	*/
	/*public function getLayout($controller, $name)
	{
    	$ControllerManager = new Utils\Controllers\Manager();

		$params = array('controller' => $controller, 'action' => $name);
        $action= 'getLayout';
		$result = $ControllerManager->call($action, $params, $data);

		return Api\Helper::output($result->data, $result->errMessage, $result->errCode);

		//$layout = new Utils\Layout();
		//$data = $layout->getLayout($controller, $name);

        //return Api\Helper::output($data, 'Cannot get this layout', 404);
	} */

}


?>