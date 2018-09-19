<?php

namespace Puzzle\Api\UserBundle\Controller;

use Puzzle\OAuthServerBundle\Service\Repository;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;

use FOS\RestBundle\Controller\Annotations;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\View;
use FOS\RestBundle\Controller\FOSRestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Controller\Annotations\Put;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Puzzle\OAuthServerBundle\Entity\User;
use Puzzle\OAuthServerBundle\UserEvents;
use Puzzle\OAuthServerBundle\Event\UserEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Puzzle\OAuthServerBundle\Service\ErrorFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Service\Utils;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class UserController extends BaseFOSRestController
{
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
        ErrorFactory $errorFactory
    ){
        parent::__construct($doctrine, $repository, $serializer, $dispatcher, $errorFactory);
        $this->fields = ['firstName', 'lastName', 'email', 'username', 'phone', 'gender'];
    }
    
	/**
	 * @Annotations\View()
	 * @Get("/users")
	 */
	public function getUsersAction(Request $request) {
	    $response = $this->repository->filter($request->query, User::class, $this->connection);
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @Annotations\View()
	 * @Get("/users/me")
	 */
	public function getUserMeAction(Request $request) {
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $this->getUser()]));
	}
	
	/**
	 * @Annotations\View()
	 * @Get("/users/{id}")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function getUserAction(Request $request, User $user) {
	    return $this->handleView(FormatUtil::formatView($request, ['resources' => $user]));
	}
	
	/**
	 * @Annotations\View()
	 * @Post("/users")
	 */
	public function postUserAction(Request $request) {
		$data = $request->request->all();
		/** @var User $user */
		$user = Utils::setter(new User(), $this->fields, $data);
		$user->setPassword(hash('sha512', $data['password']));
		/** @var Doctrine\ORM\EntityManager $em */
		$em = $this->doctrine->getManager($this->connection);
		$em->persist($user);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, ['resources' => $user]));
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserAction(Request $request, User $user) {
	    $data = $request->request->all();
	    /** @var User $user */
	    $user = Utils::setter(new User(), $this->fields, $data);
	    /** @var Doctrine\ORM\EntityManager $em */
		$em = $this->doctrine->getManager($this->connection);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));	
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}/enable")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserEnableAction(Request $request, User $user) {
	    $user->setEnabled(true);
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}/disable")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserDisableAction(Request $request, User $user) {
	    $user->setEnabled(false);
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}/lock")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserLockAction(Request $request, User $user) {
	    $user->setLocked(true);
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}/unlock")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserUnlockAction(Request $request, User $user) {
	    $user->setLocked(false);
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->doctrine->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}/change-password")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserChangePasswordAction(Request $request, User $user) {
	    if ($user->getId() !== $this->getUser()->getId()) {
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $data = $request->request->all();
	    if (isset($data['password']) && $data['password'] !== null) {
	        $user->setPassword(hash('sha512', $data['password']));
	        /** @var Doctrine\ORM\EntityManager $em */
	        $em = $this->doctrine->getManager($this->connection);
	        $em->flush();
	        
	        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 304]));
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}/add-roles")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserAddRolesAction(Request $request, User $user) {
	    if ($user->getId() !== $this->getUser()->getId()){
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $data = $request->request->all();
	    if (isset($data['roles_to_add']) && count($data['roles_to_add']) > 0) {
	        $rolesToAdd = $data['roles_to_add'];
	        foreach ($rolesToAdd as $role) {
	            $user->addRole($role);
	        }
	        
	        $em = $this->doctrine->getManager($this->connection);
	        $em->flush();
	        
	        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 304]));
	}
	
	/**
	 * @Annotations\View()
	 * @Put("/users/{id}/remove-roles")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserRemoveRolesAction(Request $request, User $user) {
	    if ($user->getId() !== $this->getUser()->getId()){
	        return $this->handleView($this->errorFactory->badRequest($request));
	    }
	    
	    $data = $request->request->all();
	    if (isset($data['roles_to_remove']) && count($data['roles_to_remove']) > 0) {
	        $rolesToRemove = $data['roles_to_remove'];
	        foreach ($rolesToRemove as $role) {
	            $user->removeRole($role);
	        }
	        
	        $em = $this->doctrine->getManager($this->connection);
	        $em->flush();
	        
	        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, ['code' => 304]));
	}
	
	/**
	 * @Annotations\View()
	 * @Delete("/users/{id}")
	 * @ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function deleteUserAction(Request $request, User $user) {
		$em = $this->doctrine->getManager($this->connection);
		$em->remove($user);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
	}
}