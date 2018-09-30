<?php

namespace Puzzle\Api\UserBundle\Controller;

use Puzzle\Api\UserBundle\Entity\Account;
use Puzzle\OAuthServerBundle\Controller\BaseFOSRestController;
use Puzzle\OAuthServerBundle\Service\Utils;
use Puzzle\OAuthServerBundle\Util\FormatUtil;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class AccountController extends BaseFOSRestController
{
    public function __construct() {
        parent::__construct();
        $this->fields = ['firstName', 'lastName', 'email', 'username', 'phone', 'gender', 'enabled', 'locked', 'accountExpiresAt'];
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/user/accounts")
     */
    public function getUserAccountsAction(Request $request) {
        $query = Utils::blameRequestQuery($request->query, $this->getUser());
        
        /** @var Puzzle\OAuthServerBundle\Service\Repository $repository */
        $repository = $this->get('papis.repository');
        $response = $repository->filter($query, Account::class, $this->connection);
        
        return $this->handleView(FormatUtil::formatView($request, $response));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Get("/user/accounts/{id}")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function getUserAccountAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->accessDenied($request));
        }
        
        return $this->handleView(FormatUtil::formatView($request, $account));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Post("/user/accounts")
     */
    public function postUserAction(Request $request) {
        $data = $request->request->all();
        
        /** @var Puzzle\Api\UserBundle\Entity\Account $account */
        $account = Utils::setter(new Account(), $this->fields, $data);
        
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine')->getManager($this->connection);
        $em->persist($account);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, $account));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\Vie()
     * @FOS\RestBundle\Controller\Annotations\Put("/user/accounts/{id}")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountAction(Request $request, Account $account) {
        $data = $request->request->all();
        
        /** @var Puzzle\Api\UserBundle\Entity\Account $account */
        $account = Utils::setter($account, $this->fields, $data);
        
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine')->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, $account));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/user/accounts/{id}/enable")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountEnableAction(Request $request, Account $account) {
        $account->setEnabled(true);
        
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine')->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, $account));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/user/accounts/{id}/disable")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountDisableAction(Request $request, Account $account) {
        $account->setEnabled(false);
        
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine')->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, $account));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/user/accounts/{id}/lock")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountLockAction(Request $request, Account $account) {
        $account->setLocked(true);
        
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine')->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, $account));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/user/accounts/{id}/unlock")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountUnlockAction(Request $request, Account $account) {
        $account->setLocked(false);
        
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->get('doctrine')->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, $account));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/user/accounts/{id}/add-roles")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountAddRolesAction(Request $request, Account $account) {
        $user = $this->getUser();
        
        if ($account->getCreatedBy()->getId() !== $user->getId()) {
            /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->badRequest($request));
        }
        
        $data = $request->request->all();
        if (isset($data['roles_to_add']) && count($data['roles_to_add']) > 0) {
            $rolesToAdd = $data['roles_to_add'];
            foreach ($rolesToAdd as $role) {
                $account->addRole($role);
            }
            
            $em = $this->get('doctrine')->getManager($this->connection);
            $em->flush();
            
            return $this->handleView(FormatUtil::formatView($request, $account));
        }
        
        return $this->handleView(FormatUtil::formatView($request, null, 204));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Put("/user/accounts/{id}/remove-roles")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountRemoveRolesAction(Request $request, Account $account) {
        $user = $this->getUser();
        
        if ($account->getCreatedBy()->getId() !== $user->getId()) {
            /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->badRequest($request));
        }
        
        $data = $request->request->all();
        if (isset($data['roles_to_remove']) && count($data['roles_to_remove']) > 0) {
            $rolesToRemove = $data['roles_to_remove'];
            foreach ($rolesToRemove as $role) {
                $account->removeRole($role);
            }
            
            $em = $this->get('doctrine')->getManager($this->connection);
            $em->flush();
            
            return $this->handleView(FormatUtil::formatView($request, $account));
        }
        
        return $this->handleView(FormatUtil::formatView($request, null, 204));
    }
    
    /**
     * @FOS\RestBundle\Controller\Annotations\View()
     * @FOS\RestBundle\Controller\Annotations\Delete("/users/{id}")
     * @Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function deleteUserAccountAction(Request $request, Account $account) {
        $user = $this->getUser();
        
        if ($account->getCreatedBy()->getId() !== $user->getId()) {
            /** @var Puzzle\OAuthServerBundle\Service\ErrorFactory $errorFactory */
            $errorFactory = $this->get('papis.error_factory');
            return $this->handleView($errorFactory->badRequest($request));
        }
        
        $em = $this->get('doctrine')->getManager($this->connection);
        $em->remove($account);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, null, 204));
    }
}