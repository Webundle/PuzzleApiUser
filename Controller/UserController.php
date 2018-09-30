<?php

namespace Puzzle\Api\UserBundle\Controller;

use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Entity\User;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 * 
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class UserController extends BaseFOSRestController
{
    public function __construct() {
        parent::__construct();
        $this->fields = ['firstName', 'lastName', 'email', 'username', 'phone', 'gender'];
    }
    
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/users")
	 */
	public function getUsersAction(Request $request) {
	    /** @var Puzzle\OAuthServerBundle\Service\Repository $repository */
	    $repository = $this->get('papis.repository');
	    $response = $repository->filter($request->query, User::class, $this->connection);
	    
	    return $this->handleView(FormatUtil::formatView($request, $response));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/users/me")
	 */
	public function getUserMeAction(Request $request) {
	    return $this->handleView(FormatUtil::formatView($request, $this->getUser()));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Get("/users/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function getUserAction(Request $request, User $user) {
	    return $this->handleView(FormatUtil::formatView($request, $user));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Post("/users")
	 */
	public function postUserAction(Request $request) {
		$data = $request->request->all();
		
		/** @var Puzzle\OAuthServerBundle\Entity\User $user */
		$user = Utils::setter(new User(), $this->fields, $data);
		$user->setPassword(hash('sha512', $data['password']));
		
		/** @var Doctrine\ORM\EntityManager $em */
		$em = $this->get('doctrine')->getManager($this->connection);
		$em->persist($user);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, $user));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\Vie()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserAction(Request $request, User $user) {
	    $data = $request->request->all();
	    
	    /** @var Puzzle\OAuthServerBundle\Entity\User $user */
	    $user = Utils::setter(new $user, $this->fields, $data);
	    
	    /** @var Doctrine\ORM\EntityManager $em */
		$em = $this->get('doctrine')->getManager($this->connection);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, $user));	
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/enable")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserEnableAction(Request $request, User $user) {
	    $user->setEnabled(true);
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $user));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/disable")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserDisableAction(Request $request, User $user) {
	    $user->setEnabled(false);
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $user));
	}
	
	/**
	 * @Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/lock")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserLockAction(Request $request, User $user) {
	    $user->setLocked(true);
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $user));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/unlock")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserUnlockAction(Request $request, User $user) {
	    $user->setLocked(false);
	    
	    /** @var Doctrine\ORM\EntityManager $em */
	    $em = $this->get('doctrine')->getManager($this->connection);
	    $em->flush();
	    
	    return $this->handleView(FormatUtil::formatView($request, $user));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/change-password")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserChangePasswordAction(Request $request, User $user) {
	    $data = $request->request->all();
	    if (isset($data['password']) && $data['password'] !== null) {
	        $user->setPassword(hash('sha512', $data['password']));
	        
	        /** @var Doctrine\ORM\EntityManager $em */
	        $em = $this->get('doctrine')->getManager($this->connection);
	        $em->flush();
	        
	        return $this->handleView(FormatUtil::formatView($request, $user));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/add-roles")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserAddRolesAction(Request $request, User $user) {
	    $data = $request->request->all();
	    if (isset($data['roles_to_add']) && count($data['roles_to_add']) > 0) {
	        $rolesToAdd = $data['roles_to_add'];
	        foreach ($rolesToAdd as $role) {
	            $user->addRole($role);
	        }
	        
	        $em = $this->get('doctrine')->getManager($this->connection);
	        $em->flush();
	        
	        return $this->handleView(FormatUtil::formatView($request, $user));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Put("/users/{id}/remove-roles")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function putUserRemoveRolesAction(Request $request, User $user) {
	    $data = $request->request->all();
	    if (isset($data['roles_to_remove']) && count($data['roles_to_remove']) > 0) {
	        $rolesToRemove = $data['roles_to_remove'];
	        foreach ($rolesToRemove as $role) {
	            $user->removeRole($role);
	        }
	        
	        $em = $this->get()->getManager($this->connection);
	        $em->flush();
	        
	        return $this->handleView(FormatUtil::formatView($request, $user));
	    }
	    
	    return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
	
	/**
	 * @FOS\RestBundle\Controller\Annotations\View()
	 * @FOS\RestBundle\Controller\Annotations\Delete("/users/{id}")
	 * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("user", class="PuzzleOAuthServerBundle:User")
	 */
	public function deleteUserAction(Request $request, User $user) {
		$em = $this->get('doctrine')->getManager($this->connection);
		$em->remove($user);
		$em->flush();
		
		return $this->handleView(FormatUtil::formatView($request, null, 204));
	}
}