<?php

namespace Puzzle\Api\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use JMS\Serializer\Annotation as JMS;
use Hateoas\Configuration\Annotation as Hateoas;
use Puzzle\OAuthServerBundle\Traits\PrimaryKeyable;
use Puzzle\OAuthServerBundle\Entity\Client;
use Puzzle\OAuthServerBundle\Entity\User;
use Knp\DoctrineBehaviors\Model\Blameable\Blameable;
use Knp\DoctrineBehaviors\Model\Timestampable\Timestampable;
                                        
/**
* User
*
* @ORM\Table(name="user_account")
* @ORM\Entity()
* @JMS\ExclusionPolicy("all")
* @JMS\XmlRoot("user")
* @Hateoas\Relation(
* 		name = "self", 
* 		href = @Hateoas\Route(
* 			"get_user", 
* 			parameters = {"id" = "expr(object.getId())"},
* 			absolute = true,
* ))
*/
class Account
{
    const ROLE_DEFAULT = 'ROLE_USER';
 
    use PrimaryKeyable, Blameable, Timestampable;
 
    /**
    * @ORM\Column(name="first_name", type="string", length=25)
    * @JMS\Expose
    * @JMS\Type("string")
    */
    private $firstName;
  
    /**
    * @ORM\Column(name="last_name", type="string", length=25)
    * @JMS\Expose
    * @JMS\Type("string")
    */
    private $lastName;
  
    /**
    * @ORM\Column(name="phone", type="string", length=255, nullable=true)
    * @JMS\Expose
    * @JMS\Type("string")
    */
    private $phone;

    /**
    * @ORM\Column(type="string", length=255, unique=true)
    * @JMS\Expose
    * @JMS\Type("string")
    */
    private $username;

    /**
    * @ORM\Column(type="string", length=255, unique=true)
    * @JMS\Expose
    * @JMS\Type("string")
    */
    private $email;
      
    /**
    * @ORM\Column(type="boolean")
    * @var boolean $enabled
    * @JMS\Expose
    * @JMS\Type("boolean")
    */
    protected $enabled;
      
    /**
    * @ORM\Column(type="boolean")
    * @var boolean $locked
    * @JMS\Expose
    * @JMS\Type("boolean")
    */
    protected $locked;
      
    /**
    * @ORM\Column(name="account_expires_at", type="datetime", nullable=true)
    * @var \DateTime $accountExpiresAt
    * @JMS\Expose
    * @JMS\Type("DateTime<'Y-m-d H:i'>")
    */
    protected $accountExpiresAt;
      
    /**
    * @ORM\Column(name="credentials_expires_at", type="datetime", nullable=true)
    * @var \DateTime $credentialsExpiresAt
    * @JMS\Expose
    * @JMS\Type("DateTime<'Y-m-d H:i'>")
    */
    protected $credentialsExpiresAt;
      
    /**
    * @ORM\Column(name="confirmation_token", type="string", nullable=true)
    * @var string $confirmationToken
    * @JMS\Expose
    * @JMS\Type("string")
    */
    protected $confirmationToken;
      
    /**
    * @ORM\Column(name="password_requested_at", type="datetime", nullable=true)
    * @var \DateTime $passwordRequestedAt
    * @JMS\Expose
    * @JMS\Type("DateTime<'Y-m-d H:i'>")
    */
    protected $passwordRequestedAt;
      
    /**
    * @ORM\Column(name="password_changed", type="boolean")
    * @var boolean $passwordChanged
    * @JMS\Expose
    * @JMS\Type("boolean")
    */
    protected $passwordChanged;
      
    /**
    * @ORM\Column(name="roles", type="array")
    * @var array
    * @JMS\Expose
    * @JMS\Type("array")
    */
    private $roles = array();
    
