<?php

namespace Puzzle\Api\UserBundle\Controller;

use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Entity\Client;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * Oauth server client
 * 
 * @author qwincy <qwincypercy@fermentuse.com>
 *
 */
class ClientController extends BaseFOSRestController
{
    public function __construct() {
        parent::__construct();
        $this->fields = ['name', 'host', 'allowedGrantTypes', 'redirectUris'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/clients")
	 */
	public function getOauthClientsAction(Request $request) {
	    $query = Utils::blameRequestQuery($request->query, $this->getUser());
	    
	    /** @var Puzzle\OAuthServerBundle\Service\Repository $repository */
	    $repository = $this->get('papis.repository');
	    $response = $repository->filter($query, Client::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/clients/{publicId}")
	 */
	public function getOauthClientAction(Request $request, $publicId) {
	    /** @var FOS\OAuthServerBundle\Entity\ClientManager $clientManager */
	    $clientManager = $this->get('fos_oauth_server.client_manager.default');
	    
	    /** @var Puzzle\OAuthServerBundle\Entity\CLient $client */
	    $client = $clientManager->findClientByPublicId($publicId);
	    
	    /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	    $errorFactory = $this->get('papis.error_factory');
	    
	    if (!$client) {
	        return $this->handleView($errorFactory->notFound($request));
	    }
	    
	    if ($client->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($errorFactory->accessDenied($request));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, $client));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/clients")
	 */
	public function postOauthClientAction(Request $request) {
		$data = $request->request->all();
		
		/** @var FOS\OAuthServerBundle\Entity\ClientManager $clientManager */
		$clientManager = $this->get('fos_oauth_server.client_manager.default');
		
		/** @var Puzzle\OAuthServerBundle\Entity\Client $client */
		$client = $clientManager->createClient();
		$client = Utils::setter($client, $this->fields, $data);
		$client->setInterne(false);
		
		$clientManager->updateClient($client);
		
		return $this->handleView(FormatUtil::formatView($request, [
		    'client_id' => $client->getRandomId(),
		    'client_secret' => $client->getSecret(),
		    'name' => $client->getName(),
		    'redirect_uris' => $client->getRedirectUris(),
		    'grant_types' => $client->getAllowedGrantTypes()
		]));
	}
	
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/clients/{publicId}")
	 */
	public function putOauthClientAction(Request $request, $publicId) {
	    /** @var FOS\OAuthServerBundle\Entity\ClientManager $clientManager */
	    $clientManager = $this->get('fos_oauth_server.client_manager.default');
	    
	    /** @var Puzzle\OAuthServerBundle\Entity\Client $client */
	    $client = $clientManager->findClientByPublicId($publicId);
	    
	    /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	    $errorFactory = $this->get('papis.error_factory');
	    
	    if (!$client) {
	        return $this->handleView($errorFactory->notFound($request));
	    }
	    
	    if ($client->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($errorFactory->accessDenied($request));
	    }
		
	    $data = $request->request->all();
	    
	    /** @var Puzzle\OAuthServerBundle\Entity\Client $client */
	    $client = Utils::setter($client, $this->fields, $data);
	    
	    $this->clientManager->updateClient($client);
		
	    return $this->handleView(FormatUtil::formatView($request, [
	        'client_id' => $client->getRandomId(),
	        'client_secret' => $client->getSecret(),
	        'name' => $client->getName(),
	        'redirect_uris' => $client->getRedirectUris(),
	        'grant_types' => $client->getAllowedGrantTypes()
	    ]));	
	}
	
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/clients/{publicId}")
	 */
	public function deleteOauthClientAction(Request $request, $publicId) {
	    /** @var FOS\OAuthServerBundle\Entity\ClientManager $clientManager */
	    $clientManager = $this->get('fos_oauth_server.client_manager.default');
	    
	    /** @var Puzzle\OAuthServerBundle\Entity\CLient $client */
	    $client = $clientManager->findClientByPublicId($publicId);
	    
	    /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
	    $errorFactory = $this->get('papis.error_factory');
	    
	    if (!$client) {
	        return $this->handleView($errorFactory->notFound($request));
	    }
	    
	    if ($client->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($errorFactory->accessDenied($request));
	    }
		
		$em = $this->get('doctrine')->getManager($this->connection);
		$em->remove($client);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
}