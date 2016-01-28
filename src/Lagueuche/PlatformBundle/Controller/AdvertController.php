<?php

namespace Lagueuche\PlatformBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Lagueuche\PlatformBundle\Entity\Advert;
use Lagueuche\PlatformBundle\Form\AdvertType;
use Lagueuche\PlatformBundle\Form\AdvertEditType;
use Lagueuche\PlatformBundle\Bigbrother\BigbrotherEvents;
use Lagueuche\PlatformBundle\Bigbrother\MessagePostEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;


class AdvertController extends Controller
{
    public function indexAction($page)
    {
        if ($page < 1) {
            throw $this->createNotFoundException("La page ".$page." n'existe pas.");
        }

        $nbPerPage = 3;

        // Pour récupérer la liste de toutes les annonces : on utilise findAll()
        $listAdverts = $this->getDoctrine()
            ->getManager()
            ->getRepository('LagueuchePlatformBundle:Advert')
            ->getAdverts($page, $nbPerPage)
        ;

        // On calcule le nombre total de pages grâce au count($listAdverts) qui retourne le nombre total d'annonces
        $nbPages = ceil(count($listAdverts)/$nbPerPage);

        // Si la page n'existe pas, on retourne une 404
        if ($page > $nbPages) {
            throw $this->createNotFoundException("La page ".$page." n'existe pas.");
        }

        // On donne toutes les informations nécessaires à la vue
        return $this->render('LagueuchePlatformBundle:Advert:index.html.twig', array(
            'listAdverts' => $listAdverts,
            'nbPages'     => $nbPages,
            'page'        => $page
        ));
    }

    public function viewAction($id)
    {

        $em = $this->getDoctrine()->getManager();

        // Pour récupérer une annonce unique : on utilise find()
        $advert = $em->getRepository('LagueuchePlatformBundle:Advert')->find($id);

        // On vérifie que l'annonce avec cet id existe bien
        if ($advert === null) {
            throw $this->createNotFoundException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On récupère la liste des advertSkill pour l'annonce $advert
        $listAdvertSkills = $em->getRepository('LagueuchePlatformBundle:AdvertSkill')->findByAdvert($advert);

        // Puis modifiez la ligne du render comme ceci, pour prendre en compte les variables :
        return $this->render('LagueuchePlatformBundle:Advert:view.html.twig', array(
            'advert'           => $advert,
            'listAdvertSkills' => $listAdvertSkills,
        ));

    }

    public function addAction(Request $request)
    {
        // On crée un objet Advert
        $advert = new Advert();
        $advert->setUser($this->container->get('security.token_storage')->getToken()->getUser());

        // On crée le FormBuilder grâce au service form factory
        $form = $this->createForm(AdvertType::class, $advert);

        if ($form->handleRequest($request)->isValid()) {

            $em = $this->getDoctrine()->getManager();
            $em->persist($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Annonce bien enregistrée.');

            return $this->redirect($this->generateUrl('lagueuche_platform_view', array('id' => $advert->getId())));
        }

        // On passe la méthode createView() du formulaire à la vue
        // afin qu'elle puisse afficher le formulaire toute seule
        return $this->render('LagueuchePlatformBundle:Advert:add.html.twig', array(
            'form' => $form->createView(),
        ));
    }

    public function editAction($id, Request $request)
    {
        // On récupère l'EntityManager
        $em = $this->getDoctrine()->getManager();

        // On récupère l'entité correspondant à l'id $id
        $advert = $em->getRepository('LagueuchePlatformBundle:Advert')->find($id);

        // Si l'annonce n'existe pas, on affiche une erreur 404
        if ($advert == null) {
            throw $this->createNotFoundException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On crée le FormBuilder grâce au service form factory
        $form = $this->createForm(AdvertEditType::class, $advert);

        if($form->handleRequest($request)->isValid()){

            $em->flush();

            $request->getSession()->getFlashBag()->add('success', 'Annonce correctement modifiée.');

            return $this->redirect($this->generateUrl('lagueuche_platform_view', array('id'=>$advert->getId())));
        }

        return $this->render('LagueuchePlatformBundle:Advert:edit.html.twig', array(
            'form' => $form->createView(),
            'advert'    => $advert // Je passe également l'annonce à la vue si jamais elle veut l'afficher
        ));

    }

    public function deleteAction($id, Request $request)
    {
        // On récupère l'EntityManager
        $em = $this->getDoctrine()->getManager();

        // On récupère l'entité correspondant à l'id $id
        $advert = $em->getRepository('LagueuchePlatformBundle:Advert')->find($id);

        // Si l'annonce n'existe pas, on affiche une erreur 404
        if ($advert == null) {
            throw $this->createNotFoundException("L'annonce d'id ".$id." n'existe pas.");
        }

        // On crée un formulaire vide, qui ne contiendra que le champ CSRF
        // Cela permet de protéger la suppression d'annonce contre cette faille
        $form = $this->createFormBuilder()->getForm();

        if ($form->handleRequest($request)->isValid()) {
            $em->remove($advert);
            $em->flush();

            $request->getSession()->getFlashBag()->add('info', "L'annonce a bien été supprimée.");

            return $this->redirect($this->generateUrl('lagueuche_platform_home'));
        }

        // Si la requête est en GET, on affiche une page de confirmation avant de supprimer
        return $this->render('LagueuchePlatformBundle:Advert:delete.html.twig', array(
            'advert' => $advert,
            'form'   => $form->createView()
        ));
    }

    public function menuAction($limit = 3)
    {
        $listAdverts = $this->getDoctrine()
            ->getManager()
            ->getRepository('LagueuchePlatformBundle:Advert')
            ->findBy(
                array(),                 // Pas de critère
                array('date' => 'desc'), // On trie par date décroissante
                $limit,                  // On sélectionne $limit annonces
                0                        // À partir du premier
            );

        return $this->render('LagueuchePlatformBundle:Advert:menu.html.twig', array(
            'listAdverts' => $listAdverts
        ));
    }

    public function purgeAction($days)
    {
        $this->get('lagueuche_platform.advert_purger')->purge($days);
        return new Response('Les annonces sans candidatures sont nettoyées');
    }

    public function translationAction($name)
    {
        return $this->render('LagueuchePlatformBundle:Advert:translation.html.twig', array(
            'name' => $name
        ));
    }

    /**
     * @ParamConverter("json")
     */
    public function ParamConverterAction($json)
    {
        return new Response(print_r($json, true));
    }

}