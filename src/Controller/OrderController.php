<?php

namespace App\Controller;

use App\Entity\City;
use App\Entity\Order;
use App\Entity\OrderProducts;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Service\Cart;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\ResponseInterface;

// Contrôleur final pour gérer les commandes
final class OrderController extends AbstractController
{

#region CREATED

    #[Route('/order', name: 'app_order')] // Déclare une route pour la création de commande
    public function index(EntityManagerInterface $entityManager, ProductRepository $productRepository, 
                         SessionInterface $session, Request $request, Cart $cart): Response
    {
        $data = $cart->getCart($session); // Récupère les données du panier depuis la session utilisateur         

       
        $order = new Order(); // Crée une nouvelle instance de la commande

        // Génère le formulaire de commande lié à l'objet $order
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        
        if($form->isSubmitted() && $form->isValid()) { // Vérifie si le formulaire a été soumis et est valide
 
            if($order->isPayOnDelivery()){ // Vérifie si le mode de paiement est "paiement à la livraison"
                if(!empty($data['total'])){
                $order->setTotalPrice($data['total']); // Définit le prix total de la commande
                $order->setCreatedAt(new \DateTimeImmutable()); // Définit la date de création de la commande à "maintenant"
                $entityManager->persist($order); // Prépare la commande pour l'enregistrement en base de données
                $entityManager->flush(); // Enregistre la commande dans la base de données

                    foreach($data['cart'] as $value) {  // Parcourt chaque article du panier pour l'enregistrer en tant que OrderProducts
                        $orderProduct = new OrderProducts();
                        $orderProduct->setOrder($order); // Lie l'objet OrderProducts à la commande courante
                        $orderProduct->setProduct($value['product']);// Lie le produit acheté
                        $orderProduct->setQuantity($value['quantity']);  // Spécifie la quantité pour ce produit
                        $entityManager->persist($orderProduct);
                        $entityManager->flush();// Enregistre chaque OrderProduct en base
                    }
                }
            }
            $session->set('cart', []); //Mise à jout du contenu du panier en session
            return $this->redirectToRoute('order_message');
        }

        return $this->render('order/index.html.twig', [ // Affiche le formulaire et le total du panier dans la vue "order/index.html.twig"
            'form' => $form->createView(),
            'total' => $data['total']
        ]);
    }
#endregion CREATION DE LA COMMANDE

#region MESSAGE
    #[Route('/order_message', name: 'order_message')]
    public function orderMessage() :Response 
    { 
        return  $this->render('order/order_message.html.twig');
    }
#endregion MESSAGE 

#region SHOW
    #[Route('editor/orders', name: 'app_orders_show')]
    #[IsGranted('ROLE_EDITOR')]
    public function getAllOrder(OrderRepository $orderRepository, Request $request, PaginatorInterface $paginator):Response
    {
        $orders = $orderRepository->findAll();
        $ordersknp = $paginator->paginate(
            $orders,
            $request->query->getInt('page', 1),//met en place la pagination
            5 //je choisi la limite de 8 articles par page
        );
        return $this->render('order/orders.html.twig',[
            'orders' => $ordersknp,
        ]);
    }
#endregion SHOW

#region UPDATE
    #[Route('editor/orders/{id}/is-completed/update', name: 'app_orders_is-completed-update')]
    #[IsGranted('ROLE_EDITOR')]
    public function isCompletedUpdate(Request $request, $id, OrderRepository $orderRepository, EntityManagerInterface $entityManager):Response
    {
        $order = $orderRepository->find($id);
        $order->setIsCompleted(true);
        $entityManager->flush();
        $this->addflash('success', 'Modification effectuée');
        return $this->redirectToRoute('app_orders_show');
    }
#endregion UPDATE

#region REMOVE
    #[Route('/editor/orders/{id}/remove', name: 'app_orders_remove')]
    public function removeOrder(Order $order, EntityManagerInterface $entityManager):Response 
    {
        $entityManager->remove($order);
        $entityManager->flush();
        $this->addFlash('danger', 'Commande supprimée');
        return $this->redirectToRoute('app_orders_show',['type']);
    }
#endregion REMOVE

#region SHIPPING COST
    #[Route('/city/{id}/shipping/cost', name: 'app_city_shipping_cost')] // Déclare une route pour obtenir le coût de livraison selon la ville choisie
    public function cityShippingCost(City $city): Response
    {
        $cityShippingPrice = $city->getShippingCost(); // Récupère le prix de livraison de la ville demandée
        
        return new Response(json_encode([  // Retourne la réponse au format JSON (utilisable par le JavaScript côté front)
            'status' => 200,
            "message" => 'on',
            'content' => $cityShippingPrice
        ]));
        // dd($city);  // (Ancienne ligne de debug)
    }
}
#endregion SHIPPING COST
