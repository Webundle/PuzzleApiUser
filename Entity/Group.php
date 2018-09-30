<?php

namespace Puzzle\Api\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;

use Doctrine\Common\Collections\Collection;
use Puzzle\OAuthServerBundle\Traits\PrimaryKeyable;
use Puzzle\OAuthServerBundle\Traits\Describable;
use Puzzle\OAuthServerBundle\Traits\Nameable;
use Knp\DoctrineBehaviors\Model\Blameable\Blameable;
use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;
use Puzzle\OAuthServerBundle\Entity\User;

/**
 * User Group
 *
 * @ORM\Table(name="user_group")
 * @ORM\Entity()
 * @JMS\ExclusionPolicy("all")
 * @JMS\XmlRoot("user_group")
 * @Hateoas\Relation(
 * 		name = "self",
 * 		href = @Hateoas\Route(
 * 			"get_user_group",
 * 			parameters = {"id" = "expr(object.getId())"},
 * 			absolute = true,
 * ))
 * @Hateoas\Relation(
 * 		name = "users", 
 *      exclusion = @Hateoas\Exclusion(excludeIf = "expr(object.getUsers() === null)"),
 *      embedded = "expr(object.getUsers())"
 * ))
 */
class Group
{
    use PrimaryKeyable,
        Describable,
        Nameable,
        Blameable,
        Timestampable;
    
    /**
     * @ORM\ManyToMany(targetEntity="Puzzle\OAuthServerBundle\Entity\User")
     * @ORM\JoinTable(name="users_groups",
     *      joinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $users;
    
    public function __construct() {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function setUsers (Collection $users) : self {
        foreach ($users as $user) {
            $this->addUser($user);
        }
        
        return $this;
    }
    
    public function addUser(User $user) :self {
        if ($this->users->count() === 0 || $this->users->contains($user) === false) {
            $this->users->add($user);
            $user->addGroup($this);
        }
        
        return $this;
    }
    
    public function removeUser(User $user) :self {
        if ($this->users->contains($user) === true) {
            $this->users->removeElement($user);
        }
        
        return $this;
    }
    
    public function getUsers() :?Collection {
        return $this->users;
    }
}