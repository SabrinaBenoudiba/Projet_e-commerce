<?php

namespace App\Controller;

use id;
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
    #[Route('/category', name: 'app_category')]
    public function category(CategoryRepository $repo): Response
    {
        $categories = $repo->findAll();

        // var_dump($categories);
        // dd($categories);

        return $this->render('category/category.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/category/new', name: 'app_category_new')]
    public function addCategory(EntityManagerInterface $entityManager, Request $request): Response
    {
        $category = new Category();

        $form =$this->createForm(CategoryFormType::class, $category);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();

            $this->addFlash('success', 'Votre catégorie a bien été créée');

            return $this->redirectToRoute('app_category');
        }
        return $this->render('category/newCategory.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/category/update/{id}', name: 'app_category_update')]
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

    #[Route('/category/delete/{id}', name: 'app_delete')]
    public function delete($id, EntityManagerInterface $entityManager): Response 
    {
        $category = $entityManager->getRepository(Category::class)->find($id); //attention à faire le repository sur l'entité que l'on veut
        
            $entityManager->remove($category); 
            $entityManager->flush();

            $this->addFlash('danger', 'Votre catégorie a bien été supprimée');

            return $this->redirectToRoute('app_category'); 
    }

}
