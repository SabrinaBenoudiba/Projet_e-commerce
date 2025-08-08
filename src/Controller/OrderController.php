<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class OrderController extends AbstractController
{
    #[Route('/order', name: 'app_order')]
    public function index(EntityManagerInterface $entityManager, ProductRepository $productRepository, 
                            SessionInterface $session, Request $request): Response
    {
        $cart = $session->get('cart', []);   // récupére les données du panier

        $cartWithData = [];  // initialise un tableau pour stocker les données du panier avec les informations de produits

        
        foreach ($cart as $id => $quantity) {  // Boucle sur les éléments du panier pour récupérer les informations de produit
            
            $cartWithData[] = [ // récupère le produit correspondant à l'id et à la quantité
                'product' => $productRepository->find($id),
                'quantity'=> $quantity
            ];
        }
        $total = array_sum(array_map(function ($item){    // calcul total du panier, on mappe sur le tableau pour récupérer ts les items

             return $item['product']->getPrice() * $item['quantity'];   // Pour chaque élément du panier, multiplie le prix du produit par la quantité

        }, $cartWithData));
            
        $order = new Order();
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        return $this->render('order/index.html.twig', [
            'form' =>$form->createView(),
            'total'=>$total,
        ]);
    }

    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')]
        public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost();
        
        // reponse en json
        return new Response(json_encode(['status'=>200, "message"=>'on', 'content'=> $cityShippingPrice]));

        // dd($city);
    }
}