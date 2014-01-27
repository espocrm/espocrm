<?php

namespace Espo\EntryPoints;

use \Espo\Core\Exceptions\NotFound;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\BadRequest;

class Download extends \Espo\Core\EntryPoints\Base
{
	public static $authRequired = true;
	
	public function run()
	{
	
		$id = $_GET['id'];
		if (empty($id)) {
			throw new BadRequest();
		}
		
		$attachment = $this->getEntityManager()->getEntity('Attachment', $id);
		
		if (!$attachment) {
			throw new NotFound();
		}		
		
		if ($attachment->get('parentId') && $attachment->get('parentType')) {
			$parent = $this->getEntityManager()->getEntity($attachment->get('parentType'), $attachment->get('parentId'));			
			if (!$this->getAcl()->check($parent)) {
				throw new Forbidden();
			}
		}
		
		$fileName = "data/upload/{$attachment->id}";
		
		if (!file_exists($fileName)) {
			throw new NotFound();
		}			
		
		header('Content-Description: File Transfer');
		if ($attachment->get('type')) {
			header('Content-Type: ' . $attachment->get('type'));
		}
		header('Content-Disposition: attachment; filename=' . $attachment->get('name'));
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($fileName));
		ob_clean();
		flush();
		readfile($fileName);
		exit;		
	}	
}

