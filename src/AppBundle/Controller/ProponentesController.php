<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Controller\UtilitiesController;
use AppBundle\Entity\Logs;

class ProponentesController extends Controller
{
    
    /**
     * @Route("/extraccionProponentes", name="extraccionProponentes")
     */
    public function indexAction() {
        $emSII = $this->getDoctrine()->getManager('sii');
        $em = $this->getDoctrine()->getManager();
        if(!isset($_POST['estado'])){
            return $this->render('default/extraccionProponentes.html.twig');
        }else{
            $estado = $_POST['estado'];
            $tipoFecha = $_POST['tipoFecha'];
            $fecha = new \DateTime();
            $util = new UtilitiesController();
            $tipoId = $util->tipoId($emSII);
            $municipios = $util->municipios($emSII);
            
            $sqlProponente = "SELECT 
                                mep.proponente,
                                mep.matricula,
                                mep.nombre as razonSocial,
                                mep.ape1 as apellido1,
                                mep.ape2 as apellido2,
                                mep.nom1 as nombre1,
                                mep.nom2 as nombre2,
                                mep.sigla,
                                mep.idtipoidentificacion,
                                mep.identificacion,
                                mep.nit,
                                mep.organizacion,
                                mep.idestadoproponente,
                                mep.fechaultimarenovacion,
                                mep.fecactualizacion,
                                mep.telcom1,
                                mep.dircom,
                                mep.muncom,
                                mep.emailcom,
                                mep.telnot,
                                mep.dirnot,
                                mep.munnot,
                                mep.emailnot,
                                mev.numid,
                                mev.ape1,
                                mev.ape2,
                                mev.nom1,
                                mev.nom2,
                                mev.nombre
                            FROM
                                sii_aburra.mreg_est_proponentes mep
                                    LEFT JOIN
                                mreg_est_vinculos mev ON (mep.matricula = mev.matricula
                                    AND mev.vinculo IN (2170 , 2600, 4170) AND mev.estado='V')
                            WHERE
                                mep.idestadoproponente IN ($estado)  ";
            if($tipoFecha != ''){
                $sqlProponente.=" AND $tipoFecha BETWEEN :dateInit AND :dateEnd 
                            GROUP BY mep.proponente ORDER BY mep.proponente ";            
            
                $rowsProp = $emSII->getConnection()->prepare($sqlProponente);
                $params = array('dateInit' => $_POST['dateInit'] , 'dateEnd' => $_POST['dateEnd']);    
                $rowsProp->execute($params);
            }else{
                $rowsProp = $emSII->getConnection()->prepare($sqlProponente);  
                $rowsProp->execute();
            }
            $proponentes = $rowsProp->fetchAll();
            $totalFiltered = $totalData = sizeof($proponentes);
            
            if( isset($_POST['start']) ) {   
                    $sqlProponente.=" LIMIT ".$_POST['start']." ,".$_POST['length']."   ";
                    $rowsProp = $emSII->getConnection()->prepare($sqlProponente);
                    $params = array('dateInit' => $_POST['dateInit'] , 'dateEnd' => $_POST['dateEnd']);    
                    $rowsProp->execute($params);
                    $proponentes = $rowsProp->fetchAll();
//                    $totalFiltered = sizeof($proponentes);
            
            }
            $accion = "Consulta";
            
            for($i=0;$i<sizeof($proponentes);$i++){
                $excelData = array();
                $excelData[] = $proponentes[$i]['proponente'];
                $excelData[] = $proponentes[$i]['matricula'];
                $excelData[] = $tipoId[$proponentes[$i]['idtipoidentificacion']];
                $excelData[] = $proponentes[$i]['identificacion'];
                $excelData[] = $proponentes[$i]['razonSocial'];
                $excelData[] = $proponentes[$i]['sigla'];
                if($_POST['excel']==1){
                    $excelData[] = $proponentes[$i]['apellido1'];
                    $excelData[] = $proponentes[$i]['apellido2'];
                    $excelData[] = $proponentes[$i]['nombre1'];
                    $excelData[] = $proponentes[$i]['nombre2'];
                    $excelData[] = $proponentes[$i]['nit'];
                    $excelData[] = $proponentes[$i]['organizacion'];
                    $excelData[] = $proponentes[$i]['idestadoproponente'];
                    $excelData[] = $proponentes[$i]['fechaultimarenovacion'];
                    $excelData[] = $proponentes[$i]['fecactualizacion'];
                    $excelData[] = $proponentes[$i]['telcom1'];
                    $excelData[] = $proponentes[$i]['dircom'];
                    $excelData[] = $municipios['municipios'][$proponentes[$i]['muncom']];
                    $excelData[] = $proponentes[$i]['emailcom'];
                    $excelData[] = $proponentes[$i]['telnot'];
                    $excelData[] = $proponentes[$i]['dirnot'];
                    $excelData[] = $municipios['municipios'][$proponentes[$i]['munnot']];
                    $excelData[] = $proponentes[$i]['emailnot'];
                    $excelData[] = $proponentes[$i]['numid'];
                    $excelData[] = $proponentes[$i]['ape1'];
                    $excelData[] = $proponentes[$i]['ape2'];
                    $excelData[] = $proponentes[$i]['nom1'];
                    $excelData[] = $proponentes[$i]['nom2'];
                    $excelData[] = $proponentes[$i]['nombre'];
                    
                    $accion = "Exportación";
                    $encabezado = ['Proponente',
                                'matricula',
                                'Tipo Identificacion',
                                'Identificacion',
                                'Razon Social',
                                'Sigla',
                                'Primer Apellido',
                                'Segundo Apellido',
                                'Primer Nombre',
                                'Segundo Nombre',
                                'Nit',
                                'Organizacion',
                                'Estado',
                                'Fec ult ren',
                                'Fec actualizacion',
                                'Tel.comercial',
                                'Dir comercial',
                                'Mun comercial',
                                'email comercial',
                                'Tel notificacion',
                                'Dir notificacion',
                                'Mun notificacion',
                                'email notificacion',
                                'Id. Rep. Legal',
                                'Primer Apellido Rep. Legal',
                                'Segundo Apellido Rep. Legal',
                                'Primer Nombre Rep. Legal',
                                'Segundo Nombre Rep. Legal',
                                'Rep. Leagl'];
                }
                $excelResultados[] = $excelData;
//                $totalFiltered++;
            }
            
            $user = $this->get('security.token_storage')->getToken()->getUser();
            $usuario = $em->getRepository('AppBundle:User')->findOneById($user);
            $ipaddress = $this->container->get('request_stack')->getCurrentRequest()->getClientIp();
            
            $logs = new Logs();
            $logs->setFecha($fecha);
            $logs->setModulo('Extracción Proponentes');
            $logs->setQuery('Query: '.$sqlProponente.' Parametros: fecIni=>'.$_POST['dateInit'].'  , fecEnd => '.$_POST['dateEnd']." Acción: ".$accion);
            $logs->setUsuario($usuario->getUsername());
            $logs->setIp($ipaddress);

            $em->persist($logs);
            $em->flush($logs);
            
            if($_POST['excel']==1){
                $nomExcel = 'ExtraccionProponentes';
                $utilities = new UtilitiesController();
                $response = $utilities->exportExcel( $excelResultados, $encabezado,$nomExcel);
                return $response;
            }else{
                $json_data = array(
                    "draw"            => intval( $_POST['draw'] ),   // for every request/draw by clientside , they send a number as a parameter, when they recieve a response/data they first check the draw number, so we are sending same number in draw. 
                    "recordsTotal"    => intval( $totalData ),  // total number of records
                    "recordsFiltered" => intval( $totalFiltered ), // total number of records after searching, if there is no searching then totalFiltered = totalData
                    "data"            => $excelResultados,   // total data array
                    "sql"             => $sqlProponente  
		);
                return new response(json_encode($json_data));
            }    
            
        }
        
    }
}
