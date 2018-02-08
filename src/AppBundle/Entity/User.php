<?php

namespace AppBundle\Entity;
 
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
 
/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var array
     * 
     * @ORM\Column(name="menus", type="array", nullable=true)
     */
    protected $menus;
 
    public function __construct()
    {
        parent::__construct();
        // your own logic
        $this->menus = array();
    }
    
    /**
     * 
     */
    public function addMenu($menu)
    {
//        $menu = strtoupper($menu);
        if ($menu === static::ROLE_DEFAULT) {
            return $this;
        }

        if (!in_array($menu, $this->menus, true)) {
            $this->menus[] = $menu;
        }

        return $this;
    }
    
    /**
     * 
     */
    public function getMenus()
    {
        $menus = $this->menus;

        foreach ($this->getGroups() as $group) {
            $menus = array_merge($menus, $group->getMenus());
        }

        // we need to make sure to have at least one menu
        $menus[] = static::ROLE_DEFAULT;

        return array_unique($menus);
    }

    /**
     * 
     */
    public function hasMenu($menu)
    {
//        return in_array(strtoupper($menu), $this->getMenus(), true);
        return in_array($menu, $this->getMenus(), true);
    }
    
    /**
     * 
     */
    public function removeMenu($menu)
    {
//        if (false !== $key = array_search(strtoupper($menu), $this->menus, true)) {
        if (false !== $key = array_search($menu, $this->menus, true)) {
            unset($this->menus[$key]);
            $this->menus = array_values($this->menus);
        }

        return $this;
    }
    
    /**
     * 
     */
    public function setMenus(array $menus)
    {
        $this->menus = array();

        foreach ($menus as $menu) {
            $this->addMenu($menu);
        }

        return $this;
    }
}
