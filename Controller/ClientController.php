<?php

namespace Puzzle\Api\UserBundle\Controller;

use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use JMS\Serializer\SerializerInterface;
use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Entity\Client;
use Puzzle\OAuthServerBundle\Service\ErrorFactory;
use Puzzle\OAuthServerBundle\Service\Repository;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Oauth server client
 * 
 * @author qwincy <qwincypercy@fermentuse.com>
 *
 */
class ClientController extends BaseFOSRestController
{
    /**
     * @var ClientManagerInterface $clientManager
     */
    protected $clientManager;
    
    /**
     * @param RegistryInterface         $doctrine
     * @param Repository                $repository
     * @param SerializerInterface       $serializer
     * @param EventDispatcherInterface  $dispatcher
     * @param ErrorFactory              $errorFactory
     */
    public function __construct(
        RegistryInterface $doctrine,
        Repository $repository,
        SerializerInterface $serializer,
        EventDispatcherInterface $dispatcher,
        ErrorFactory $errorFactory,
        ClientManagerInterface $clientManager
    ){
        $this->clientManager = $clientManager;
        parent::__construct($doctrine, $repository, $serializer, $dispatcher, $errorFactory);
        $this->fields = ['name', 'host', 'allowedGrantTypes', 'redirectUris'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/clients")
	 */
	public function getOauthClientsAction(Request $request) {
	    $query = Utils::blameRequestQuery($request->query, $this->getUser());
	    $response = $this->repository->filter($query, Client::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/clients/{publicId}")
	 */
	public function getOauthClientAction(Request $request, $publicId) {
	    $client = $this->clientManager->findClientByPublicId($publicId);
	    
	    if (!$client) {
	        return $this->handleView($this->errorFactory->notFound($request));
	    }
	    
	    if ($client->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->accessDenied($request));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $client]));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/clients")
	 */
	public function postOauthClientAction(Request $request) {
		$data = $request->request->all();
		
		$client = $this->clientManager->createClient();
		
		$client = Utils::setter($client, $this->fields, $data);
		$client->setInterne(false);
		
		$this->clientManager->updateClient($client);
		
		$array = [
			'client_id' => $client->getRandomId(),
			'client_secret' => $client->getSecret(),
			'name' => $client->getName(),
			'redirect_uris' => $client->getRedirectUris(),
		    'grant_types' => $client->getAllowedGrantTypes()
		];
        
		return $this->handleView(FormatUtil::formatView($request, $array));
	}
	
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/clients/{publicId}")
	 */
	public function putOauthClientAction(Request $request, $publicId) {
	    $client = $this->clientManager->findClientByPublicId($publicId);
	    
	    if (!$client) {
	        return $this->handleView($this->errorFactory->notFound($request));
	    }
	    
	    if ($client->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->accessDenied($request));
	    }
		
	    $data = $request->request->all();
	    $client = Utils::setter($client, $this->fields, $data);
	    
	    $this->clientManager->updateClient($client);
		
		return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));	
	}
	
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/clients/{publicId}")
	 */
	public function deleteOauthClientAction(Request $request, $publicId) {
	    $client = $this->clientManager->findClientByPublicId($publicId);
	    
	    if (!$client) {
	        return $this->handleView($this->errorFactory->notFound($request));
	    }
	    
	    if ($client->getCreatedBy()->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->accessDenied($request));
	    }
		
		$em = $this->doctrine->getManager($this->connection);
		$em->remove($client);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
}