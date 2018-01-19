<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Linea;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;use Symfony\Component\HttpFoundation\Request;

/**
 * Linea controller.
 *
 * @Route("linea")
 */
class LineaController extends Controller
{
    /**
     * Lists all linea entities.
     *
     * @Route("/", name="linea_index")
     * @Method("GET")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $lineas = $em->getRepository('AppBundle:Linea')->findAll();

        return $this->render('linea/index.html.twig', array(
            'lineas' => $lineas,
        ));
    }

    /**
     * Creates a new linea entity.
     *
     * @Route("/new", name="linea_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $linea = new Linea();
        $form = $this->createForm('AppBundle\Form\LineaType', $linea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($linea);
            $em->flush($linea);

            return $this->redirectToRoute('linea_show', array('id' => $linea->getId()));
        }

        return $this->render('linea/new.html.twig', array(
            'linea' => $linea,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a linea entity.
     *
     * @Route("/{id}", name="linea_show")
     * @Method("GET")
     */
    public function showAction(Linea $linea)
    {
        $deleteForm = $this->createDeleteForm($linea);

        return $this->render('linea/show.html.twig', array(
            'linea' => $linea,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing linea entity.
     *
     * @Route("/{id}/edit", name="linea_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Linea $linea)
    {
        $deleteForm = $this->createDeleteForm($linea);
        $editForm = $this->createForm('AppBundle\Form\LineaType', $linea);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('linea_edit', array('id' => $linea->getId()));
        }

        return $this->render('linea/edit.html.twig', array(
            'linea' => $linea,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a linea entity.
     *
     * @Route("/{id}", name="linea_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Linea $linea)
    {
        $form = $this->createDeleteForm($linea);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($linea);
            $em->flush($linea);
        }

        return $this->redirectToRoute('linea_index');
    }

    /**
     * Creates a form to delete a linea entity.
     *
     * @param Linea $linea The linea entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Linea $linea)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('linea_delete', array('id' => $linea->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
