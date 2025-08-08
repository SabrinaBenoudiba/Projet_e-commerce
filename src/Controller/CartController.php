<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Service\Cart;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class CartController extends AbstractController
{
    // service (objet qui fournit une fonctionnalité spécifique)
    public function __construct(private readonly ProductRepository $productRepository) 
    {
        // on définit ce qui sera accesssible que depuis cette classe, c'est pour l'encapsulation (niveau de sécurité), on prépare l'injection de dépendance d'un certain service plus tard ds le cours
        // readonly consommable que depuis l'intérieur de la classe, propriété assignée qu'une seule fois ds le constructeur, on aura accès au getter ms pas au setter, donc on ne pt pas modifier le panier
    }
#region CREATION DU PANIER
    #[Route('/cart', name: 'app_cart', methods: ['GET'])]
    public function index(SessionInterface $session, Cart $cart): Response
    {   
        $data = $cart->getCart($session);
        return $this->render('cart/index.html.twig', [
            'items'=>$data['cart'],
            'total'=>$data['total'],
        ]);
    }
#endregion CREATION
#region ADD PRODUCTS
        #[Route('/cart/add/{id}', name: 'app_cart_new', methods: ['GET'])]
        // Définit une route pour ajouter un produit au panier
        public function addProductToCart(int $id, SessionInterface $session): Response // int veut dire type integer obligatoire, + sécurisé
        // Méthode pour ajouter un produit au panier, prend l'ID du produit et la session en paramètres
        {
            $cart = $session->get('cart', []);
            // récupère le panier actuel de la session, ou un tableau vide s'il n'existe pas
            if (!empty($cart[$id])){
                $cart[$id]++;
            }else{
                $cart[$id]=1;
            }
            // Si le produit est déjà ds le panier, incrémente sa quantité sinon l'ajoute avec une quantité de 1
            $session->set('cart', $cart);
            // Met à jour le panier ds la session et redirige vers la page du panier
            return $this->redirectToRoute('app_cart');
        }
#endregion ADD PRODUCTS
#region REMOVE TO CART
        #[Route('/cart/remove/{id}', name: 'app_cart_product_remove', methods: ['GET'])]
        public function removeToCart($id, SessionInterface $session): Response
        {
             $cart = $session->get('cart', []);
            // récupère le panier actuel de la session, ou un tableau vide s'il n'existe pas
            if (!empty($cart[$id])){
                if ($cart[$id] > 1){
                    $cart[$id]--;
            }else{
                    unset($cart[$id]);
            }
           
            // Met à jour le panier
            $session->set('cart', $cart);
            
            }

        // Met à jour le panier ds la session et redirige vers la page du panier
            return $this->redirectToRoute('app_cart');

    }
#endregion REMOVE TO CART
#region DELETE CART
    #[Route('/cart/remove', name: 'app_cart_remove', methods: ['GET'])]
     public function deleteCart(SessionInterface $session): Response
        {
             $cart = $session->remove('cart', []);            
            
            return $this->redirectToRoute('app_cart');
            }

        // Met à jour le panier ds la session et redirige vers la page du panier
#endregion DELETE CART          
}