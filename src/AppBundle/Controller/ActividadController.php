<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Actividad;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Programa;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * Actividad controller.
 *
 * @Route("actividad")
 */
class ActividadController extends Controller
{
    /**
     * Lists all actividad entities.
     *
     * @Route("/", name="actividad_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $actividad = $em->getRepository('AppBundle:Actividad')->findAll();

        return $this->render('actividad/index.html.twig', array(
            'actividad' => $actividad,
        ));
    }

    /**
     * Creates a new actividad entity.
     *
     * @Route("/new", name="actividad_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $actividad = new Actividad();
//        $form = $this->createForm('AppBundle\Form\ActividadType', $actividad);
//        $form->handleRequest($request);
        $form = $this->createFormBuilder($actividad)
                ->add('descripcion',TextType::class, array('label' => 'Descripción', 'attr' => array('class' => 'form-control','id' => 'descrip', 'placeholder' => 'Descripción')))
                ->add('programa',EntityType::class, array('label' => 'Programa', 'attr' => array('class' => 'form-control selectpicker' , 'id' => 'dateCreate' , 'data-style' => 'btn-primary' , 'data-id' => 'dateCreacion' , 'data-live-search' => 'true' ),
                            'class' => 'AppBundle:Programa',
//                            'query_builder' => function (EntityRepository $er) {
//                                                    return $er->createQueryBuilder('u')
//                                                            ->where('u.id=2');
//                                                },
                            'choice_label' => 'descripcion',
                        ))
                ->add('codigo',TextType::class, array('label' => 'Código', 'attr' => array('class' => 'form-control','id' => 'codigo', 'placeholder' => 'Código')))
                ->add('save', SubmitType::class, array('label' => 'Guardar' , 'attr' => array('class' => 'btn btn-default')))
                ->getForm();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($actividad);
            $em->flush($actividad);

            return $this->redirectToRoute('actividad_show', array('id' => $actividad->getId()));
        }

        return $this->render('actividad/new.html.twig', array(
            'actividad' => $actividad,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a actividad entity.
     *
     * @Route("/{id}", name="actividad_show")
     * @Method("GET")
     */
    public function showAction(Actividad $actividad)
    {
        $deleteForm = $this->createDeleteForm($actividad);

        return $this->render('actividad/show.html.twig', array(
            'actividad' => $actividad,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing actividad entity.
     *
     * @Route("/{id}/edit", name="actividad_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Actividad $actividad)
    {
        $deleteForm = $this->createDeleteForm($actividad);
        $editForm = $this->createForm('AppBundle\Form\ActividadType', $actividad);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('actividad_edit', array('id' => $actividad->getId()));
        }

        return $this->render('actividad/edit.html.twig', array(
            'actividad' => $actividad,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a actividad entity.
     *
     * @Route("/{id}", name="actividad_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Actividad $actividad)
    {
        $form = $this->createDeleteForm($actividad);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($actividad);
            $em->flush($actividad);
        }

        return $this->redirectToRoute('actividad_index');
    }

    /**
     * Creates a form to delete a actividad entity.
     *
     * @param Actividad $actividad The actividad entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Actividad $actividad)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('actividad_delete', array('id' => $actividad->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
