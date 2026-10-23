<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\EventType;
use App\Entity\GuestRange;
use App\Entity\SpecialDate;
use App\Form\Admin\EventTypeFormType;
use App\Form\Admin\GuestRangeFormType;
use App\Form\Admin\SpecialDateFormType;
use App\Form\Admin\TravelSettingsFormType;
use App\Repository\EventTypeRepository;
use App\Repository\GuestRangeRepository;
use App\Repository\SpecialDateRepository;
use App\Settings\SettingsProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pricing rules of the online quote: guest ranges, event types, special dates and travel fees.
 */
#[Route('/admin/devis/regles')]
final class QuoteRulesController extends AbstractController
{
    /** URL segment => [entity class, form type, singular label]. */
    private const RULES = [
        'personnes' => [GuestRange::class, GuestRangeFormType::class, 'tranche de personnes'],
        'evenements' => [EventType::class, EventTypeFormType::class, "type d'événement"],
        'dates' => [SpecialDate::class, SpecialDateFormType::class, 'date spéciale'],
    ];
    private const RULE_REQUIREMENT = 'personnes|evenements|dates';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'admin_quote_rules', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SettingsProvider $settingsProvider,
        GuestRangeRepository $guestRanges,
        EventTypeRepository $eventTypes,
        SpecialDateRepository $specialDates,
    ): Response {
        $settings = $settingsProvider->get();
        $travelForm = $this->createForm(TravelSettingsFormType::class, $settings);
        $travelForm->handleRequest($request);

        if ($travelForm->isSubmitted() && $travelForm->isValid()) {
            $settingsProvider->save($settings);
            $this->addFlash('success', 'Les frais de déplacement ont été enregistrés.');

            return $this->redirectToRoute('admin_quote_rules', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/quote_rules/index.html.twig', [
            'travelForm' => $travelForm,
            'guestRanges' => $guestRanges->findAllOrdered(),
            'eventTypes' => $eventTypes->findAllOrdered(),
            'specialDates' => $specialDates->findAllOrdered(),
        ]);
    }

    #[Route('/{rule}/nouveau', name: 'admin_quote_rule_new', requirements: ['rule' => self::RULE_REQUIREMENT], methods: ['GET', 'POST'])]
    public function new(Request $request, string $rule): Response
    {
        [$class] = self::RULES[$rule];

        return $this->handleForm($request, $rule, new $class());
    }

    #[Route('/{rule}/{id}', name: 'admin_quote_rule_edit', requirements: ['rule' => self::RULE_REQUIREMENT, 'id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, string $rule, int $id): Response
    {
        return $this->handleForm($request, $rule, $this->findRule($rule, $id));
    }

    #[Route('/{rule}/{id}/supprimer', name: 'admin_quote_rule_delete', requirements: ['rule' => self::RULE_REQUIREMENT, 'id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, string $rule, int $id): Response
    {
        $entity = $this->findRule($rule, $id);

        if ($this->isCsrfTokenValid(\sprintf('delete-%s-%d', $rule, $id), $request->getPayload()->getString('_token'))) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('La %s a été supprimée.', self::RULES[$rule][2]));
        }

        return $this->redirectToRoute('admin_quote_rules', status: Response::HTTP_SEE_OTHER);
    }

    private function handleForm(Request $request, string $rule, object $entity): Response
    {
        [, $formType, $label] = self::RULES[$rule];
        $form = $this->createForm($formType, $entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();
            $this->addFlash('success', \sprintf('La %s a été enregistrée.', $label));

            return $this->redirectToRoute('admin_quote_rules', status: Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/quote_rules/form.html.twig', [
            'form' => $form,
            'label' => $label,
            'rule' => $rule,
            'entity' => $entity,
        ]);
    }

    private function findRule(string $rule, int $id): object
    {
        return $this->entityManager->find(self::RULES[$rule][0], $id) ?? throw $this->createNotFoundException();
    }
}
