<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;


/**
 * Actividad
 * 
 * @ORM\Entity
 * @ORM\Table(name="actividad")
 */
class Actividad
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
     * 
     * @var string
     * 
     * @ORM\Column(name="codigo", type="string", length=15)
     */
    private $codigo;
    
    /**
     * @ORM\ManyToOne(targetEntity="Programa", inversedBy="programa")
     * @ORM\JoinColumn(name="programa", referencedColumnName="id")
     */
    private $programa;
    
    public function __construct()
    {
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
     * @return Programa
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
     * @return Programa
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
     * Set codigo
     *
     * @param string $codigo
     *
     * @return Programa
     */
    public function setCodigo($codigo)
    {
        $this->codigo = $codigo;

        return $this;
    }

    /**
     * Get codigo
     *
     * @return int
     */
    public function getCodigo()
    {
        return $this->codigo;
    }
    
    /**
     * Set programa
     *
     * @param integer $programa
     *
     * @return Actividad
     */
    public function setPrograma($programa)
    {
        $this->programa = $programa;

        return $this;
    }

    /**
     * Get programa
     *
     * @return int
     */
    public function getPrograma()
    {
        return $this->programa;
    }
}
