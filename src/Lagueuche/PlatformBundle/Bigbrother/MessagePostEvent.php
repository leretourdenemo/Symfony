<?php
/**
 * Created by PhpStorm.
 * User: Laurent
 * Date: 25/01/2016
 * Time: 17:42
 */


namespace Lagueuche\PlatformBundle\Bigbrother;

use Symfony\Component\EventDispatcher\Event;

class MessagePostEvent extends Event
{
    protected $message;
    protected $user;

    public function __construct($message, UserInterface $user)
    {
        $this->message = $message;
        $this->user    = $user;
    }

    // Le listener doit avoir accès au message
    public function getMessage()
    {
        return $this->message;
    }

    // Le listener doit pouvoir modifier le message
    public function setMessage($message)
    {
        return $this->message = $message;
    }

    // Le listener doit avoir accès à l'utilisateur
    public function getUser()
    {
        return $this->user;
    }

    // Pas de setUser, le listener ne peut pas modifier l'auteur du message !
}