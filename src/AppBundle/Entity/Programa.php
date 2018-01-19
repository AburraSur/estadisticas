<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Programa
 * 
 * @ORM\Entity
 * @ORM\Table(name="programa")
 */
class Programa
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
     * 
     * @var string
     * 
     * @ORM\Column(name="descripcion", type="string", length=150)
     */
    private $descripcion;
    
    /**
     * @ORM\ManyToOne(targetEntity="Linea", inversedBy="linea")
     * @ORM\JoinColumn(name="linea", referencedColumnName="id")
     */
    private $linea;
    
    /**
     * @ORM\OneToMany(targetEntity="Actividad", mappedBy="programa")
     */
    private $programa;
    
    public function __construct()
    {
        $this->programa = new ArrayCollection();
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
     * Set linea
     *
     * @param integer $linea
     *
     * @return Programa
     */
    public function setLinea($linea)
    {
        $this->linea = $linea;

        return $this;
    }

    /**
     * Get linea
     *
     * @return int
     */
    public function getLinea()
    {
        return $this->linea;
    }
    
    /**
     * Add programa
     *
     * @param \AppBundle\Entity\Actividad $programa
     *
     * @return Programa
     */
    public function addPrograma(\AppBundle\Entity\Actividad $programa)
    {
        $this->programa[] = $programa;

        return $this;
    }

    /**
     * Remove programa
     *
     * @param \AppBundle\Entity\Actividad $programa
     */
    public function removePrograma(\AppBundle\Entity\Actividad $programa)
    {
        $this->programa->removeElement($programa);
    }

    /**
     * Get programa
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getPrograma()
    {
        return $this->programa;
    }
}
