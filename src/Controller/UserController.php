<?php

namespace App\Controller;

use id;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class UserController extends AbstractController
{
    #[Route('/admin/user', name: 'app_user')]
    public function user(UserRepository $userRepo): Response
    {
        return $this->render('user/user.html.twig', [
            'controller_name' => 'UserController',
            'users' => $userRepo->findAll(), //initialisation de la variable user qui aura pour valeur toutes les valeurs du repository de User et qu'on consomme immédiatement avec le find all
        ]);
    }

    #[Route('/admin/user/update/{id}', name: 'app_user_update_role')]
    public function updateRole(EntityManagerInterface $entityManager, User $user): Response
    {
      
        $user->setRoles(['ROLE_EDITOR', 'ROLE_USER']);
        $entityManager->flush();

        $this->addFlash('success', "Le rôle de l'éditeur a bien été ajouté à l'utilisateur");
        
        return $this->redirectToRoute('app_user');
    }

    #[Route('/admin/user/delete/{id}', name: 'app_user_delete_role')]
    public function deleteRole(EntityManagerInterface $entityManager, User $user): Response
    {
      
        $user->setRoles([]);
        $entityManager->flush();

        $this->addFlash('warning', "Le rôle de l'éditeur a bien été retiré à l'utilisateur");
        
        return $this->redirectToRoute('app_user');
    }

      #[Route('/admin/user/delete/{id}', name: 'app_user_delete_user')]
    public function deleteUser(EntityManagerInterface $entityManager, $id, UserRepository $userRpository): Response
    {
        
        $user = $userRpository->find($id);
        $entityManager->remove($user);
        $entityManager->flush();

        $this->addFlash('danger', "L'utilisateur a bien été supprimé");
        
        return $this->redirectToRoute('app_user');
    }



}
