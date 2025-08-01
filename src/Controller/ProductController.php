<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\ProductType;
use App\Entity\AddProductHistory;
use App\Form\AddProductHistoryType;
use App\Repository\ProductRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/editor/product')]
#[IsGranted('ROLE_EDITOR')]
final class ProductController extends AbstractController
{
    #[Route(name: 'app_product_index', methods: ['GET'])]
    public function index(ProductRepository $productRepository): Response
    {
        return $this->render('product/index.html.twig', [
            'products' => $productRepository->findAll(),
        ]);
    }

#region ADD
#[Route('/new', name: 'app_product_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
{
    $product = new Product();
    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $image = $form->get('image')->getData(); //on récupère le fichier de l'image et son contenu qui sera upload (chargé)

        if($image) { //si une image a bien été envoyée
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME); //on récupère le nom d'origine sans les extensions (jpeg, png,jpg)
            $safeImageName = $slugger->slug($originalName); //on va "slugger" (donc remplacer tous les accents espaces... par un "-")
            $newFileImageName = $safeImageName.'-'.uniqid().'.'.$image->guessExtension(); //ajoute un id unique avec les extensions

            try { //ça va déplacer le fichier (l'image) dans le dossier que j'ai défini dans le paramètre 'image_directory' 
                $image->move
                    ($this->getParameter('image_directory'),
                    $newFileImageName);
            }catch (FileException $exception) {
                //gestion d'un message d'erreur si besoin
            } 
            $product->setImage($newFileImageName); //on sauvegarde le nom du fichier 
        }

        $entityManager->persist($product);
        $entityManager->flush();

        $stockHistory = new AddProductHistory(); //nouvelle instanciation de la classe
        $stockHistory->setQuantity($product->getStock()); //on recup l'id du produit et on ajoute au stock
        $stockHistory->setProduct($product); //on insere le produit
        $stockHistory->setCreatedAt(new DateTimeImmutable());
        $entityManager->persist($stockHistory);
        $entityManager->flush(); // on effectue la maj en bdd

        $this->addFlash('success', "Le produit a bien été ajouté");

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('product/new.html.twig', [
        'product' => $product,
        'form' => $form,
    ]);
}
#endregion

#region SHOW
#[Route('/{id}', name: 'app_product_show', methods: ['GET'])]
public function show(Product $product): Response
{
    return $this->render('product/show.html.twig', [
        'product' => $product,
    ]);
}
#endregion

#region EDIT
#[Route('/{id}/edit', name: 'app_product_edit', methods: ['GET', 'POST'])]
public function edit(Request $request, Product $product, EntityManagerInterface $entityManager): Response
{
    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->flush();

        $this->addFlash('success', "Le produit a bien été modifié");

        return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
    }

    return $this->render('product/edit.html.twig', [
        'product' => $product,
        'form' => $form,
    ]);
}
#endregion

#region DELETE
#[Route('/{id}', name: 'app_product_delete', methods: ['POST'])]
public function delete(Request $request, Product $product, EntityManagerInterface $entityManager): Response
{
    if ($this->isCsrfTokenValid('delete'.$product->getId(), $request->getPayload()->getString('_token'))) {
        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('danger', "Le produit a bien été supprimé");
    }

    return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);
}
#endregion

#region STOCK ADD
#[Route('/add/product/{id}/', name: 'app_product_stock_add', methods: ['POST'])]
public function stockAdd($id, EntityManagerInterface $entityManager, Request $request): Response
{
    $stockAdd = new AddProductHistory();
    $form =$this->createForm(AddProductHistoryType::class, $stockAdd);
    $form->handleRequest($request);

    // $this->addFlash('danger', "Le produit a bien été supprimé");
 

    // return $this->redirectToRoute('app_product_index', [], Response::HTTP_SEE_OTHER);

    return $this->render('product/addStock.html.twig',
            ['form'=> $form->createView(),
            //  'product' => $product,
            ]
        );
}
#endregion
}


