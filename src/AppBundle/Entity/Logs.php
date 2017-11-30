<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * LogExtracciones
 *
 * @ORM\Entity
 * @ORM\Table(name="logs")
 */
class Logs
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
     * @ORM\ManyToOne(targetEntity="User", inversedBy="logs")
     * @ORM\JoinColumn(name="idUser", referencedColumnName="id")
     */
    private $idUser;

    /**
     * @var string
     *
     * @ORM\Column(name="modulo", type="string", length=255)
     */
    private $modulo;

    /**
     * @var string
     *
     * @ORM\Column(name="query", type="text")
     */
    private $query;


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
     * @return LogExtracciones
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
     * Set idUser
     *
     * @param integer $idUser
     *
     * @return LogExtracciones
     */
    public function setIdUser($idUser)
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get idUser
     *
     * @return int
     */
    public function getIdUser()
    {
        return $this->idUser;
    }

    /**
     * Set modulo
     *
     * @param string $modulo
     *
     * @return LogExtracciones
     */
    public function setModulo($modulo)
    {
        $this->modulo = $modulo;

        return $this;
    }

    /**
     * Get modulo
     *
     * @return string
     */
    public function getModulo()
    {
        return $this->modulo;
    }

    /**
     * Set query
     *
     * @param string $query
     *
     * @return LogExtracciones
     */
    public function setQuery($query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * Get query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }
}

