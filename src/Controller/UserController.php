<?php

namespace App\Controller;

use id;
use App\Entity\User;
use App\Entity\Product;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER')]
final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function user(UserRepository $userRepo): Response
    {
        return $this->render('user/user.html.twig', [
            'controller_name' => 'UserController',
            'users' => $userRepo->findAll(), //initialisation de la variable user qui aura pour valeur toutes les valeurs du repository de User et qu'on consomme immédiatement avec le find all
        ]);
    }

    #[Route('/admin/user/update/{id}', name: 'app_user_update_role')]
    #[IsGranted('ROLE_ADMIN')]
    public function updateRole(EntityManagerInterface $entityManager, User $user): Response
    {
      
        $user->setRoles(['ROLE_EDITOR', 'ROLE_USER']); //modifier, mettre à jour le rôle par role éditor et role user
        $entityManager->flush();

        $this->addFlash('success', "Le rôle de l'éditeur a bien été ajouté à l'utilisateur");
        
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/deleteRole/{id}', name: 'app_user_delete_role')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteRole(EntityManagerInterface $entityManager, User $user): Response
    {
      
        $user->setRoles([]); //mettre à jour pour qu'il n'est pas de rôle
        $entityManager->flush();

        $this->addFlash('warning', "Le rôle de l'éditeur a bien été retiré à l'utilisateur");
        
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/deleteUser/{id}', name: 'app_user_delete_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteUser(EntityManagerInterface $entityManager, $id, UserRepository $userRpository): Response
    {
        
        $user = $userRpository->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', "L'utilisateur a bien été supprimé");
        
        return $this->redirectToRoute('app_user');
    }

    #region FAVORITES
    #[Route('/user/add/product/{id}/favorites', name: 'app_product_favorites')]
    public function toggleFavoritesProduct(Product $product, #[CurrentUser] User $user, EntityManagerInterface $em, Request $request): Response
    {
        if (!$user->getFavorites()->contains($product)) {
        $user->addFavorite($product);
        }else{
        $user->removeFavorite($product);
    }
        $em->flush();

     
        return $this->redirect($request->headers->get('referer'));

    }
    #endregion FAVORITES

    #region SHOW
    #[Route('/user/show/favorites', name: 'app_product_favorites_show', methods: ['GET'])]
    public function show(#[CurrentUser] User $user): Response
    {   
        return $this->render('user/my_favorites.html.twig', [
            'products' => $user->getFavorites()
        ]);
    }
    #endregion
}
