<?php

namespace Rh\UserBundle\Controller;

use Rh\UserBundle\RhUserBundle;

use Symfony\Component\BrowserKit\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use JMS\SecurityExtraBundle\Annotation\Secure;

use FOS\UserBundle\Entity\UserManager;
use FOS\UserBundle\Model\UserManagerInterface;

use Rh\UserBundle\Entity\User;
use Rh\UserBundle\Repository\UserRepository;
use Rh\UserBundle\Form\Handler\UserHandler;
use Rh\UserBundle\Form\Type\RechercheUserFormType;
use Rh\UserBundle\Form\Type\UserType;
use Rh\UserBundle\Form\Type\ChefType;

/**
 * Classe du controleur de UserBundle
 * @author Simon
 *
 */
class UserController extends Controller
{
    /**
     * Fonction permettant de récupérer l'utilisateur connecté
     */
    public function indexAction()
    {
        // On récupère l'utilisateur connecté.
        $utilisateur = $this->container->get('security.context')->getToken()->getUser();
        
        // Si il n'y a pas d'utilisateur, on retourne un message d'erreur.
        if (!is_object($utilisateur)) {
            throw new AccessDeniedException('Vous n\'êtes pas authentifié.');
        }
        // Sinon, on retourne la page index de notre UserBundle avec l'utilisateur en paramètre.
        return $this->render('RhUserBundle:User:index.html.twig', array('utilisateur' => $utilisateur));
    }
    
    
    /**
     * Fonction permettant d'ajouter un utilisateur
     * @Secure(roles="ROLE_ADMINISTRATEUR, ROLE_RH")
     */
    public function ajouterAction()
    {
         // Création de mon propre utilisateur créé avec mon entitée
        $monUtilisateur = new User();

        // Création du formulaire
        $form = $this->createForm(new UserType, $monUtilisateur);
        
        // Création du gestionnaire de formulaires (handler)
        $formHandler = new UserHandler($form, $this->get('request'), $this->getDoctrine()->getEntityManager());
        
        if( $formHandler->process() == "true" )
        {
            // Si la requête s'est bien passée,
            // on affiche un message flash pour dire que l'utilisateur a été enregistré.
            $this->get('session')->setFlash('success', 'Utilisateur enregistré.');
            
            // Puis on redirige vers la page d'ajout d'utilisateur vide.
            return $this->redirect( $this->generateUrl('rhuser_ajouter'));
        } elseif ($formHandler->process() == "error") {
            // Sinon, si la requête nous retourne la valeur "error",
            // on affiche un message flash pour dire que l'utilisateur a été enregistré.
            $this->get('session')->setFlash('error', 'Erreur dans le formulaire.');
        }
        return $this->render('RhUserBundle:User:ajouter.html.twig', array('form' => $form->createView(),));
    }
    
    
    /**
     * Fonction qui liste les utlisateurs en fonction de la recherche tapée.
     */
    public function listAction()
    {
        // Récupération de l'objet "request" venant du serveur.
        $request = $this->getRequest();
        // On rentre dans la condition si le mot "search" est dans l'url en tant que paramètre.
        if ($request->query->has('search')) 
        {
            // On récupère notre UserRepository
            $userRepo = $this->getDoctrine()->getEntityManager()->getRepository('RhUserBundle:User');
            
            // On appelle ma fonction de recherche d'utilisateur par nom.
            $users = $userRepo->searchUserByName($request->query->get('search'));
            
            // On retourne la page avec la liste des utilisateurs.
            return $this->render('RhUserBundle:User:list.html.twig', array(
                    'users' => $users));
        }
        
       // On retourne la page sans données.
       return $this->render('RhUserBundle:User:list.html.twig'); 
    }
    
    
    /**
     * Fonction qui retourne la liste des chefs
     * @Secure(roles="ROLE_ADMINISTRATEUR, ROLE_RH")
     */
    public function listChefAction()
    {
        // Récupération de l'objet "request" venant du serveur.
        $request = $this->getRequest();
        // On rentre dans la condition si le mot "search" est dans l'url en tant que paramètre.
        if ($request->query->has('search'))
        {
            // On récupère notre UserRepository
            $userRepo = $this->getDoctrine()->getEntityManager()->getRepository('RhUserBundle:User');
            
            // On appelle la fonction de récupération des utilisateurs
            $users = $userRepo->searchUserByName($request->query->get('search'));
            
            // On instancie le tableau des chefs
            $chefs = array();
            // Pour chaque utilisateur, on vérifie si il a le le ROLE_CHEF ou non
            foreach ($users as $user)
            {
                // On récupère le tableau des rôles de l'utilisateur courant.
                $roles = $user->getRoles();
                // Pour chaque role du tableau retourné,
                foreach ($roles as $role)
                {
                    // On vérifie si il est de valeur "ROLE_CHEF".
                    if ($role == 'ROLE_CHEF')
                    {
                        // Si il l'est, on ajoute l'utilisateur à notre tableau de chefs.
                        $chefs[] = $user;
                    }
                }
            }
            // On retourne la page avec la liste des chefs.
            return $this->render('RhUserBundle:User:listChef.html.twig', array(
                    'chefs' => $chefs));
        }
        // On retourne la page sans données.
       return $this->render('RhUserBundle:User:listChef.html.twig'); 
    }
    
    
    /**
     * Fonction pour modifier un utilisateur.
     * 
     * @param int $id
     */
    public function modifierAction($id)
    {
        // Récupération de l'utilsateur
        $user = $this->getDoctrine()
                        ->getEntityManager()
                        ->getRepository('RhUserBundle:User')
                        ->find($id);
        
        // Création du formulaire pré rempli
        $form = $this->createForm(new UserType(), $user);
        // Si il n'y a pas d'utilisateur,
        if ($user == null)
        {
            // on retourne une exception avec un message.
            throw $this->createNotFoundException('Utilisateur[id='.$id.'] inexistant');
        } else {
            // Sinon,
            // on exécute notre requête envoyée par notre formulaire.
            $formHandler = new UserHandler($form, 
                    $this->getRequest('request'), 
                    $this->getDoctrine()->getEntityManager());
            
            // Si la requête a fonctionnée,
            if( $formHandler->process() == "true" )
            {
                // On affiche un message flash pour dire que l'utilisateur a été modifié.
                $this->get('session')->setFlash('success', 'Utilisateur modifié.');
                
                // Puis on redirige vers la page de recherche d'utilisateur.
                return $this->redirect( $this->generateUrl('rhuser_list'));
            } elseif ($formHandler->process() == "error") {
                // Sinon,
                // On affiche un message flash pour dire que l'utilisateur a été enregistré.
                $this->get('session')->setFlash('error', 'Erreur dans le formulaire.');
            }
        }
        // On retourne la vue modifier avec le formulaire vide.
        return $this->render('RhUserBundle:User:modifier.html.twig', array('form' => $form->createView(),));
    }
    
    
    /**
     * Fonction permettant de supprimer l'utilisateur désigné par l'id.
     * @Secure(roles="ROLE_ADMINISTRATEUR, ROLE_RH")
     * @param int $id
     */
    public function supprimerAction($id)
    {
        // Récupération de l'utilsateur grâce à son ID.
        $user = $this->getDoctrine()
                        ->getEntityManager()
                        ->getRepository('RhUserBundle:User')
                        ->find($id);
        
        // Création du formulaire avec l'ID en valeur cachée
        $form = $this->createFormBuilder(array('id' => $id))
                        ->add('id', 'hidden')
                        ->getForm();
        
        // On récupère l'objet "request"
        $request = $this->getRequest();
        // On ajoute cet objet au formulaire.
        $form->bindRequest($request);
        // Si le formulaire est valide,
        if ($form->isValid()) {
            // On récupère l'utilisateur par son ID
            $em = $this->getDoctrine()->getEntityManager();
            $entity = $em->getRepository('RhUserBundle:User')->find($id);
            // Si il n'y a pas d'entitée USER correspondant à cet ID,
            if (!$entity) {
                // On affiche un message flash pour dire qu'il y a eu une erreur de suppression.
                $this->get('session')->setFlash('error', 'Erreur de suppression.');
                throw $this->createNotFoundException('Impossible de trouver l\'entitée.');
            } else {
                // Sinon, on supprime l'entitée.
                $em->remove($entity);
                // Puis on sauvegarde dans la BDD.
                $em->flush();
                
                // On affiche un message flash pour dire que l'utilisateur a été supprimé.
                $this->get('session')->setFlash('success', 'Utilisateur supprimé.');
                // On redirige vers la page de la liste des utilisateurs (page de recherche).
                return $this->redirect($this->generateUrl('rhuser_list'));
            }
        }
        // On retourne la page de suppresion avec le formulaire et l'utilisateur à supprimer.
        return $this->render('RhUserBundle:User:supprimer.html.twig', array(
                'form' => $form->createView(),
                'user' => $user));
    }
    
    
    /**
     * Fonction qui permet d'afficher le formulaire d'ajout de chef (construction d'une équipe)
     * @Secure(roles="ROLE_ADMINISTRATEUR, ROLE_RH")
     * @param int $id
     */
    public function addChefAction($id)
    {
        // On récupère notre chef en fonction de son ID.
        $userRepo = $this->getDoctrine()->getEntityManager()->getRepository('RhUserBundle:User');
        $chef = $userRepo->find($id);

        // Ajout d'une propriété à l'objet Chef.
        $chef->employes = array();
        
        // Appel de notre fonction dans le Repository
        $employes = $userRepo->recupUserSansChefOuChefDefini($id);
        
        // Si on a une requête qui n'est pas de type POST,
        if ($this->getRequest()->getMethod() != 'POST') 
        {
            // On créé un tableau 'actives'
            $actives = array();
            // Pour chaque employé du tableau retourné par la requête,
            foreach ($employes as $employe)
            {
                // On regarde si il a un chef.
                $dummy = $employe->getChef();
                // Si il a un chef,
                if (!empty($dummy))
                {
                    // on rentre son ID dans le tableau 'actives'
                    $actives[] = $employe->getId();
                }
            }
            // On donne la liste de ses employés au chef.
            $chef->employes = $actives;
        }
        
        // Création du formulaire
        $form = $this->createForm(new ChefType($employes), $chef);
        
        // Si la requête a été faite par une méthode POST,
        if ($this->getRequest()->getMethod() == 'POST') {
            // On récupère la requête du formulaire.
            $form->bindRequest($this->getRequest());
            // On vérifie que le formulaire soit valide.
            if ($form->isValid()) {
                // On fait essaye de faire notre requête pour insérer l'ID du chef dans chacun de ses employés.
                try {
                    $userRepo->saveEmployes($chef->getId(), $chef->employes);
                } catch (Exception $e) {
                    // Si on nous retourne une erreur, on retourne le message d'erreur sur le template "formEquipe".
                    $this->get('session')->setFlash('error', 'Erreur :'.$e);
                    return $this->render('RhUserBundle:User:formEquipe.html.twig', array(
                            'form' => $form->createView(),
                            'chef' => $chef));
                }
                // Si tout se passe bien, on retourne un message pour dire que la modification s'est bien passée
                $this->get('session')->setFlash('success', 'L\'équipe de '.$chef->getPrenom().' '.$chef->getNom().' a bien été modifée.');
                // et on redirige vers la liste des chefs.
                return $this->redirect($this->generateUrl('rhuser_listChef'));
            }
        }
        // On redirige vers le formulaire d'équipe.
        return $this->render('RhUserBundle:User:formEquipe.html.twig', array(
                'form' => $form->createView(),
                'chef' => $chef));
    }
}