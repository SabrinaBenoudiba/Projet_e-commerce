<?php

namespace App\Controller;

use App\Entity\Category;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CategoryController extends AbstractController
{
    #[Route('admin/category', name: 'app_category')] 
    public function category(CategoryRepository $repo): Response
    {
        $categories = $repo->findAll();

        // var_dump($categories);
        // dd($categories);

        return $this->render('category/category.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('admin/category/new', name: 'app_category_new')]  //Déclaration d'une route accessible via l'url admin/category/new

    //Méthode du controller qui gère la création d'une nouvelle catégorie
    //Elle a pour paramètre le gestionnaire d'entité EntityManagerIntreface pour la bdd et la requête http et elle renvoie une réponse
    public function addCategory(EntityManagerInterface $entityManager, Request $request): Response 
    
    {
        $category = new Category(); //Création d'une nouvelle instance de l'entity catégory

        
        $form =$this->createForm(CategoryFormType::class, $category); //Création d'un formulaire basé sur la classe CategoryFormType, lié à mon objet category ($category)
        $form->handleRequest($request); //Traite les données envoyées dans la requête pour remplir le formulaire

        if($form->isSubmitted() && $form->isValid()) { //Vérifie si le formulaire est soumis et si les données sont valides
            $entityManager->persist($category); //Prépare l'objet category de ma variable pour être envoyé en bdd
            $entityManager->flush(); //Exécute la requête d'insertion en bdd, ça sauvegarde ma category

            $this->addFlash('success', 'Votre catégorie a bien été créée'); //ajoute un message flash

            return $this->redirectToRoute('app_category'); //Redirige l'utilisateur vers la route indiqué : app_category
        }
        return $this->render('category/newCategory.html.twig', [ //Affiche le formulaire s'il est soumis et valide, mais ré affiche le formulaire grâce au form juste en dessous
            'form' => $form //Affiche le formulaire
        ]);
    }

    #[Route('admin/category/update/{id}', name: 'app_category_update')]
    public function updateCategory( EntityManagerInterface $entityManager, Request $request, Category $category): Response
    {

        $form =$this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Votre catégorie a bien été modifiée');

            return $this->redirectToRoute('app_category');

        }

        return $this->render('category/updateCategory.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('admin/category/delete/{id}', name: 'app_delete')]
    public function delete($id, EntityManagerInterface $entityManager): Response 
    {
        $category = $entityManager->getRepository(Category::class)->find($id); //attention à faire le repository sur l'entité que l'on veut
        
            $entityManager->remove($category); 
            $entityManager->flush();

            $this->addFlash('danger', 'Votre catégorie a bien été supprimée');

            return $this->redirectToRoute('app_category'); 
    }

}
