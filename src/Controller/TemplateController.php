<?php

namespace App\Controller;

use App\Entity\Template;
use App\Entity\User;
use App\Entity\Form as FilledForm; // Alias to avoid conflict with FormType
use App\Entity\Comment;
use App\Form\TemplateType;
use App\Form\FormAnswerType;
use App\Service\TemplateManager;
use App\Service\FormManager;
use App\Service\CommentService;
use App\Repository\TemplateRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;

class TemplateController extends AbstractController
{
    #[Route('/templates', name: 'app_template_index')]
    public function index(TemplateRepository $templateRepository): Response
    {
        $templates = $templateRepository->findBy([], ['createdAt' => 'DESC']);

        return $this->render('template/index.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/template/new', name: 'app_template_new')]
    public function new(Request $request, TemplateManager $templateManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $template = new Template();
        $form = $this->createForm(TemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $templateManager->createTemplate($template, $this->getUser());
                $this->addFlash('success', 'Template created successfully!');
                return $this->redirectToRoute('app_template_show', ['id' => $template->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error creating template: ' . $e->getMessage());
            }
        }

        return $this->render('template/create_edit.html.twig', [
            'form' => $form->createView(),
            'template' => $template,
        ]);
    }

    #[Route('/template/{id}', name: 'app_template_show', requirements: ['id' => '\d+'])]
    public function show(Template $template, Request $request, CommentService $commentService, CommentRepository $commentRepository): Response
    {
        // Check access for restricted templates if not public
        if ($template->getAccessType() === 'restricted') {
            $user = $this->getUser();
            if (!$user || (!$template->getRestrictedUsers()->contains($user) && $template->getUser() !== $user && !$this->isGranted('ROLE_ADMIN'))) {
                throw new AccessDeniedException('You do not have access to this template.');
            }
        }

        $comments = $commentRepository->findCommentsForTemplate($template);

        return $this->render('template/show.html.twig', [
            'template' => $template,
            'comments' => $comments,
        ]);
    }

    #[Route('/template/{id}/edit', name: 'app_template_edit', requirements: ['id' => '\d+'])]
    public function edit(Request $request, Template $template, TemplateManager $templateManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Only template creator or admin can manage
        if ($template->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You are not authorized to edit this template.');
        }

        $form = $this->createForm(TemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $templateManager->updateTemplate($template);
                $this->addFlash('success', 'Template updated successfully!');
                return $this->redirectToRoute('app_template_show', ['id' => $template->getId()]);
            } catch (OptimisticLockException $e) {
                $this->addFlash('error', 'This template was modified by another user. Please review changes and try again.');
                // Optionally reload the template from DB to show latest state
                $this->getDoctrine()->getManager()->refresh($template);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating template: ' . $e->getMessage());
            }
        }

        return $this->render('template/create_edit.html.twig', [
            'form' => $form->createView(),
            'template' => $template,
        ]);
    }

    #[Route('/template/{id}/delete', name: 'app_template_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Template $template, TemplateManager $templateManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($template->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You are not authorized to delete this template.');
        }

        try {
            $templateManager->deleteTemplate($template);
            $this->addFlash('success', 'Template deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting template: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_template_index');
    }

    #[Route('/template/{templateId}/fill', name: 'app_template_fill', requirements: ['templateId' => '\d+'])]
    public function fillForm(Request $request, Template $template, FormManager $formManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        // Access check for restricted templates
        if ($template->getAccessType() === 'restricted') {
            $user = $this->getUser();
            if (!$user || (!$template->getRestrictedUsers()->contains($user) && $template->getUser() !== $user && !$this->isGranted('ROLE_ADMIN'))) {
                throw new AccessDeniedException('You do not have access to fill out this template.');
            }
        }

        $filledForm = new FilledForm();
        $form = $this->createForm(FormAnswerType::class, $filledForm, ['template' => $template]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formManager->createForm($filledForm, $template, $this->getUser());
                $this->addFlash('success', 'Form submitted successfully!');
                return $this->redirectToRoute('app_form_show', ['id' => $filledForm->getId()]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error submitting form: ' . $e->getMessage());
            }
        }

        return $this->render('form/fill.html.twig', [
            'template' => $template,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/form/{id}', name: 'app_form_show', requirements: ['id' => '\d+'])]
    public function showFilledForm(FilledForm $filledForm): Response
    {
        $user = $this->getUser();
        // Access control: Form creator, template creator, or admin
        if ($filledForm->getUser() !== $user && $filledForm->getTemplate()->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You are not authorized to view this form.');
        }

        return $this->render('form/show_filled.html.twig', [
            'form' => $filledForm,
            'template' => $filledForm->getTemplate(),
        ]);
    }

    #[Route('/form/{id}/edit', name: 'app_form_edit', requirements: ['id' => '\d+'])]
    public function editFilledForm(Request $request, FilledForm $filledForm, FormManager $formManager): Response
    {
        $user = $this->getUser();
        if ($filledForm->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You are not authorized to edit this form.');
        }

        $form = $this->createForm(FormAnswerType::class, $filledForm, ['template' => $filledForm->getTemplate()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $formManager->updateForm($filledForm);
                $this->addFlash('success', 'Form answers updated successfully!');
                return $this->redirectToRoute('app_form_show', ['id' => $filledForm->getId()]);
            } catch (OptimisticLockException $e) {
                $this->addFlash('error', 'These answers were modified by another user. Please review and try again.');
                $this->getDoctrine()->getManager()->refresh($filledForm); // Reload latest state
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating form answers: ' . $e->getMessage());
            }
        }

        return $this->render('form/fill.html.twig', [
            'template' => $filledForm->getTemplate(),
            'form' => $form->createView(),
            'filledForm' => $filledForm, // Pass for editing mode
        ]);
    }

    #[Route('/form/{id}/delete', name: 'app_form_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteFilledForm(FilledForm $filledForm, FormManager $formManager): Response
    {
        $user = $this->getUser();
        if ($filledForm->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            throw new AccessDeniedException('You are not authorized to delete this form.');
        }

        try {
            $formManager->deleteForm($filledForm);
            $this->addFlash('success', 'Form deleted successfully!');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error deleting form: ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_user_profile', ['id' => $this->getUser()->getId()]); // Redirect to user's profile
    }

    // API endpoint for adding comments (could be in a separate ApiController)
    #[Route('/api/template/{id}/comments', name: 'api_template_comments_add', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addComment(Template $template, Request $request, CommentService $commentService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $content = json_decode($request->getContent(), true)['content'] ?? null;

        if (!$content) {
            return $this->json(['error' => 'Comment content is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $comment = $commentService->addCommentToTemplate($template, $this->getUser(), $content);
            return $this->json([
                'id' => $comment->getId(),
                'user' => $comment->getUser()->getEmail(), // Or getUsername()
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('Y-m-d H:i:s')
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Could not add comment: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}