    /**
     * @ORM\ManyToOne(targetEntity="Puzzle\OAuthServerBundle\Entity\Client")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $client;
    
    public function __construct() {
        $this->roles = [self::ROLE_DEFAULT];
        $this->enabled = true;
        $this->locked = false;
        $this->passwordChanged = false;
    }
    
    public function setClient(Client $client) :self {
        $this->client = $client;
        return $this;
    }
    
    public function getClient() :?Client {
        return $this->client;
    }
    
    public function getUsername() {
        return $this->username;
    }
    
    public function setUsername($username) {
        $this->username = $username;
        return $this;
    }
    
    public function getSalt() {
        return $this->salt;
    }
    
    public function setSalt($salt) {
        $this->salt = $salt;
        return $this;
    }
    
    public function hasRole(string $role) :bool {
        return in_array(strtoupper($role), $this->roles, true);
    }
      
    public function addRole(string $role) :self {
        $role = strtoupper($role);
        if (false === in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }
        
        return $this;
    }
      
    public function setRoles(array $roles) :self {
        foreach ($roles as $role) {
            $this->addRole($role);
        }
        
        return $this;
    }
      
    public function removeRole(string $role) :self {
        $role = strtoupper($role);
        if ($role !== static::ROLE_DEFAULT) {
            if (false !== ($key = array_search($role, $this->roles, true))) {
                unset($this->roles[$key]);
                $this->roles = array_values($this->roles);
            }
        }
          
        return $this;
    }
      
    public function getRoles() {
        return $this->roles;
    }

    public function setEmail($email) {
        $this->email = $email;
        return $this;
    }

    public function getEmail() {
        return $this->email;
    }

    public function setFirstName($firstName) {
        $this->firstName = $firstName;
        return $this;
    }

    public function getFirstName() {
        return $this->firstName;
    }

    public function setLastName($lastName) {
        $this->lastName = $lastName;
        return $this;
    }

    public function getLastName() {
        return $this->lastName;
    }
    
    public function setPhone($phone) {
        $this->phone = $phone;
        return $this;
    }

    public function getPhone() {
        return $this->phone;
    }
    
    public function getPassword() {
        return $this->password;
    }
    
    public function setPassword($password) {
        $this->password = $password;
        return $this;
    }
    
    public function getAccountExpiresAt() :?\DateTime {
        return $this->accountExpiresAt;
    }
    
    public function setAccountExpiresAt(\DateTime $expiresAt = null) :self {
        $this->accountExpiresAt = $expiresAt;
        return $this;
    }
    
    public function isAccountNonExpired() {
        return $this->accountExpiresAt instanceof \DateTime ?
        $this->accountExpiresAt->getTimestamp() >= time () : true;
    }
    
    public function getCredentialsExpiresAt() :?\DateTime {
        return $this->credentialsExpiresAt;
    }
    
    public function setCredentialsExpiresAt(\DateTime $expiresAt = null) :self {
        $this->credentialsExpiresAt = $expiresAt;
        return $this;
    }
    
    public function isCredentialsNonExpired() {
        return $this->credentialsExpiresAt instanceof \DateTime ?
        $this->credentialsExpiresAt->getTimestamp() >= time () : true;
    }
    
    public function setEnabled(bool $enabled) :self {
        $this->enabled = $enabled;
        return $this;
    }
    
    public function isEnabled() {
        return $this->enabled;
    }
    
    public function setLocked(bool $locked) :self {
        $this->locked = $locked;
        return $this;
    }
    
    public function isLocked() {
        return $this->locked;
    }
    
    public function isAccountNonLocked() {
        return !$this->locked;
    }
    
    public function getConfirmationToken() :?string {
        return $this->confirmationToken;
    }
    
    public function setConfirmationToken(string $confirmationToken = null) :?self {
        $this->confirmationToken = $confirmationToken;
        return $this;
    }
    
    public function getPasswordRequestedAt() :?\DateTime {
        return $this->passwordRequestedAt;
    }
    
    public function setPasswordRequestedAt(\DateTime $passwordRequestedAt = null) :self {
        $this->passwordRequestedAt = $passwordRequestedAt;
        return $this;
    }
    
    public function isPasswordRequestNonExpired(int $ttl) :bool {
        return $this->passwordRequestedAt instanceof \DateTime &&
        $this->passwordRequestedAt->getTimestamp() + $ttl > time();
    }
    
    public function setPasswordChanged(bool $passwordChanged) :self {
        $this->passwordChanged = $passwordChanged;
        return $this;
    }
    
    public function isPasswordChanged() {
        return $this->passwordChanged;
    }
    
    public function getFullName(int $width = null) :?string {
        $fullName = $this->firstName ?: '';
        $fullName .= $this->lastName && $this->firstName ? ' '.$this->lastName : ($this->lastName ?: '');
        
        return $width && $fullName ? mb_strimwidth($fullName, 0, $width, '...') : $fullName;
    }
    
    public function __toString() {
        return $this->getFullName() ?: $this->username;
    }
}
