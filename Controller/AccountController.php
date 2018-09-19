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
use FOS\OAuthServerBundle\Model\ClientManagerInterface;
use Puzzle\Api\UserBundle\Entity\Account;

/**
 *
 * @author AGNES Gnagne Cedric <cecenho55@gmail.com>
 *
 */
class AccountController extends BaseFOSRestController
{
    /**
     * 
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
        parent::__construct($doctrine, $repository, $serializer, $dispatcher, $errorFactory);
        $this->fields = ['firstName', 'lastName', 'email', 'username', 'phone', 'client'];
        $this->clientManager = $clientManager;
    }
    
    /**
     * @Annotations\View()
     * @Get("/accounts")
     */
    public function getUserAccountsAction(Request $request) {
        $publicId = $request->query->get('client_id');
        
        if (!$publicId || $client = $this->clientManager->findClientByPublicId($publicId)) {
            return $this->handleView($this->errorFactory->accessDenied($request, ''));
        }
        
        $query = Utils::blameRequestQuery($request->query, $this->getUser());
        $query->set('filter', $query->get('filter').',client=='.$client->getId());
        $response = $this->repository->filter($query, User::class, $this->connection);
        
        return $this->handleView(FormatUtil::formatView($request, $response));
    }
    
    /**
     * @Annotations\View()
     * @Get("/accounts/{id}")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function getUserAccountAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->accessDenied($request));
        }
        
        return $this->handleView(FormatUtil::formatView($request, ['resources' => $account]));
    }
    
    /**
     * @Annotations\View()
     * @Post("/accounts")
     */
    public function postUserAccountAction(Request $request) {
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->doctrine->getManager($this->connection);
        
        $data = $request->request->all();
        $data['client'] = $this->clientManager->findClientByPublicId($data['client']);
        /** @var Account $account */
        $account = Utils::setter(new Account(), $this->fields, $data);
        
        $em->persist($account);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, ['resources' => $account]));
    }
    
    /**
     * @Annotations\View()
     * @Put("/accounts/{id}")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->accessDenied($request));
        }
        
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->doctrine->getManager($this->connection);
        
        $data = $request->request->all();
        $data['client'] = $this->clientManager->findClientByPublicId($data['client']);
        /** @var Account $account */
        $account = Utils::setter(new Account(), $this->fields, $data);
        
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
    }
    
    /**
     * @Annotations\View()
     * @Put("/accounts/{id}/enable")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountEnableAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->badRequest($request));
        }
        
        $account->setEnabled(true);
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->doctrine->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
    }
    
    /**
     * @Annotations\View()
     * @Put("/accounts/{id}/disable")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountDisableAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->badRequest($request));
        }
        
        $account->setEnabled(false);
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->doctrine->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
    }
    
    /**
     * @Annotations\View()
     * @Put("/accounts/{id}/lock")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountLockAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->badRequest($request));
        }
        
        $account->setLocked(true);
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->doctrine->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
    }
    
    /**
     * @Annotations\View()
     * @Put("/accounts/{id}/unlock")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountUnlockAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->badRequest($request));
        }
        
        $account->setLocked(false);
        /** @var Doctrine\ORM\EntityManager $em */
        $em = $this->doctrine->getManager($this->connection);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
    }
    
    /**
     * @Annotations\View()
     * @Put("/accounts/{id}/add-roles")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountAddRolesAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->badRequest($request));
        }
        
        $data = $request->request->all();
        if (isset($data['roles_to_add']) && count($data['roles_to_add']) > 0) {
            $rolesToAdd = $data['roles_to_add'];
            foreach ($rolesToAdd as $role) {
                $account->addRole($role);
            }
            
            $em = $this->doctrine->getManager($this->connection);
            $em->flush();
            
            return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
        }
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 304]));
    }
    
    /**
     * @Annotations\View()
     * @Put("/accounts/{id}/remove-roles")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function putUserAccountRemoveRolesAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->badRequest($request));
        }
        
        $data = $request->request->all();
        if (isset($data['roles_to_remove']) && count($data['roles_to_remove']) > 0) {
            $rolesToRemove = $data['roles_to_remove'];
            foreach ($rolesToRemove as $role) {
                $account->removeRole($role);
            }
            
            $em = $this->doctrine->getManager($this->connection);
            $em->flush();
            
            return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
        }
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 304]));
    }
    
    /**
     * @Annotations\View()
     * @Delete("/accounts/{id}")
     * @ParamConverter("account", class="PuzzleApiUserBundle:Account")
     */
    public function deleteUserAccountAction(Request $request, Account $account) {
        if ($account->getCreatedBy()->getId() !== $this->getUser()->getId()) {
            return $this->handleView($this->errorFactory->badRequest($request));
        }
        
        $em = $this->doctrine->getManager($this->connection);
        $em->remove($account);
        $em->flush();
        
        return $this->handleView(FormatUtil::formatView($request, ['code' => 200]));
    }
}