<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Programa;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Linea;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Programa controller.
 *
 * @Route("programa")
 */
class ProgramaController extends Controller
{
    /**
     * Lists all programa entities.
     *
     * @Route("/", name="programa_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $programa = $em->getRepository('AppBundle:Programa')->findAll();

        return $this->render('programa/index.html.twig', array(
            'programa' => $programa,
        ));
    }

    /**
     * Creates a new programa entity.
     *
     * @Route("/new", name="programa_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $programa = new Programa();
//        $form = $this->createForm('AppBundle\Form\ProgramaType', $programa);
//        $form->handleRequest($request);
        $form = $this->createFormBuilder($programa)
                ->add('descripcion',TextType::class, array('label' => 'Descripción', 'attr' => array('class' => 'form-control','id' => 'descrip', 'placeholder' => 'Descripción')))
                ->add('linea',EntityType::class, array('label' => 'Linea', 'attr' => array('class' => 'form-control selectpicker' , 'id' => 'dateCreate' , 'data-style' => 'btn-primary' , 'data-id' => 'dateCreacion' , 'data-live-search' => 'true' ),
                            'class' => 'AppBundle:Linea',
//                            'query_builder' => function (EntityRepository $er) {
//                                                    return $er->createQueryBuilder('u')
//                                                            ->where('u.id=2');
//                                                },
                            'choice_label' => 'descripcion',
                        ))
                ->add('save', SubmitType::class, array('label' => 'Guardar' , 'attr' => array('class' => 'btn btn-default')))
                ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($programa);
            $em->flush($programa);

            return $this->redirectToRoute('programa_show', array('id' => $programa->getId()));
        }

        return $this->render('programa/new.html.twig', array(
            'programa' => $programa,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a programa entity.
     *
     * @Route("/{id}", name="programa_show")
     * @Method("GET")
     */
    public function showAction(Programa $programa)
    {
        $deleteForm = $this->createDeleteForm($programa);

        return $this->render('programa/show.html.twig', array(
            'programa' => $programa,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing programa entity.
     *
     * @Route("/{id}/edit", name="programa_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Programa $programa)
    {
        $deleteForm = $this->createDeleteForm($programa);
        $editForm = $this->createForm('AppBundle\Form\ProgramaType', $programa);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('programa_edit', array('id' => $programa->getId()));
        }

        return $this->render('programa/edit.html.twig', array(
            'programa' => $programa,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a programa entity.
     *
     * @Route("/{id}", name="programa_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Programa $programa)
    {
        $form = $this->createDeleteForm($programa);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($programa);
            $em->flush($programa);
        }

        return $this->redirectToRoute('programa_index');
    }

    /**
     * Creates a form to delete a programa entity.
     *
     * @param Programa $programa The programa entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Programa $programa)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('programa_delete', array('id' => $programa->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
