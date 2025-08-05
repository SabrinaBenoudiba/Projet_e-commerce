<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomePageController extends AbstractController
{
    #[Route('/', name: 'app_home_page', methods: ['GET'])]// methode Get on demande des infos au serveur et post pour envoyer des demandes au serveurs
    public function index(ProductRepository $productRepository, CategoryRepository $categoryRepository): Response
    {
        return $this->render('home_page/homePage.html.twig', [
            'controller_name' => 'HomePageController',
            'products' => $productRepository -> findAll(),
            'categories' => $categoryRepository -> findAll()
        ]);
    }

    #[Route('/product/{id}/show', name: 'app_home_product_show', methods: ['GET'])]// methode Get on demande des infos au serveur et post pour envoyer des demandes au serveurs
    public function showProduct(Product $product, ProductRepository $productRepository): Response
    {
        $lastProductsAdd = $productRepository->findBy([], ['id'=>'DESC'],5); //1ere condition : aucun filtrage pour récuperer tous les produtis, le 2eme : est pour classer les résultats par l'id par l'ordre décroissant et le 3ème argument c'est pour demander de nous retourner que 5 résultats

        return $this->render('home_page/show.html.twig', [
            'product' => $product,
            'products' =>$lastProductsAdd
        ]);
    }

}

            