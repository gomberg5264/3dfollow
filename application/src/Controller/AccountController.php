<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Form\AccountType;
use App\Security\AppLoginFormAuthenticator;
use App\Security\TokenRefresher;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

/**
 * @Route("/account", name="account_")
 */
class AccountController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET", "POST"})
     */
    public function index(
        UserPasswordEncoderInterface $passwordEncoder,
        TokenRefresher $tokenRefresher,
        Request $request
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(AccountType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isValid = true;

            if ($form->get('newPassword')->getData()) {
                if ($passwordEncoder->isPasswordValid($user, $form->get('oldPassword')->getData())) {
                    $user->setPassword(
                        $passwordEncoder->encodePassword(
                            $user,
                            $form->get('newPassword')->getData()
                        )
                    );
                } else {
                    // todo trans
                    $form->get('oldPassword')->addError(new FormError('Veuillez saisir votre mot de passe actuel'));
                    $isValid = false;
                }
            }

            if ($isValid) {
                if ($user->getIsPrinter() && !$user->getTeamCreated()) {
                    $team = new Team();
                    $user->setTeamCreated($team);
                }

                $this->getDoctrine()->getManager()->flush();

                // todo trans
                $this->addFlash('success', 'Compte mis à jour');

                $tokenRefresher->refresh($user, $request);

                return $this->redirectToRoute('account_index');
            }
        }

        return $this->render('account/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
