<?php

namespace Lagueuche\PlatformBundle\Purger;

use Doctrine\ORM\EntityManager;

class LagueuchePurger
{
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    //Récupération des annonces sans candidature et non modifiées depuis X jours
    public function purge($days) {
        //on récupère la liste d'annonce à purger
        $listAdverts = $this->getDoctrine()
            ->getManager()
            ->getRepository('LagueuchePlatformBundle:Advert')
            ->findAdvertPurge($days)
        ;

        // On supprime les annonces
        foreach ($listAdverts as $advert) {
            $this->em->remove($advert);
        }

        $this->em->flush();
    }
}