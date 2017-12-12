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
     * @var string
     *
     * @ORM\Column(name="usuario", type="string", length=100)
     */
    private $usuario;

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
     * @var string
     *
     * @ORM\Column(name="ip", type="text", length=20)
     */
    private $ip;


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
     * Set usuario
     *
     * @param integer $usuario
     *
     * @return LogExtracciones
     */
    public function setUsuario($usuario)
    {
        $this->usuario = $usuario;

        return $this;
    }

    /**
     * Get usuario
     *
     * @return int
     */
    public function getUsuario()
    {
        return $this->usuario;
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
    
    /**
     * Set ip
     *
     * @param string $ip
     *
     * @return LogExtracciones
     */
    public function setIp($ip)
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * Get ip
     *
     * @return string
     */
    public function getIp()
    {
        return $this->ip;
    }
}

