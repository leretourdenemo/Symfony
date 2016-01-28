<?php
/**
 * Created by PhpStorm.
 * User: Laurent
 * Date: 22/01/2016
 * Time: 11:50
 */

namespace Lagueuche\PlatformBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class Antiflood extends Constraint
{
    public $message = "Vous avez déjà posté un message il y a moins de 15 secondes, merci d'attendre un peu.";

    public function validatedBy()
    {
        return 'lagueuche_platform_antiflood'; // Ici, on fait appel à l'alias du service
    }
}