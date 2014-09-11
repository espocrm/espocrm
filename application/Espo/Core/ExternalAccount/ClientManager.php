<?php

namespace Espo\Core\ExternalAccount;

use \Espo\Core\Exceptions\Error;
use \Espo\Core\Exceptions\Forbidden;
use \Espo\Core\Exceptions\NotFound;

class ClientManager
{
	protected $entityManager;
	
	protected $metadata;
	
	protected $clientMap = array();
	
	public function __construct($entityManager, $metadata, $config)
	{
		$this->entityManager = $entityManager;
		$this->metadata = $metadata;
		$this->config = $config;
	}
	
	protected function getMetadata()
	{
		return $this->metadata;
	}
	
	protected function getEntityManager()
	{
		return $this->entityManager;
	}
	
	protected function getConfig()
	{
		return $this->config;
	}
	
	public function storeAccessToken($hash, $data)
	{
		if (!empty($this->clientMap[$hash]) && !empty($this->clientMap[$hash]['externalAccountEntity'])) {
			$externalAccountEntity = $this->clientMap[$hash]['externalAccountEntity'];
			$externalAccountEntity->set('accessToken', $data['accessToken']);
			$externalAccountEntity->set('tokenType', $data['tokenType']);
			$this->getEntityManager()->saveEntity($externalAccountEntity);
		}
	} 
	
	public function create($integration, $userId)
	{
		$authMethod = $this->getMetadata()->get("integrations.{$integration}.authMethod");		
		$methodName = 'create' . ucfirst($authMethod);		
		return $this->$methodName($integration, $userId);		
	}
	
	protected function createOAuth2($integration, $userId)
	{		
		$integrationEntity = $this->getEntityManager()->getEntity('Integration', $integration);
		$externalAccountEntity = $this->getEntityManager()->getEntity('ExternalAccount', $integration . '__' . $userId);
		
		$className = $this->getMetadata()->get("integrations.{$integration}.clientClassName");
		
		$redirectUri = $this->getConfig()->get('siteUrl') . '/oauthcallback'; // TODO move to client class
		
		if (!$externalAccountEntity) {
			throw new Error("External Account {$integration} not found for {$userId}");
		}
		
		if (!$integrationEntity->get('enabled')) {
			return null;
		}		
		if (!$externalAccountEntity->get('enabled')) {
			return null;
		}		
		
		$oauth2Client = new \Espo\Core\ExternalAccount\OAuth2\Client();		
				
		$client = new $className($oauth2Client, array(
			'endpoint' => $this->getMetadata()->get("integrations.{$integration}.params.endpoint"),
			'tokenEndpoint' => $this->getMetadata()->get("integrations.{$integration}.params.tokenEndpoint"),
			'clientId' => $integrationEntity->get('clientId'),
			'clientSecret' => $integrationEntity->get('clientSecret'),
			'redirectUri' => $redirectUri,
			'accessToken' => $externalAccountEntity->get('accessToken'),
			'refreshToken' => $externalAccountEntity->get('refreshToken'),
			'tokenType' => $externalAccountEntity->get('tokenType'),
		), $this);
		
		$this->addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId);
		
		return $client;		
	}
	
	protected function addToClientMap($client, $integrationEntity, $externalAccountEntity, $userId)
	{
		$this->clientMap[spl_object_hash($client)] = array(
			'client' => $client,
			'userId' => $userId,
			'integration' => $integrationEntity->id,
			'integrationEntity' => $integrationEntity,
			'externalAccountEntity' => $externalAccountEntity,
		);
	}
}

