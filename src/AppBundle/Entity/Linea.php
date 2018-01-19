<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Linea
 *
 * @ORM\Entity
 * @ORM\Table(name="linea")
 */
class Linea
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="fecha", type="datetime")
     */
    private $fecha;
    
    /**
     * @var string
     *
     * @ORM\Column(name="vigencia", type="string", length=4)
     */
    private $vigencia;

    /** 
     * @var string
     *
     * @ORM\Column(name="descripcion", type="string", length=100)
     */
    private $descripcion;

    /**
     * @ORM\OneToMany(targetEntity="Programa", mappedBy="linea")
     */
    private $linea;
    
    public function __construct()
    {
        $this->linea = new ArrayCollection();
        $this->fecha  = new \DateTime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set fecha
     *
     * @param \DateTime $fecha
     *
     * @return Linea
     */
    public function setFecha($fecha)
    {
        $this->fecha = $fecha;

        return $this;
    }

    /**
     * Get fecha
     *
     * @return \DateTime
     */
    public function getFecha()
    {
        return $this->fecha;
    }
    

    /**
     * Set vigencia
     *
     * @param string $vigencia
     *
     * @return Linea
     */
    public function setVigencia($vigencia)
    {
        $this->vigencia = $vigencia;

        return $this;
    }

    /**
     * Get vigencia
     *
     * @return string
     */
    public function getVigencia()
    {
        return $this->vigencia;
    }    

    /**
     * Set descripcion
     *
     * @param string $descripcion
     *
     * @return Linea
     */
    public function setDescripcion($descripcion)
    {
        $this->descripcion = $descripcion;

        return $this;
    }

    /**
     * Get descripcion
     *
     * @return int
     */
    public function getDescripcion()
    {
        return $this->descripcion;
    }

    /**
     * Add linea
     *
     * @param \AppBundle\Entity\Programa $linea
     *
     * @return Linea
     */
    public function addLinea(\AppBundle\Entity\Programa $linea)
    {
        $this->linea[] = $linea;

        return $this;
    }

    /**
     * Remove linea
     *
     * @param \AppBundle\Entity\Programa $linea
     */
    public function removeLinea(\AppBundle\Entity\Programa $linea)
    {
        $this->linea->removeElement($linea);
    }

    /**
     * Get linea
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLinea()
    {
        return $this->linea;
    }
    
}

