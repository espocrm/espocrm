<?php

namespace Espo\Jobs;

use \Espo\Core\Exceptions;


class CheckInboundEmails extends \Espo\Core\Jobs\Base
{
	
	public function run()
	{	
		//some code		
		//for problems use Exceptions. In this case job status will be "Failed", otherwise "Success"
	}	
}

